<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;

/**
 * Delayed job: transisi booking confirmed → in_session saat schedule_start tiba.
 *
 * Di-dispatch saat booking menjadi confirmed (approve payment / reschedule approved).
 * Memvalidasi ulang status dan waktu sebelum update untuk mencegah race condition
 * dan menangani kasus reschedule yang mengubah jadwal.
 */
class AutoStartSession implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $booking_id,
    ) {
    }

    public function handle(): void
    {
        $booking = Booking::find($this->booking_id);

        if ($booking === null) {
            return;
        }

        // Hanya proses jika masih confirmed — jika sudah berubah status
        // (misal reschedule ulang, cancelled, dll), abaikan
        if ($booking->status !== 'confirmed') {
            return;
        }

        // Validasi waktu: schedule_start harus sudah tiba atau sudah lewat
        if (now()->lt($booking->schedule_start)) {
            return;
        }

        $booking->update(['status' => 'in_session']);
    }
}
