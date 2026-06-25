<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\AutoStartSession;
use App\Models\Booking;
use App\Models\Reschedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RescheduleService
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly AuditService $auditService,
    ) {
    }

    public function requestByClient(User $client, Booking $booking, array $data): Reschedule
    {
        if ($booking->status !== 'confirmed') {
            throw ValidationException::withMessages([
                'new_schedule_start' => 'Booking ini tidak dapat diajukan reschedule.',
            ]);
        }

        if ($booking->reschedule_count >= 1) {
            throw ValidationException::withMessages([
                'new_schedule_start' => 'Kuota reschedule reguler sudah habis (maksimal 1x).',
            ]);
        }

        if (! $booking->schedule_start->copy()->subDay()->endOfDay()->isFuture()) {
            throw ValidationException::withMessages([
                'new_schedule_start' => 'Reschedule reguler hanya bisa diajukan minimal H-1.',
            ]);
        }

        return DB::transaction(function () use ($client, $booking, $data) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $hasPending = Reschedule::where('booking_id', $lockedBooking->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPending) {
                throw ValidationException::withMessages([
                    'new_schedule_start' => 'Masih ada pengajuan reschedule yang belum diproses.',
                ]);
            }

            $newStart = Carbon::parse($data['new_schedule_start']);
            $newEnd = $newStart->copy()->addMinutes($lockedBooking->duration_minutes);

            if (! $this->availabilityService->isCounselorAvailableForRange($lockedBooking->counselor_id, $newStart, $newEnd)) {
                throw ValidationException::withMessages([
                    'new_schedule_start' => 'Jadwal yang dipilih berada di luar ketersediaan konselor.',
                ]);
            }

            $hasConflict = Booking::query()
                ->where('counselor_id', $lockedBooking->counselor_id)
                ->where('id', '!=', $lockedBooking->id)
                ->whereNotIn('status', ['expired', 'cancelled'])
                ->where(function ($query) {
                    $query->where('status', '!=', 'pending_payment')
                          ->orWhere('payment_deadline', '>', now());
                })
                ->where('schedule_start', '<', $newEnd)
                ->where('schedule_end', '>', $newStart)
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'new_schedule_start' => 'Slot jadwal yang dipilih sudah terisi. Silakan pilih slot lain.',
                ]);
            }

            $reschedule = Reschedule::create([
                'booking_id' => $lockedBooking->id,
                'requested_by' => $client->id,
                'old_schedule_start' => $lockedBooking->schedule_start,
                'old_schedule_end' => $lockedBooking->schedule_end,
                'new_schedule_start' => $newStart,
                'new_schedule_end' => $newEnd,
                'reason' => $data['reason'],
                'status' => 'pending',
            ]);

            $lockedBooking->update(['status' => 'pending_reschedule']);

            return $reschedule;
        });
    }

    public function approveByAdmin(User $admin, Reschedule $reschedule, array $data): void
    {
        DB::transaction(function () use ($admin, $reschedule, $data) {
            $lockedReschedule = Reschedule::with('booking')->lockForUpdate()->findOrFail($reschedule->id);

            if ($lockedReschedule->status !== 'pending') {
                throw ValidationException::withMessages([
                    'new_schedule_start' => 'Permintaan reschedule ini sudah diproses sebelumnya.',
                ]);
            }

            $booking = Booking::lockForUpdate()->findOrFail($lockedReschedule->booking_id);
            $newStart = Carbon::parse($data['new_schedule_start']);
            $newEnd = $newStart->copy()->addMinutes($booking->duration_minutes);

            if (! $this->availabilityService->isCounselorAvailableForRange($booking->counselor_id, $newStart, $newEnd)) {
                throw ValidationException::withMessages([
                    'new_schedule_start' => 'Jadwal pengganti berada di luar ketersediaan konselor.',
                ]);
            }

            $hasConflict = Booking::query()
                ->where('counselor_id', $booking->counselor_id)
                ->where('id', '!=', $booking->id)
                ->whereNotIn('status', ['expired', 'cancelled'])
                ->where(function ($query) {
                    $query->where('status', '!=', 'pending_payment')
                          ->orWhere('payment_deadline', '>', now());
                })
                ->where('schedule_start', '<', $newEnd)
                ->where('schedule_end', '>', $newStart)
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'new_schedule_start' => 'Slot pengganti bentrok dengan booking lain.',
                ]);
            }

            $lockedReschedule->update([
                'new_schedule_start' => $newStart,
                'new_schedule_end' => $newEnd,
                'status' => 'approved',
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);

            $incrementRescheduleCount = $lockedReschedule->requested_by === $booking->client_id;

            $booking->update([
                'schedule_start' => $newStart,
                'schedule_end' => $newEnd,
                'status' => 'confirmed',
                'reschedule_count' => $incrementRescheduleCount
                    ? $booking->reschedule_count + 1
                    : $booking->reschedule_count,
            ]);

            $this->auditService->log(
                userId: $admin->id,
                action: 'reschedule_approved',
                auditable: $booking,
                oldValues: [
                    'schedule_start' => (string) $lockedReschedule->old_schedule_start,
                    'schedule_end' => (string) $lockedReschedule->old_schedule_end,
                    'status' => 'pending_reschedule',
                ],
                newValues: [
                    'schedule_start' => (string) $newStart,
                    'schedule_end' => (string) $newEnd,
                    'status' => 'confirmed',
                    'reschedule_count' => $booking->fresh()->reschedule_count,
                ],
            );

            // Dispatch delayed job: auto-start session saat jadwal baru tiba
            $delay = $newStart->isFuture() ? $newStart : now();
            AutoStartSession::dispatch($booking->id)->delay($delay);
        });
    }

    public function rejectByAdmin(User $admin, Reschedule $reschedule, string $adminNotes): void
    {
        DB::transaction(function () use ($admin, $reschedule, $adminNotes) {
            $lockedReschedule = Reschedule::with('booking')->lockForUpdate()->findOrFail($reschedule->id);

            if ($lockedReschedule->status !== 'pending') {
                throw ValidationException::withMessages([
                    'admin_notes' => 'Permintaan reschedule ini sudah diproses sebelumnya.',
                ]);
            }

            $lockedReschedule->update([
                'status' => 'rejected',
                'admin_notes' => $adminNotes,
            ]);

            if ($lockedReschedule->booking->status === 'pending_reschedule') {
                $lockedReschedule->booking->update(['status' => 'confirmed']);

                // Dispatch delayed job: booking kembali confirmed dengan jadwal semula
                $scheduleStart = $lockedReschedule->booking->schedule_start;
                $delay = $scheduleStart->isFuture() ? $scheduleStart : now();
                AutoStartSession::dispatch($lockedReschedule->booking->id)->delay($delay);
            }

            $this->auditService->log(
                userId: $admin->id,
                action: 'reschedule_rejected',
                auditable: $lockedReschedule->booking,
                oldValues: ['reschedule_status' => 'pending'],
                newValues: [
                    'reschedule_status' => 'rejected',
                    'status' => 'confirmed',
                    'reason' => $adminNotes,
                ],
            );
        });
    }
}
