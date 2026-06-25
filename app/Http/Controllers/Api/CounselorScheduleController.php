<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CounselorProfile;
use App\Models\ServicePrice;
use App\Services\SlotGeneratorService;
use Illuminate\Http\JsonResponse;

class CounselorScheduleController extends Controller
{
    /**
     * Get all available slots for a counselor grouped by service_type.
     * Also returns applicable service prices based on practitioner_type.
     */
    public function show(int $counselorUserId): JsonResponse
    {
        $profile = CounselorProfile::where('user_id', $counselorUserId)
            ->where('is_visible', true)
            ->first();

        if (!$profile) {
            return response()->json(['slots' => [], 'prices' => []], 404);
        }

        $prices = ServicePrice::where('is_active', true)
            ->where('practitioner_type', $profile->practitioner_type)
            ->orderBy('service_type')
            ->orderBy('duration_minutes')
            ->get();

        $slotsByType = app(SlotGeneratorService::class)->generateForCounselor($counselorUserId);

        return response()->json([
            'slots' => $slotsByType,
            'prices' => $prices->values(),
        ]);
    }
}
