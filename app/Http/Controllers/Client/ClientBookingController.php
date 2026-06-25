<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ServicePrice;
use App\Services\RescheduleService;
use App\Services\SlotGeneratorService;
use Illuminate\Http\Request;

class ClientBookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $bookings = Booking::where('client_id', $request->user()->id)
            ->with(['counselor.counselorProfile', 'payment', 'servicePrice'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return \Inertia\Inertia::render('Client/Bookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $servicePrices = ServicePrice::where('is_active', true)->get();
        
        $counselors = \Inertia\Inertia::lazy(fn () => 
            \App\Models\CounselorProfile::where('is_visible', true)
                ->with('user')
                ->get()
        );

        $slots = \Inertia\Inertia::lazy(function () use ($request) {
            $servicePriceId = (int) $request->input('service_price_id');

            if ($servicePriceId <= 0) {
                return [];
            }

            return app(SlotGeneratorService::class)->generateForServicePrice($servicePriceId);
        });

        return \Inertia\Inertia::render('Client/Bookings/Create', [
            'servicePrices' => $servicePrices,
            'counselors' => $counselors,
            'slots' => $slots,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, \App\Services\BookingService $bookingService)
    {
        $validated = $request->validate([
            'counselor_id' => ['required', 'exists:users,id'],
            'service_price_id' => ['required', 'exists:service_prices,id'],
            'schedule_start' => ['required', 'date', 'after:now'],
            'intake_form' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $booking = $bookingService->createBooking($request->user(), $validated);

            // Kirim notifikasi WhatsApp & Email bahwa booking berhasil dibuat
            $booking->client->notify(new \App\Notifications\InAppNotification(
                event: 'booking_created',
                title: 'Booking Berhasil Dibuat',
                message: "Booking #{$booking->id} berhasil dibuat. Silakan unggah bukti pembayaran dalam 15 menit.",
                url: route('client.bookings.show', ['booking' => $booking->id], false),
                meta: ['booking_id' => $booking->id],
            ));

            return redirect()->route('client.bookings.show', $booking->id)
                ->with('success', 'Booking berhasil dibuat. Silakan unggah bukti pembayaran dalam 15 menit.');
        } catch (\App\Exceptions\SlotAlreadyBookedException $e) {
            return back()->withErrors(['schedule_start' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request)
    {
        $booking = Booking::with(['counselor.counselorProfile', 'payment', 'servicePrice'])
            ->findOrFail($id);

        if ($booking->client_id !== $request->user()->id) {
            abort(403);
        }

        return \Inertia\Inertia::render('Client/Bookings/Show', [
            'booking' => $booking,
        ]);
    }

    public function requestReschedule(Request $request, Booking $booking, RescheduleService $rescheduleService)
    {
        if ($booking->client_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'new_schedule_start' => ['required', 'date', 'after:now'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $rescheduleService->requestByClient($request->user(), $booking, $validated);

        return back()->with('success', 'Pengajuan reschedule berhasil dikirim. Menunggu persetujuan admin.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        abort(404);
    }

    public function createTestimonial(Request $request)
    {
        $user = $request->user();
        
        $booking = Booking::where('client_id', $user->id)
            ->where('status', 'completed')
            ->whereDoesntHave('testimonial')
            ->orderByDesc('schedule_start')
            ->first();

        return \Inertia\Inertia::render('Client/Testimonials/Create', [
            'bookingId' => $booking?->id,
            'counselorName' => $booking?->counselor?->name,
        ]);
    }

    public function storeTestimonial(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        \App\Models\Testimonial::create([
            'booking_id' => $validated['booking_id'],
            'client_id' => $user->id,
            'client_name' => $user->name,
            'content' => $validated['content'],
            'rating' => $validated['rating'],
            'is_visible' => false,
        ]);

        return redirect()->route('client.dashboard')
            ->with('success', 'Terima kasih! Ulasan Anda berhasil dikirim.');
    }
}
