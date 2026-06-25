<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class AdminTestimonialController extends Controller
{
    public function index()
    {
        $testimonials = Testimonial::with('booking.counselor', 'client')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return \Inertia\Inertia::render('Admin/Testimonials/Index', [
            'testimonials' => $testimonials,
        ]);
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimoni berhasil dihapus.');
    }

    public function toggleActive(Testimonial $testimonial)
    {
        $testimonial->update(['is_visible' => !$testimonial->is_visible]);

        $status = $testimonial->is_visible ? 'ditampilkan' : 'disembunyikan';
        return back()->with('success', "Testimoni berhasil {$status}.");
    }
}
