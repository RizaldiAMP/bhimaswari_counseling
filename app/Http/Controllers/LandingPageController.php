<?php

namespace App\Http\Controllers;

use App\Models\CounselorProfile;
use App\Models\ServicePrice;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class LandingPageController extends Controller
{
    public function index()
    {
        // Cache the landing page data for 5 minutes (300 seconds) to optimize performance
        // as per PRD NFR optimizations
        $data = Cache::remember('landing_page_data_with_testimonials', 300, function () {
            return [
                'counselors' => CounselorProfile::visible()
                    ->with('user:id,name')
                    ->orderBy('display_order')
                    ->get(),
                'services' => ServicePrice::active()
                    ->get(),
                'testimonials' => Testimonial::with('booking.counselor')
                    ->where('is_visible', true)
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get(),
            ];
        });

        return Inertia::render('Landing', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'counselors' => $data['counselors'],
            'services' => $data['services'],
            'testimonials' => $data['testimonials'],
        ]);
    }
}
