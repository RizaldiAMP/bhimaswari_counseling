<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CounselorProfile;
use App\Models\Payment;
use App\Models\ServicePrice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
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
            'full_title' => 'Counselor',
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

    public function test_client_can_reupload_proof_while_pending_verification(): void
    {
        Storage::fake('private');

        $booking = Booking::create([
            'client_id' => $this->client->id,
            'counselor_id' => $this->counselor->id,
            'service_price_id' => $this->servicePrice->id,
            'service_type' => 'online',
            'duration_minutes' => 60,
            'price_at_booking' => 150000,
            'schedule_start' => now()->addDay(),
            'schedule_end' => now()->addDay()->addHour(),
            'status' => 'pending_verification',
            'intake_form' => str_repeat('A valid intake form ', 10),
            'informed_consent' => true,
            'payment_deadline' => now()->addMinutes(15),
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'status' => 'rejected',
            'proof_file_path' => 'payments/old-proof.png',
        ]);

        Storage::disk('private')->put('payments/old-proof.png', 'old-file');

        $file = UploadedFile::fake()->image('proof.png');

        $response = $this->actingAs($this->client)
            ->post(route('client.bookings.payment.store', $booking), [
                'proof' => $file,
            ]);

        $response->assertSessionHasNoErrors();

        $booking->refresh();
        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();

        $this->assertSame('pending_verification', $booking->status);
        $this->assertSame('pending_verification', $payment->status);
        $this->assertNotNull($payment->proof_file_path);
        $this->assertNotSame('payments/old-proof.png', $payment->proof_file_path);

        $this->assertFalse(Storage::disk('private')->exists('payments/old-proof.png'));
        $this->assertTrue(Storage::disk('private')->exists($payment->proof_file_path));
    }

    public function test_approve_is_idempotent_for_already_approved_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $booking = Booking::create([
            'client_id' => $this->client->id,
            'counselor_id' => $this->counselor->id,
            'service_price_id' => $this->servicePrice->id,
            'service_type' => 'online',
            'duration_minutes' => 60,
            'price_at_booking' => 150000,
            'schedule_start' => now()->addDay(),
            'schedule_end' => now()->addDay()->addHour(),
            'status' => 'confirmed',
            'intake_form' => str_repeat('A valid intake form ', 10),
            'informed_consent' => true,
            'payment_deadline' => now()->addMinutes(15),
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'status' => 'approved',
            'verified_by' => $admin->id,
            'verified_at' => Carbon::now()->subMinutes(10),
        ]);

        $firstVerifiedAt = Payment::where('booking_id', $booking->id)->firstOrFail()->verified_at;

        $response = $this->actingAs($admin)
            ->post(route('admin.verifications.approve', $booking));

        $response->assertSessionHasNoErrors();

        $payment = $booking->payment()->firstOrFail();
        $this->assertSame('approved', $payment->status);
        $this->assertTrue($payment->verified_at->equalTo($firstVerifiedAt));
    }

    public function test_login_redirects_back_to_booking_flow(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $bookingUrl = '/client/bookings/create?service_price_id=1&counselor_id=2&schedule=2026-05-06%2010%3A00%3A00';

        $response = $this->get('/login?redirect=' . urlencode($bookingUrl));

        $response->assertOk();

        $loginResponse = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $loginResponse->assertRedirect($bookingUrl);
    }
}
