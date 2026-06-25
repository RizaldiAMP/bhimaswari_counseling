<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ServicePrice;
use App\Models\CounselorProfile;
use App\Models\Booking;
use App\Jobs\ExpireUnpaidBookings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $counselor;
    private ServicePrice $servicePrice;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = User::factory()->create(['role' => 'client']);
        
        $this->counselor = User::factory()->create(['role' => 'counselor']);
        CounselorProfile::create([
            'user_id' => $this->counselor->id,
            'full_title' => 'Dr. Counselor, S.Psi',
            'practitioner_type' => 'counselor',
            'is_visible' => true,
        ]);

        $this->servicePrice = ServicePrice::create([
            'service_type' => 'online',
            'practitioner_type' => 'counselor',
            'duration_minutes' => 60,
            'price' => 150000,
            'is_active' => true,
        ]);
    }

    public function test_client_can_create_booking_successfully(): void
    {
        Queue::fake([ExpireUnpaidBookings::class]);

        $scheduleStart = now()->addDays(1)->format('Y-m-d H:i:s');

        $response = $this->actingAs($this->client)->post(route('client.bookings.store'), [
            'counselor_id' => $this->counselor->id,
            'service_price_id' => $this->servicePrice->id,
            'schedule_start' => $scheduleStart,
            'intake_form' => str_repeat('A valid lengthy intake form explanation. ', 4), // > 100 chars
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(); // should redirect to show

        $this->assertDatabaseHas('bookings', [
            'client_id' => $this->client->id,
            'counselor_id' => $this->counselor->id,
            'service_type' => 'online',
            'status' => 'pending_payment',
        ]);

        Queue::assertPushed(ExpireUnpaidBookings::class);
    }

    public function test_prevents_double_booking_on_same_slot(): void
    {
        $scheduleStart = now()->addDays(1)->format('Y-m-d H:i:s');
        
        // First booking
        Booking::create([
            'client_id' => User::factory()->create()->id,
            'counselor_id' => $this->counselor->id,
            'service_price_id' => $this->servicePrice->id,
            'service_type' => 'online',
            'duration_minutes' => 60,
            'price_at_booking' => 150000,
            'schedule_start' => $scheduleStart,
            'schedule_end' => Carbon::parse($scheduleStart)->addMinutes(60),
            'status' => 'pending_payment',
            'intake_form' => 'dummy',
            'informed_consent' => true,
            'payment_deadline' => now()->addMinutes(15),
        ]);

        // Attempt second booking on the same slot
        $response = $this->actingAs($this->client)->post(route('client.bookings.store'), [
            'counselor_id' => $this->counselor->id,
            'service_price_id' => $this->servicePrice->id,
            'schedule_start' => $scheduleStart,
            'intake_form' => str_repeat('A valid lengthy intake form explanation. ', 4),
        ]);

        $response->assertSessionHasErrors(['schedule_start' => 'Slot jadwal ini bentrok dengan sesi yang sudah ada.']);
    }
}
