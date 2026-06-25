<?php

namespace App\Http\Controllers;

use App\Models\CounselorProfile;
use App\Models\ServicePrice;
use App\Services\SlotGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class PublicBookingController extends Controller
{
    public function index(Request $request)
    {
        $servicePrices = ServicePrice::where('is_active', true)->get();

        $counselors = Inertia::lazy(fn () =>
            CounselorProfile::visible()
                ->with('user:id,name')
                ->get()
        );

        $slots = Inertia::lazy(function () use ($request) {
            $servicePriceId = (int) $request->input('service_price_id');

            if ($servicePriceId <= 0) {
                return [];
            }

            return app(SlotGeneratorService::class)->generateForServicePrice($servicePriceId);
        });

        return Inertia::render('PublicBooking', [
            'servicePrices' => $servicePrices,
            'counselors' => $counselors,
            'slots' => $slots,
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }
}
