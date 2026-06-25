<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\InAppNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class SendSessionReminderNotifications implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $startWindow = Carbon::now()->addDay();
        $endWindow = Carbon::now()->addDay()->addHour();

        $bookings = Booking::query()
            ->with(['client:id,name', 'counselor:id,name'])
            ->where('status', 'confirmed')
            ->whereBetween('schedule_start', [$startWindow, $endWindow])
            ->get();

        foreach ($bookings as $booking) {
            $cacheKey = "booking:{$booking->id}:h1-reminder";

            if (! Cache::add($cacheKey, true, now()->addDays(2))) {
                continue;
            }

            $sessionTime = $booking->schedule_start->translatedFormat('d M Y, H:i');

            $booking->client->notify(new InAppNotification(
                event: 'session_reminder_h1',
                title: 'Pengingat sesi H-1',
                message: "Sesi Anda dijadwalkan besok ({$sessionTime} WIB).",
                url: route('client.bookings.show', ['booking' => $booking->id], false),
                meta: ['booking_id' => $booking->id],
            ));

            if ($booking->counselor !== null) {
                $booking->counselor->notify(new InAppNotification(
                    event: 'session_reminder_h1',
                    title: 'Pengingat sesi H-1',
                    message: "Sesi dengan {$booking->client->name} dijadwalkan besok ({$sessionTime} WIB).",
                    url: route('counselor.bookings.show', ['booking' => $booking->id], false),
                    meta: ['booking_id' => $booking->id],
                ));
            }
        }
    }
}
