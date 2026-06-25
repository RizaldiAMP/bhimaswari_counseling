<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CounselorManagementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminCounselorController extends Controller
{
    public function index()
    {
        $counselors = User::where('role', 'counselor')
            ->with('counselorProfile')
            ->withCount(['bookingsAsCounselor as active_bookings_count' => function ($query) {
                $query->activeBookings();
            }])
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('Admin/Counselors', [
            'counselors' => $counselors,
        ]);
    }

    public function store(Request $request, CounselorManagementService $counselorManagementService)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Rules\Password::defaults()],
            'practitioner_type' => ['required', 'in:psychologist,counselor'],
            'sipp_number' => ['nullable', 'string', 'max:100'],
        ]);

        $counselorManagementService->createCounselor($validated);

        return back()->with('success', 'Akun konselor berhasil dibuat.');
    }

    public function update(Request $request, User $counselor, CounselorManagementService $counselorManagementService)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($counselor->id)],
            'practitioner_type' => ['required', 'in:psychologist,counselor'],
            'sipp_number' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ]);

        $counselorManagementService->updateCounselor($counselor, $validated, $request->user()->id);

        return back()->with('success', 'Data konselor berhasil diperbarui.');
    }

    public function toggleActive(Request $request, User $counselor, CounselorManagementService $counselorManagementService)
    {
        try {
            $counselorManagementService->toggleActive($counselor, $request->user()->id);

            $status = $counselor->fresh()->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return back()->with('success', "Konselor berhasil {$status}.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    public function destroy(User $counselor, CounselorManagementService $counselorManagementService)
    {
        try {
            $counselorManagementService->deleteCounselor($counselor);
            return back()->with('success', 'Akun konselor berhasil dihapus.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }
}
