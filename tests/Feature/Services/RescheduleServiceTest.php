<?php

namespace Tests\Feature\Services;

use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Reschedule;
use App\Models\ServicePrice;
use App\Models\User;
use App\Services\RescheduleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RescheduleServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_client_can_request_reschedule_and_booking_becomes_pending_reschedule(): void
    {
        [$client, $counselor, $booking] = $this->createConfirmedBooking();

        $service = app(RescheduleService::class);
        $newStart = Carbon::parse($booking->schedule_start)->addDay()->setTime(10, 0, 0);

        $reschedule = $service->requestByClient($client, $booking, [
            'new_schedule_start' => $newStart->toDateTimeString(),
            'reason' => 'Ada agenda mendadak',
        ]);

        $this->assertDatabaseHas('reschedules', [
            'id' => $reschedule->id,
            'booking_id' => $booking->id,
            'status' => 'pending',
            'requested_by' => $client->id,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'pending_reschedule',
        ]);
    }

    public function test_admin_can_approve_reschedule_and_booking_schedule_is_updated(): void
    {
        [$client, $counselor, $booking] = $this->createConfirmedBooking();
        $admin = User::factory()->create(['role' => 'admin', 'whatsapp' => '+628133333333']);

        $pendingReschedule = Reschedule::create([
            'booking_id' => $booking->id,
            'requested_by' => $client->id,
            'old_schedule_start' => $booking->schedule_start,
            'old_schedule_end' => $booking->schedule_end,
            'new_schedule_start' => Carbon::parse($booking->schedule_start)->addDay()->setTime(10, 0, 0),
            'new_schedule_end' => Carbon::parse($booking->schedule_end)->addDay()->setTime(11, 0, 0),
            'reason' => 'Perlu ubah jadwal',
            'status' => 'pending',
        ]);

        $booking->update(['status' => 'pending_reschedule']);

        $service = app(RescheduleService::class);
        $newStart = Carbon::parse($booking->schedule_start)->addDay()->setTime(11, 0, 0);

        $service->approveByAdmin($admin, $pendingReschedule, [
            'new_schedule_start' => $newStart->toDateTimeString(),
            'admin_notes' => 'Disetujui',
        ]);

        $this->assertDatabaseHas('reschedules', [
            'id' => $pendingReschedule->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
            'reschedule_count' => 1,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'reschedule_approved',
            'auditable_id' => $booking->id,
        ]);
    }

    public function test_reject_non_pending_reschedule_throws_validation_exception(): void
    {
        [$client, $counselor, $booking] = $this->createConfirmedBooking();
        $admin = User::factory()->create(['role' => 'admin', 'whatsapp' => '+628144444444']);

        $reschedule = Reschedule::create([
            'booking_id' => $booking->id,
            'requested_by' => $client->id,
            'old_schedule_start' => $booking->schedule_start,
            'old_schedule_end' => $booking->schedule_end,
            'new_schedule_start' => Carbon::parse($booking->schedule_start)->addDay(),
            'new_schedule_end' => Carbon::parse($booking->schedule_end)->addDay(),
            'reason' => 'Perlu ubah jadwal',
            'status' => 'approved',
        ]);

        $this->expectException(ValidationException::class);

        app(RescheduleService::class)->rejectByAdmin($admin, $reschedule, 'Tidak bisa diproses ulang');
    }

    private function createConfirmedBooking(): array
    {
        $client = User::factory()->create(['role' => 'client', 'whatsapp' => '+628100000001']);
        $counselor = User::factory()->create(['role' => 'counselor', 'whatsapp' => '+628100000002']);

        // Monday at 09:00:00 for the original booking
        AvailabilityRule::create([
            'counselor_id' => $counselor->id,
            'day_of_week' => 0, // Monday is 0
            'start_time' => '09:00:00',
            'is_active' => true,
        ]);

        // Tuesday at 09:00:00
        AvailabilityRule::create([
            'counselor_id' => $counselor->id,
            'day_of_week' => 1, // Tuesday is 1
            'start_time' => '09:00:00',
            'is_active' => true,
        ]);

        // Tuesday at 10:00:00
        AvailabilityRule::create([
            'counselor_id' => $counselor->id,
            'day_of_week' => 1, // Tuesday is 1
            'start_time' => '10:00:00',
            'is_active' => true,
        ]);

        // Tuesday at 11:00:00
        AvailabilityRule::create([
            'counselor_id' => $counselor->id,
            'day_of_week' => 1, // Tuesday is 1
            'start_time' => '11:00:00',
            'is_active' => true,
        ]);

        $servicePrice = ServicePrice::create([
            'service_type' => 'online',
            'practitioner_type' => 'counselor',
            'duration_minutes' => 60,
            'price' => 200000,
            'is_active' => true,
        ]);

        $start = Carbon::now()->next('Monday')->setTime(9, 0, 0);

        $booking = Booking::create([
            'client_id' => $client->id,
            'counselor_id' => $counselor->id,
            'service_price_id' => $servicePrice->id,
            'service_type' => 'online',
            'duration_minutes' => 60,
            'price_at_booking' => 200000,
            'schedule_start' => $start,
            'schedule_end' => $start->copy()->addHour(),
            'status' => 'confirmed',
            'intake_form' => str_repeat('Kondisi klien dan tujuan konseling. ', 5),
            'informed_consent' => true,
            'reschedule_count' => 0,
            'payment_deadline' => now()->addMinutes(15),
        ]);

        return [$client, $counselor, $booking];
    }
}
