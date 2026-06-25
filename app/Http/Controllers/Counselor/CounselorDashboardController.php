<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CounselorDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get sessions happening today
        $todaySessions = $user->bookingsAsCounselor()
            ->with('client:id,name')
            ->whereDate('schedule_start', Carbon::today())
            ->whereIn('status', ['confirmed', 'in_session'])
            ->orderBy('schedule_start', 'asc')
            ->get()
            ->filter(function ($booking) {
                $end = \Carbon\Carbon::parse($booking->schedule_end);
                if ($booking->service_type === 'chat') {
                    $end->addHour();
                }
                return !now()->greaterThan($end);
            })
            ->values();

        // Get upcoming sessions for future dates (excluding today)
        $upcomingSessions = $user->bookingsAsCounselor()
            ->with('client:id,name')
            ->whereDate('schedule_start', '>', Carbon::today())
            ->where('status', 'confirmed')
            ->orderBy('schedule_start', 'asc')
            ->limit(10) // Limit to next 10 to keep dashboard clean
            ->get();

        $totalClients = $user->bookingsAsCounselor()
            ->distinct('client_id')
            ->count('client_id');

        return Inertia::render('Counselor/Dashboard', [
            'todaySessions' => $todaySessions,
            'upcomingSessions' => $upcomingSessions,
            'totalClients' => $totalClients,
        ]);
    }
}
