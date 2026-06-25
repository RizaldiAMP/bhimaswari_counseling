<?php

namespace App\Jobs;

use App\Services\BookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireUnpaidBookings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $booking_id)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(BookingService $bookingService): void
    {
        $booking = \App\Models\Booking::find($this->booking_id);

        if ($booking) {
            $bookingService->expireBooking($booking);
        }
    }
}

