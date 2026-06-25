<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\CounselorProfile;
use App\Models\RecurringDayOff;
use App\Models\ServicePrice;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SlotGeneratorService
{
    /**
     * Generate available slots for multiple counselors matching a given service price.
     * Returns: [ counselorUserId => [ dayArray, ... ], ... ]
     *
     * Slot logic:
     *  - AvailabilityRule stores specific start_times (not ranges)
     *  - All service types share the same availability
     *  - Bookings of ANY service type block the counselor's time
     *  - Duration of the selected service determines how long a slot blocks
     */
    public function generateForServicePrice(int $servicePriceId): array
    {
        $servicePrice = ServicePrice::where('id', $servicePriceId)
            ->where('is_active', true)
            ->first();

        if ($servicePrice === null) {
            return [];
        }

        $counselors = CounselorProfile::where('is_visible', true)
            ->where('practitioner_type', $servicePrice->practitioner_type)
            ->get(['id', 'user_id']);

        $counselorUserIds = $counselors->pluck('user_id')->toArray();
        if (empty($counselorUserIds)) {
            return [];
        }

        $durationMinutes = (int) $servicePrice->duration_minutes;

        return $this->generateSlots($counselorUserIds, $durationMinutes);
    }

    /**
     * Generate available slots for a single counselor across all service types.
     * Returns: [ serviceType => [ dayArray, ... ], ... ]
     * Used by the team/counselor profile page API.
     */
    public function generateForCounselor(int $counselorUserId): array
    {
        $profile = CounselorProfile::where('user_id', $counselorUserId)
            ->where('is_visible', true)
            ->first();

        if (!$profile) {
            return [];
        }

        $prices = ServicePrice::where('is_active', true)
            ->where('practitioner_type', $profile->practitioner_type)
            ->orderBy('service_type')
            ->orderBy('duration_minutes')
            ->get();

        // Group by service_type, use shortest duration for max slots
        $slotsByType = [];
        $serviceTypes = $prices->pluck('service_type')->unique();

        foreach ($serviceTypes as $serviceType) {
            $minDuration = $prices->where('service_type', $serviceType)->min('duration_minutes') ?: 60;
            $slots = $this->generateSlots([$counselorUserId], $minDuration);
            if (!empty($slots[$counselorUserId])) {
                $slotsByType[$serviceType] = $slots[$counselorUserId];
            }
        }

        return $slotsByType;
    }

    /**
     * Core slot generation for given counselor user IDs and a session duration.
     * Returns: [ counselorUserId => [ dayArray, ... ], ... ]
     */
    public function generateSlots(array $counselorUserIds, int $durationMinutes, int $windowDays = 14): array
    {
        // Ubah batas minimal pemesanan menjadi besok (H+1) agar tidak ada pemesanan mendadak hari ini
        $windowStart = now()->addDay()->startOfDay();
        $windowEnd = now()->copy()->addDays($windowDays)->endOfDay();

        // Bulk fetch all relevant data
        $allRules = AvailabilityRule::query()
            ->whereIn('counselor_id', $counselorUserIds)
            ->where('is_active', true)
            ->get()
            ->groupBy('counselor_id');

        $allDayOffs = RecurringDayOff::query()
            ->whereIn('counselor_id', $counselorUserIds)
            ->get()
            ->groupBy('counselor_id');

        $allExceptions = AvailabilityException::query()
            ->whereIn('counselor_id', $counselorUserIds)
            ->whereBetween('exception_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
            ->get()
            ->groupBy('counselor_id');

        // Bookings across ALL service types block the counselor
        // Exclude expired, cancelled, pending_reschedule, and pending_payment past deadline
        $allBooked = Booking::query()
            ->whereIn('counselor_id', $counselorUserIds)
            ->where('schedule_start', '>=', now())
            ->whereNotIn('status', ['expired', 'cancelled', 'pending_reschedule'])
            ->where(function ($query) {
                // Exclude pending_payment yang sudah lewat deadline (booking gagal)
                $query->where('status', '!=', 'pending_payment')
                      ->orWhere(function ($q) {
                          $q->where('status', 'pending_payment')
                            ->where(function ($inner) {
                                $inner->whereNull('payment_deadline')
                                      ->orWhere('payment_deadline', '>=', now());
                            });
                      });
            })
            ->get(['counselor_id', 'schedule_start', 'schedule_end'])
            ->groupBy('counselor_id');

        $allSlots = [];

        foreach ($counselorUserIds as $cId) {
            $rules = $allRules->get($cId) ?? collect();
            $exceptions = $allExceptions->get($cId) ?? collect();
            $booked = $allBooked->get($cId) ?? collect();
            $dayOffDays = ($allDayOffs->get($cId) ?? collect())->pluck('day_of_week')->toArray();

            $days = [];

            foreach (CarbonPeriod::create($windowStart, '1 day', $windowEnd) as $day) {
                $day = Carbon::parse($day)->startOfDay();
                $dateString = $day->toDateString();
                $dayOfWeek = $day->dayOfWeekIso - 1; // 0=Monday

                // Skip recurring day offs
                if (in_array($dayOfWeek, $dayOffDays, true)) {
                    continue;
                }

                $dayExceptions = $exceptions->where('exception_date', $dateString);

                // Skip if full-day block exists
                $blockedFullDay = $dayExceptions
                    ->where('type', 'blocked')
                    ->contains(fn ($e) => $e->start_time === null);

                if ($blockedFullDay) {
                    continue;
                }

                // Collect slot start times from weekly rules
                $slotTimes = [];
                foreach ($rules->where('day_of_week', $dayOfWeek) as $rule) {
                    $slotTimes[] = $rule->start_time;
                }

                // Add slot times from 'added' exceptions
                foreach ($dayExceptions->where('type', 'added') as $exception) {
                    if ($exception->start_time !== null) {
                        $slotTimes[] = $exception->start_time;
                    }
                }

                // Remove slot times blocked by specific exceptions
                $blockedSlotTimes = $dayExceptions
                    ->where('type', 'blocked')
                    ->whereNotNull('start_time')
                    ->pluck('start_time')
                    ->map(fn ($t) => substr($t, 0, 5))
                    ->toArray();

                $slotTimes = array_filter($slotTimes, function ($t) use ($blockedSlotTimes) {
                    return !in_array(substr($t, 0, 5), $blockedSlotTimes, true);
                });

                // Deduplicate and sort
                $slotTimes = array_unique(array_map(fn ($t) => substr($t, 0, 5), $slotTimes));
                sort($slotTimes);

                if (empty($slotTimes)) {
                    continue;
                }

                $times = [];

                foreach ($slotTimes as $timeStr) {
                    $slotStart = Carbon::parse($dateString . ' ' . $timeStr);
                    $slotEnd = $slotStart->copy()->addMinutes($durationMinutes);

                    // Skip past slots
                    if ($slotStart->lte(now())) {
                        continue;
                    }

                    // Check booking overlap (ALL service types)
                    $isBooked = $booked->contains(
                        fn ($booking) => $booking->schedule_start->lt($slotEnd) && $booking->schedule_end->gt($slotStart)
                    );

                    if (!$isBooked) {
                        $key = $slotStart->format('Y-m-d H:i:s');
                        $times[$key] = [
                            'value' => $key,
                            'label' => $slotStart->format('H:i'),
                        ];
                    }
                }

                if (count($times) === 0) {
                    continue;
                }

                ksort($times);
                $days[] = [
                    'date' => $dateString,
                    'label' => $day->translatedFormat('l, d M Y'),
                    'dayStr' => $day->translatedFormat('d M'),
                    'dayName' => $day->translatedFormat('l'),
                    'times' => array_values($times),
                ];
            }

            if (!empty($days)) {
                $allSlots[$cId] = $days;
            }
        }

        return $allSlots;
    }
}
