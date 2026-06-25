<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Klien hanya bisa upload bukti jika booking miliknya dan masih valid.
     */
    public function uploadProof(User $user, Booking $booking): bool
    {
        return $user->id === $booking->client_id
            && in_array($booking->status, ['pending_payment', 'pending_verification'], true)
            && ($booking->payment_deadline === null || $booking->payment_deadline->isFuture());
    }

    /**
     * Klien hanya bisa reschedule booking confirmed miliknya.
     */
    public function reschedule(User $user, Booking $booking): bool
    {
        return $user->id === $booking->client_id
            && $booking->status === 'confirmed'
            && $booking->reschedule_count < 1
            && $booking->schedule_start->subDay()->endOfDay()->isFuture(); // H-1
    }

    /**
     * Klien atau konselor terkait bisa melihat booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->client_id
            || $user->id === $booking->counselor_id
            || $user->isAdmin();
    }

    /**
     * Admin bisa approve/reject payment.
     */
    public function verifyPayment(User $user, Booking $booking): bool
    {
        return $user->isAdmin();
    }
}
