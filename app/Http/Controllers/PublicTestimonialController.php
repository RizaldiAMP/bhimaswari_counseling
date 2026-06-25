<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Testimonial;

class PublicTestimonialController extends Controller
{
    /**
     * Show the form for creating a new public testimonial.
     */
    public function create()
    {
        return \Inertia\Inertia::render('Public/Testimonial/Create');
    }

    /**
     * Store a newly created testimonial in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        Testimonial::create([
            'booking_id' => null,
            'client_id' => null,
            'client_name' => $validated['client_name'],
            'content' => $validated['content'],
            'rating' => $validated['rating'],
            'is_visible' => false,
        ]);

        return redirect()->route('landing')
            ->with('success', 'Terima kasih! Ulasan Anda berhasil dikirim.');
    }
}
