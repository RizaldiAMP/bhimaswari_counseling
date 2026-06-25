<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityRule;
use App\Models\AvailabilityException;
use App\Models\RecurringDayOff;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CounselorAvailabilityController extends Controller
{
    /**
     * Minimal gap between slots in minutes.
     */
    private const MIN_SLOT_GAP_MINUTES = 90;

    public function index(Request $request)
    {
        $user = $request->user();

        $rules = AvailabilityRule::where('counselor_id', $user->id)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $dayOffs = RecurringDayOff::where('counselor_id', $user->id)
            ->pluck('day_of_week')
            ->toArray();

        $exceptions = AvailabilityException::where('counselor_id', $user->id)
            ->where('exception_date', '>=', now()->toDateString())
            ->orderBy('exception_date')
            ->get();

        return Inertia::render('Counselor/Availability', [
            'rules' => $rules,
            'dayOffs' => $dayOffs,
            'exceptions' => $exceptions,
        ]);
    }

    public function storeRule(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
        ]);

        $userId = $request->user()->id;
        $newStartMinutes = $this->timeToMinutes($validated['start_time']);

        // Check duplicate: same day + same start_time
        $exists = AvailabilityRule::where('counselor_id', $userId)
            ->where('day_of_week', $validated['day_of_week'])
            ->whereRaw("SUBSTRING(start_time, 1, 5) = ?", [$validated['start_time']])
            ->where('is_active', true)
            ->exists();

        if ($exists) {
            return back()->withErrors(['start_time' => 'Slot jam ini sudah ada di hari tersebut.']);
        }

        // Check 90-minute minimum gap with existing slots on same day
        $existingSlots = AvailabilityRule::where('counselor_id', $userId)
            ->where('day_of_week', $validated['day_of_week'])
            ->where('is_active', true)
            ->get();

        foreach ($existingSlots as $slot) {
            $existingMinutes = $this->timeToMinutes(substr($slot->start_time, 0, 5));
            $gap = abs($newStartMinutes - $existingMinutes);

            if ($gap > 0 && $gap < self::MIN_SLOT_GAP_MINUTES) {
                return back()->withErrors([
                    'start_time' => "Jarak antar slot minimal " . self::MIN_SLOT_GAP_MINUTES . " menit. Slot " . substr($slot->start_time, 0, 5) . " terlalu dekat (jarak {$gap} menit)."
                ]);
            }
        }

        AvailabilityRule::create([
            'counselor_id' => $userId,
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function destroyRule(Request $request, AvailabilityRule $rule)
    {
        if ($rule->counselor_id !== $request->user()->id) {
            abort(403);
        }

        $rule->delete();

        return back()->with('success', 'Jadwal berhasil dihapus.');
    }

    /**
     * Toggle a day of week as recurring day off.
     */
    public function toggleDayOff(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
        ]);

        $userId = $request->user()->id;

        $existing = RecurringDayOff::where('counselor_id', $userId)
            ->where('day_of_week', $validated['day_of_week'])
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Hari libur rutin dihapus.');
        }

        RecurringDayOff::create([
            'counselor_id' => $userId,
            'day_of_week' => $validated['day_of_week'],
        ]);

        return back()->with('success', 'Hari libur rutin ditambahkan.');
    }

    public function storeException(Request $request)
    {
        $validated = $request->validate([
            'exception_date' => ['required', 'date', 'after_or_equal:today'],
            'type' => ['required', 'in:blocked,added'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $userId = $request->user()->id;
        $startTime = $validated['start_time'] ?? null;

        // For blocked full-day, start_time is null
        if ($validated['type'] === 'blocked' && !$startTime) {
            $startTime = null;
        }

        // For 'added' type, validate 90-minute gap with existing slots on that date
        if ($validated['type'] === 'added' && $startTime) {
            $newStartMinutes = $this->timeToMinutes($startTime);
            $date = Carbon::parse($validated['exception_date']);
            $dayOfWeek = $date->dayOfWeekIso - 1;

            // Check against weekly rules for that day
            $weeklySlots = AvailabilityRule::where('counselor_id', $userId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->get();

            foreach ($weeklySlots as $slot) {
                $existingMinutes = $this->timeToMinutes(substr($slot->start_time, 0, 5));
                $gap = abs($newStartMinutes - $existingMinutes);

                if ($gap > 0 && $gap < self::MIN_SLOT_GAP_MINUTES) {
                    return back()->withErrors([
                        'start_time' => "Jarak antar slot minimal " . self::MIN_SLOT_GAP_MINUTES . " menit. Slot rutin " . substr($slot->start_time, 0, 5) . " terlalu dekat."
                    ]);
                }
            }

            // Check against other 'added' exceptions on the same date
            $existingAdded = AvailabilityException::where('counselor_id', $userId)
                ->where('exception_date', $validated['exception_date'])
                ->where('type', 'added')
                ->whereNotNull('start_time')
                ->get();

            foreach ($existingAdded as $exc) {
                $existingMinutes = $this->timeToMinutes(substr($exc->start_time, 0, 5));
                $gap = abs($newStartMinutes - $existingMinutes);

                if ($gap > 0 && $gap < self::MIN_SLOT_GAP_MINUTES) {
                    return back()->withErrors([
                        'start_time' => "Jarak antar slot minimal " . self::MIN_SLOT_GAP_MINUTES . " menit. Slot khusus " . substr($exc->start_time, 0, 5) . " terlalu dekat."
                    ]);
                }
            }
        }

        AvailabilityException::create([
            'counselor_id' => $userId,
            'exception_date' => $validated['exception_date'],
            'type' => $validated['type'],
            'start_time' => $startTime,
            'reason' => $validated['reason'] ?? null,
        ]);

        return back()->with('success', $validated['type'] === 'blocked' ? 'Cuti berhasil ditambahkan.' : 'Jadwal tanggal khusus berhasil ditambahkan.');
    }

    public function destroyException(Request $request, AvailabilityException $exception)
    {
        if ($exception->counselor_id !== $request->user()->id) {
            abort(403);
        }

        $exception->delete();

        return back()->with('success', 'Jadwal khusus berhasil dihapus.');
    }

    /**
     * Convert HH:mm to total minutes since midnight.
     */
    private function timeToMinutes(string $time): int
    {
        [$h, $m] = explode(':', $time);
        return ((int) $h * 60) + (int) $m;
    }
}
