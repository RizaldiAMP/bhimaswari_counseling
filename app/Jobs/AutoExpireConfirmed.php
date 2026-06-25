<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AutoExpireConfirmed implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Booking yang sudah confirmed tapi jadwal sudah lewat (schedule_end sudah lewat)
        // dan belum pernah ada yang masuk chat → expire.
        // Booking yang sudah punya ChatSession tidak boleh di-expire karena
        // artinya salah satu pihak sudah masuk (akan ditangani AutoCompleteSession).
        Booking::where('status', 'confirmed')
            ->where('schedule_end', '<', now())
            ->whereDoesntHave('chatSession')
            ->update(['status' => 'expired']);
    }
}
