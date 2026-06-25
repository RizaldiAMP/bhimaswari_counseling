<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AutoRescheduleOverdue implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // PRD 4.2: Jika jadwal sesi lewat dan belum diverifikasi → pending_reschedule
        Booking::where('status', 'pending_verification')
            ->where('schedule_start', '<', now())
            ->update(['status' => 'pending_reschedule']);
    }
}
