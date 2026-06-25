<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CounselorSessionController extends Controller
{
    public function online(Request $request): Response
    {
        $bookings = Booking::with(['client', 'servicePrice'])
            ->where('counselor_id', $request->user()->id)
            ->where('service_type', 'online')
            ->orderByRaw("FIELD(status, 'in_session', 'confirmed', 'completed', 'pending_payment', 'pending_verification', 'rejected', 'cancelled', 'expired')")
            ->orderBy('schedule_start', 'desc')
            ->get();

        return Inertia::render('Counselor/Sessions/Online', [
            'bookings' => $bookings,
        ]);
    }

    public function offline(Request $request): Response
    {
        $bookings = Booking::with(['client', 'servicePrice'])
            ->where('counselor_id', $request->user()->id)
            ->where('service_type', 'offline')
            ->orderByRaw("FIELD(status, 'in_session', 'confirmed', 'completed', 'pending_payment', 'pending_verification', 'rejected', 'cancelled', 'expired')")
            ->orderBy('schedule_start', 'desc')
            ->get();

        return Inertia::render('Counselor/Sessions/Offline', [
            'bookings' => $bookings,
        ]);
    }
}
