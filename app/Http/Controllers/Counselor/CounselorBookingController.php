<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Notifications\InAppNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CounselorBookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $bookings = $user->bookingsAsCounselor()
            ->with(['client:id,name,email', 'servicePrice'])
            ->orderBy('schedule_start', 'desc')
            ->paginate(15);

        return Inertia::render('Counselor/Bookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    public function show(Request $request, Booking $booking)
    {
        if ($booking->counselor_id !== $request->user()->id) {
            abort(403);
        }

        $booking->load(['client:id,name,email,whatsapp,created_at', 'payment', 'servicePrice', 'chatSession']);

        return Inertia::render('Counselor/Bookings/Show', [
            'booking' => $booking,
        ]);
    }

    public function updateMeetingInfo(Request $request, Booking $booking)
    {
        if ($booking->counselor_id !== $request->user()->id) {
            abort(403);
        }

        $isFinished = in_array($booking->status, ['completed', 'cancelled', 'rejected', 'expired']) 
            || now()->gt($booking->schedule_end);

        if ($isFinished || !in_array($booking->status, ['confirmed', 'in_session'])) {
            return back()->withErrors(['message' => 'Sesi yang sudah selesai atau tidak aktif tidak dapat diubah.']);
        }

        // Auto-sanitize and trim meeting_link input before validating
        if ($request->has('meeting_link') && !empty($request->input('meeting_link'))) {
            $link = trim($request->input('meeting_link'));
            if (!preg_match('~^(?:f|ht)tps?://~i', $link)) {
                $link = 'https://' . $link;
            }
            $request->merge(['meeting_link' => $link]);
        }

        // Trim other inputs as well
        if ($request->has('meeting_location') && is_array($request->input('meeting_location'))) {
            $location = $request->input('meeting_location');
            $trimmed = [];
            foreach ($location as $key => $value) {
                $trimmed[$key] = is_string($value) ? trim($value) : $value;
            }
            $request->merge(['meeting_location' => $trimmed]);
        }
        if ($request->has('meeting_notes')) {
            $request->merge(['meeting_notes' => trim((string)$request->input('meeting_notes'))]);
        }

        $validated = $request->validate([
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'meeting_location' => ['nullable', 'array'],
            'meeting_location.place_name' => ['required_with:meeting_location', 'nullable', 'string', 'max:255'],
            'meeting_location.address' => ['required_with:meeting_location', 'nullable', 'string', 'max:500'],
            'meeting_location.city' => ['required_with:meeting_location', 'nullable', 'string', 'max:100'],
            'meeting_location.google_maps_url' => ['nullable', 'url', 'max:1000'],
            'meeting_location.landmark' => ['nullable', 'string', 'max:255'],
            'meeting_location.parking_info' => ['nullable', 'string', 'max:255'],
            'meeting_location.contact_on_site' => ['nullable', 'string', 'max:100'],
            'meeting_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Clean empty location: if all sub-fields are empty, set to null
        if (isset($validated['meeting_location'])) {
            $hasValue = collect($validated['meeting_location'])->filter()->isNotEmpty();
            if (!$hasValue) {
                $validated['meeting_location'] = null;
            }
        }

        $booking->update($validated);

        if (! empty($validated['meeting_link']) || ! empty($validated['meeting_location'])) {
            $booking->loadMissing('client:id,name');

            $locationName = is_array($validated['meeting_location'] ?? null)
                ? ($validated['meeting_location']['place_name'] ?? 'lokasi yang ditentukan')
                : 'meeting';

            $booking->client->notify(new InAppNotification(
                event: 'meeting_info_updated',
                title: 'Info meeting sudah tersedia',
                message: "Konselor sudah mengisi info meeting untuk booking #{$booking->id}. Lokasi: {$locationName}.",
                url: route('client.bookings.show', ['booking' => $booking->id], false),
                meta: ['booking_id' => $booking->id],
            ));
        }

        return back()->with('success', 'Info meeting berhasil diperbarui.');
    }
}
