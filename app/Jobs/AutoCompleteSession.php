<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Booking;
use App\Models\ChatSession;
use App\Notifications\InAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AutoCompleteSession implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Auto-complete sessions where timer has been running for 60+ minutes
        $timerExpiredSessions = ChatSession::whereNotNull('timer_started_at')
            ->whereNull('ended_at')
            ->where('timer_started_at', '<', now()->subMinutes(60))
            ->with('booking.client', 'booking.counselor')
            ->get();

        foreach ($timerExpiredSessions as $session) {
            if ($session->booking && $session->booking->status === 'in_session') {
                $session->booking->update(['status' => 'completed']);
                $session->update([
                    'ended_at' => now(),
                    'ended_by' => 'system',
                ]);

                $this->notifyAutoComplete($session->booking);
            }
        }

        // Fallback: Also auto-complete if schedule_end + 60m has passed (no timer started)
        $overdueBookings = Booking::with('chatSession', 'client', 'counselor')
            ->where('status', 'in_session')
            ->where('schedule_end', '<', now()->subMinutes(60))
            ->get();

        foreach ($overdueBookings as $booking) {
            $booking->update(['status' => 'completed']);

            if ($booking->chatSession) {
                $booking->chatSession->update([
                    'ended_at' => now(),
                    'ended_by' => 'system',
                ]);
            }

            $this->notifyAutoComplete($booking);
        }
    }

    /**
     * Kirim notifikasi ke client dan counselor saat sesi di-auto-complete oleh sistem.
     */
    private function notifyAutoComplete(Booking $booking): void
    {
        $message = "Sesi konseling #{$booking->id} telah diselesaikan otomatis oleh sistem karena waktu sudah habis.";

        if ($booking->client) {
            $booking->client->notify(new InAppNotification(
                event: 'session_auto_completed',
                title: 'Sesi selesai otomatis',
                message: $message,
                url: route('client.bookings.show', ['booking' => $booking->id], false),
                meta: ['booking_id' => $booking->id],
            ));
        }

        if ($booking->counselor) {
            $booking->counselor->notify(new InAppNotification(
                event: 'session_auto_completed',
                title: 'Sesi selesai otomatis',
                message: $message,
                url: route('counselor.bookings.show', ['booking' => $booking->id], false),
                meta: ['booking_id' => $booking->id],
            ));
        }
    }
}

