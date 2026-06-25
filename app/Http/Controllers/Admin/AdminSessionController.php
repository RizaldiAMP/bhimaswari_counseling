<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Exports\BookingsExport;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class AdminSessionController extends Controller
{
    /**
     * Monitoring: Sesi Aktif & Mendatang (24 jam)
     */
    public function index(Request $request): Response
    {
        $activeSessions = Booking::with(['client:id,name', 'counselor:id,name', 'servicePrice', 'chatSession:id,booking_id', 'payment'])
            ->where('service_type', 'chat')
            ->whereIn('status', ['in_session', 'confirmed'])
            ->where('schedule_start', '<=', now()->addDay())
            ->where('schedule_end', '>=', now())
            ->orderBy('schedule_start', 'asc')
            ->paginate(20, ['*'], 'active_page');

        return Inertia::render('Admin/Sessions', [
            'activeSessions' => $activeSessions,
        ]);
    }

    /**
     * Rekap: Semua sesi (history lengkap)
     */
    public function rekap(Request $request): Response
    {
        $query = Booking::with(['client:id,name', 'counselor:id,name', 'chatSession:id,booking_id', 'payment']);

        // Filter: keyword
        if ($q = $request->input('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('id', (int) $q)
                    ->orWhereHas('client', fn ($s) => $s->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('counselor', fn ($s) => $s->where('name', 'like', "%{$q}%"));
            });
        }

        // Filter: status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter: service_type
        if ($type = $request->input('service_type')) {
            $query->where('service_type', $type);
        }

        // Filter: date range
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('schedule_start', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('schedule_start', '<=', $dateTo);
        }

        $sessions = $query
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Summary stats
        $summary = [
            'total' => Booking::count(),
            'completed' => Booking::where('status', 'completed')->count(),
            'in_session' => Booking::where('status', 'in_session')->count(),
            'confirmed' => Booking::where('status', 'confirmed')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
            'expired' => Booking::effectivelyExpired()->count(),
            'pending_reschedule' => Booking::where('status', 'pending_reschedule')->count(),
        ];

        return Inertia::render('Admin/Rekap', [
            'sessions' => $sessions,
            'summary' => $summary,
            'filters' => [
                'q' => $request->input('q', ''),
                'status' => $request->input('status', ''),
                'service_type' => $request->input('service_type', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
            ],
        ]);
    }

    /**
     * Admin read-only chat view
     */
    public function showChat(Request $request, Booking $booking): Response|RedirectResponse
    {
        if ($booking->chatSession === null) {
            return redirect()->back()
                ->with('error', 'Chat session untuk booking ini belum tersedia.');
        }

        $booking->load([
            'client:id,name',
            'counselor:id,name',
            'chatSession.messages.sender:id,name',
        ]);

        return Inertia::render('Admin/Sessions/Chat', [
            'booking' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'schedule_start' => $booking->schedule_start,
                'schedule_end' => $booking->schedule_end,
                'client' => $booking->client,
                'counselor' => $booking->counselor,
            ],
            'messages' => $booking->chatSession->messages
                ->sortBy('created_at')
                ->values(),
        ]);
    }
    /**
     * Export bookings to Excel
     */
    public function export(Request $request)
    {
        $filename = 'rekap_sesi_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new BookingsExport($request), $filename);
    }
}
