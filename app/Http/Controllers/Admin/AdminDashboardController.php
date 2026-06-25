<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_bookings' => Booking::count(),
            'active_bookings' => Booking::whereIn('status', ['confirmed', 'in_session'])->count(),
            'pending_payments' => Booking::activePendingPayment()->count(),
            'pending_verifications' => Payment::where('status', 'pending_verification')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
            'expired' => Booking::effectivelyExpired()->count(),
            'total_revenue' => Booking::where('status', 'completed')->sum('price_at_booking'),
            'total_counselors' => User::where('role', 'counselor')->count(),
            'total_clients' => User::where('role', 'client')->count(),
        ];

        // Upcoming sessions (next 24h)
        $upcomingSessions = Booking::with(['client:id,name', 'counselor:id,name'])
            ->whereIn('status', ['confirmed', 'in_session'])
            ->where('schedule_start', '<=', now()->addDay())
            ->where('schedule_end', '>=', now())
            ->orderBy('schedule_start', 'asc')
            ->limit(5)
            ->get();

        // Pending verifications
        $recentVerifications = Booking::with(['client:id,name', 'payment'])
            ->where('status', 'pending_verification')
            ->has('payment')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // Recent bookings
        $recentBookings = Booking::with(['client:id,name', 'counselor:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // --- Chart Data ---
        
        // 1. Bookings by Month (Last 6 Months)
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
        $recentCompletedBookings = Booking::where('schedule_start', '>=', $sixMonthsAgo)
            ->whereIn('status', ['completed', 'confirmed', 'in_session'])
            ->get();

        $bookingsByMonth = collect();
        for ($i = 5; $i >= 0; $i--) {
            $monthString = now()->subMonths($i)->format('Y-m');
            $monthData = $recentCompletedBookings->filter(fn($b) => $b->schedule_start->format('Y-m') === $monthString);
            
            $bookingsByMonth->push([
                'month' => now()->subMonths($i)->translatedFormat('M Y'),
                'total_bookings' => $monthData->count(),
                'total_revenue' => $monthData->sum('price_at_booking'),
            ]);
        }

        // 2. Bookings by Service Type
        $bookingsByServiceData = Booking::select('service_type', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->whereIn('status', ['completed', 'confirmed', 'in_session'])
            ->groupBy('service_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->service_type => $item->total];
            });
            
        // Ensure all types exist even if 0
        $bookingsByService = [
            'chat' => $bookingsByServiceData['chat'] ?? 0,
            'online' => $bookingsByServiceData['online'] ?? 0,
            'offline' => $bookingsByServiceData['offline'] ?? 0,
        ];

        // 3. Counselor Performance
        $counselorPerformance = User::where('role', 'counselor')
            ->withCount(['bookingsAsCounselor as total_sessions' => function ($query) {
                $query->whereIn('status', ['completed', 'confirmed', 'in_session']);
            }])
            ->orderByDesc('total_sessions')
            ->limit(10)
            ->get()
            ->map(function ($counselor) {
                return [
                    'name' => $counselor->name,
                    'total_sessions' => $counselor->total_sessions,
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'upcomingSessions' => $upcomingSessions,
            'recentVerifications' => $recentVerifications,
            'recentBookings' => $recentBookings,
            'chartData' => [
                'bookingsByMonth' => $bookingsByMonth,
                'bookingsByService' => $bookingsByService,
                'counselorPerformance' => $counselorPerformance,
            ]
        ]);
    }
}
