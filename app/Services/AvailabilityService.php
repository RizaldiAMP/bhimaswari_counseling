<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use Carbon\Carbon;

class AvailabilityService
{
    public function isCounselorAvailableForRange(int $counselorId, Carbon $start, Carbon $end): bool
    {
        $dayOfWeek = $this->mapCarbonDayToRuleDay($start);
        $startTime = $start->format('H:i:s');
        $date = $start->toDateString();

        $hasRuleCoverage = AvailabilityRule::query()
            ->where('counselor_id', $counselorId)
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', $startTime)
            ->exists();

        $hasAddedExceptionCoverage = AvailabilityException::query()
            ->where('counselor_id', $counselorId)
            ->whereDate('exception_date', $date)
            ->where('type', 'added')
            ->where('start_time', $startTime)
            ->exists();

        if (! $hasRuleCoverage && ! $hasAddedExceptionCoverage) {
            return false;
        }

        $blockedWholeDay = AvailabilityException::query()
            ->where('counselor_id', $counselorId)
            ->whereDate('exception_date', $date)
            ->where('type', 'blocked')
            ->whereNull('start_time')
            ->exists();

        if ($blockedWholeDay) {
            return false;
        }

        $blockedSlot = AvailabilityException::query()
            ->where('counselor_id', $counselorId)
            ->whereDate('exception_date', $date)
            ->where('type', 'blocked')
            ->where('start_time', $startTime)
            ->exists();

        return ! $blockedSlot;
    }

    private function mapCarbonDayToRuleDay(Carbon $date): int
    {
        return $date->dayOfWeekIso - 1;
    }
}
