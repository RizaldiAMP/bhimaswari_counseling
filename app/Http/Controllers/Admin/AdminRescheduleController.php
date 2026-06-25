<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reschedule;
use App\Services\RescheduleService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminRescheduleController extends Controller
{
    public function index()
    {
        $pendingReschedules = Reschedule::with([
                'booking.client:id,name,email',
                'booking.counselor:id,name',
                'booking.servicePrice:id,service_type,duration_minutes,price',
                'requester:id,name',
            ])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return Inertia::render('Admin/Reschedules', [
            'pendingReschedules' => $pendingReschedules,
        ]);
    }

    public function approve(Request $request, Reschedule $reschedule, RescheduleService $rescheduleService)
    {
        $validated = $request->validate([
            'new_schedule_start' => ['required', 'date', 'after:now'],
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $rescheduleService->approveByAdmin($request->user(), $reschedule, $validated);

        return back()->with('success', 'Reschedule berhasil disetujui dan jadwal booking diperbarui.');
    }

    public function reject(Request $request, Reschedule $reschedule, RescheduleService $rescheduleService)
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:500'],
        ]);

        $rescheduleService->rejectByAdmin($request->user(), $reschedule, $validated['admin_notes']);

        return back()->with('success', 'Pengajuan reschedule ditolak.');
    }
}
