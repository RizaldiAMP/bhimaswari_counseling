<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $activeBookings = $user->bookingsAsClient()
            ->with('counselor.counselorProfile')
            ->where(function ($q) {
                $q->whereIn('status', ['pending_verification', 'confirmed', 'in_session'])
                  ->orWhere(function ($sub) {
                      $sub->where('status', 'pending_payment')
                          ->where(function ($inner) {
                              $inner->whereNull('payment_deadline')
                                    ->orWhere('payment_deadline', '>', now());
                          });
                  });
            })
            ->orderBy('schedule_start', 'asc')
            ->get()
            ->map(function ($booking) {
                $status = $booking->status;
                
                if (in_array($status, ['confirmed', 'in_session'])) {
                    $now = now();
                    $start = \Carbon\Carbon::parse($booking->schedule_start);
                    $end = \Carbon\Carbon::parse($booking->schedule_end);

                    if ($booking->service_type === 'chat') {
                        $end->addHour();
                    }

                    if ($now->lessThan($start)) {
                        $status = 'confirmed';
                    } elseif ($now->between($start, $end)) {
                        $status = 'in_session';
                    } else {
                        $status = 'completed';
                    }
                }

                return [
                    'id' => $booking->id,
                    'counselor' => [
                        'name' => $booking->counselor->name,
                        'full_title' => $booking->counselor->counselorProfile?->full_title ?? '',
                        'photo_path' => $booking->counselor->counselorProfile?->photo_path,
                        'photo_url' => $booking->counselor->counselorProfile?->photo_url,
                    ],
                    'service_type' => $booking->service_type,
                    'duration_minutes' => $booking->duration_minutes,
                    'schedule_start' => $booking->schedule_start,
                    'schedule_end' => $booking->schedule_end,
                    'status' => $status,
                    'meeting_link' => $booking->meeting_link,
                ];
            });

        // Find the next upcoming confirmed booking from the mapped collection
        $upcomingSchedule = $activeBookings->where('status', 'confirmed')->first();

        return Inertia::render('Client/Dashboard', [
            'activeBookings' => $activeBookings,
            'upcomingSchedule' => $upcomingSchedule,
        ]);
    }
}
