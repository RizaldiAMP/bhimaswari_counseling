<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CounselorProfile;
use App\Models\ServicePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EndToEndIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $counselor;
    private User $client;
    private ServicePrice $chatPrice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->client = User::factory()->create(['role' => 'client']);
        
        $this->counselor = User::factory()->create(['role' => 'counselor']);
        CounselorProfile::create([
            'user_id' => $this->counselor->id,
            'full_title' => 'Dr. Counselor, S.Psi',
            'practitioner_type' => 'counselor',
            'is_visible' => true,
        ]);

        $this->chatPrice = ServicePrice::create([
            'service_type' => 'chat',
            'practitioner_type' => 'counselor',
            'duration_minutes' => 60,
            'price' => 100000,
            'is_active' => true,
        ]);
    }

    public function test_full_booking_flow_for_chat_session(): void
    {
        // 1. Client Books a Chat Session
        $scheduleStart = now()->addDays(1)->setTime(9, 0, 0)->format('Y-m-d H:i:s');
        
        $response = $this->actingAs($this->client)->post(route('client.bookings.store'), [
            'counselor_id' => $this->counselor->id,
            'service_price_id' => $this->chatPrice->id,
            'schedule_start' => $scheduleStart,
            'intake_form' => str_repeat('Intake explanation here. ', 5), // > 100 chars
        ]);
        
        $response->assertRedirect();
        
        $booking = Booking::where('client_id', $this->client->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals('pending_payment', $booking->status);

        // 2. Client views booking list
        $response = $this->actingAs($this->client)->get(route('client.bookings.index'));
        $response->assertStatus(200);

        // 3. Admin views verifications
        $response = $this->actingAs($this->admin)->get(route('admin.verifications.index'));
        $response->assertStatus(200);

        // 4. Admin updates payment status to confirmed (Simulate manual confirmation or payment gateway)
        $booking->update(['status' => 'confirmed']);
        
        // 5. Counselor views their upcoming sessions
        $response = $this->actingAs($this->counselor)->get(route('counselor.bookings.index'));
        $response->assertStatus(200);

        // Advance time to schedule start so counselor can access the chat
        Carbon::setTestNow(Carbon::parse($booking->schedule_start));

        // 5.1 Simulate counselor opening the chat room to start the session (which changes status to 'in_session')
        $response = $this->actingAs($this->counselor)->get(route('counselor.chat.show', $booking->id));
        $response->assertStatus(200);

        // Refresh to check if status became in_session
        $booking->refresh();
        $this->assertEquals('in_session', $booking->status);

        // 6. Session time arrives. Counselor ends session
        // Route should be counselor.chat.complete
        $response = $this->actingAs($this->counselor)->post(route('counselor.chat.complete', $booking->id), [
            'notes' => 'Session went well.'
        ]);
        
        $response->assertRedirect();
        
        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
    }

    public function test_full_booking_flow_for_online_session(): void
    {
        $onlinePrice = ServicePrice::create([
            'service_type' => 'online',
            'practitioner_type' => 'counselor',
            'duration_minutes' => 60,
            'price' => 150000,
            'is_active' => true,
        ]);

        $scheduleStart = now()->addDays(2)->setTime(14, 0, 0)->format('Y-m-d H:i:s');
        
        // 1. Client Books Online Session
        $response = $this->actingAs($this->client)->post(route('client.bookings.store'), [
            'counselor_id' => $this->counselor->id,
            'service_price_id' => $onlinePrice->id,
            'schedule_start' => $scheduleStart,
            'intake_form' => str_repeat('Intake explanation here. ', 5),
        ]);
        $response->assertRedirect();
        
        $booking = Booking::where('client_id', $this->client->id)->latest('id')->first();
        $this->assertEquals('pending_payment', $booking->status);

        // 2. Admin confirm payment
        $booking->update(['status' => 'confirmed']);

        // 3. Counselor adds meeting link
        // Note: Route counselor.bookings.meeting.update
        $response = $this->actingAs($this->counselor)->put(route('counselor.bookings.meeting.update', $booking->id), [
            'meeting_link' => 'https://zoom.us/j/123456789'
        ]);
        $response->assertRedirect();
        $booking->refresh();
        $this->assertEquals('https://zoom.us/j/123456789', $booking->meeting_link);
    }

    public function test_full_booking_flow_for_offline_session(): void
    {
        $offlinePrice = ServicePrice::create([
            'service_type' => 'offline',
            'practitioner_type' => 'counselor',
            'duration_minutes' => 60,
            'price' => 200000,
            'is_active' => true,
        ]);

        $scheduleStart = now()->addDays(3)->setTime(10, 0, 0)->format('Y-m-d H:i:s');
        
        // 1. Client Books Offline Session
        $response = $this->actingAs($this->client)->post(route('client.bookings.store'), [
            'counselor_id' => $this->counselor->id,
            'service_price_id' => $offlinePrice->id,
            'schedule_start' => $scheduleStart,
            'intake_form' => str_repeat('Intake explanation here. ', 5),
        ]);
        $response->assertRedirect();
        
        $booking = Booking::where('client_id', $this->client->id)->latest('id')->first();
        $this->assertEquals('pending_payment', $booking->status);

        // 2. Admin confirm payment
        $booking->update(['status' => 'confirmed']);

        // 3. Counselor adds meeting location
        $response = $this->actingAs($this->counselor)->put(route('counselor.bookings.meeting.update', $booking->id), [
            'meeting_location' => 'Klinik Bhimaswari Lt 2 Ruang 5'
        ]);
        $response->assertRedirect();
        $booking->refresh();
        $this->assertEquals('Klinik Bhimaswari Lt 2 Ruang 5', $booking->meeting_location);
    }
}
