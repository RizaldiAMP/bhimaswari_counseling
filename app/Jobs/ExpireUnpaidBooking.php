<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireUnpaidBooking implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $bookingId
    ) {}

    public function handle(BookingService $bookingService): void
    {
        $booking = Booking::find($this->bookingId);

        if (! $booking) {
            return;
        }

        $bookingService->expireBooking($booking);
    }
}

