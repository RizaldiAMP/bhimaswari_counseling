<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CounselorChatController extends Controller
{
    public function index(Request $request): Response
    {
        $bookings = Booking::with(['client:id,name', 'chatSession'])
            ->where('counselor_id', $request->user()->id)
            ->where('service_type', 'chat')
            ->whereIn('status', ['confirmed', 'in_session', 'completed'])
            ->orderByRaw("FIELD(status, 'in_session', 'confirmed', 'completed')")
            ->orderBy('schedule_start', 'desc')
            ->get()
            ->map(function ($booking) {
                $lastMessage = null;
                if ($booking->chatSession) {
                    $lastMessage = Message::where('chat_session_id', $booking->chatSession->id)->latest()->first();
                }

                return [
                    'id' => $booking->id,
                    'status' => $booking->status,
                    'schedule_start' => $booking->schedule_start,
                    'schedule_end' => $booking->schedule_end,
                    'client' => [
                        'name' => $booking->client->name,
                    ],
                    'chat_session' => $booking->chatSession ? [
                        'id' => $booking->chatSession->id,
                        'last_message' => $lastMessage ? [
                            'body' => $lastMessage->body,
                            'created_at' => $lastMessage->created_at,
                        ] : null,
                    ] : null,
                ];
            });

        return Inertia::render('Counselor/ChatIndex', [
            'bookings' => $bookings,
        ]);
    }

    public function show(Request $request, Booking $booking): Response|RedirectResponse
    {
        if ($booking->counselor_id !== $request->user()->id) {
            abort(403);
        }

        if (! in_array($booking->status, ['confirmed', 'in_session', 'completed'], true)) {
            return redirect()->route('counselor.bookings.show', $booking->id)
                ->with('error', 'Sesi chat belum tersedia untuk booking ini.');
        }

        // Counselor can access 10 minutes before schedule
        if (now()->lt($booking->schedule_start->copy()->subMinutes(10))) {
            return redirect()->route('counselor.bookings.show', $booking->id)
                ->with('error', 'Akses chat konselor aktif mulai 10 menit sebelum jadwal.');
        }

        // If ChatSession doesn't exist yet (client hasn't entered), create it
        $chatSession = $booking->chatSession;
        if ($chatSession === null) {
            $chatSession = $booking->chatSession()->create([
                'started_at' => now(),
            ]);
        }

        // Move booking to in_session if still confirmed
        if ($booking->status === 'confirmed') {
            $booking->update(['status' => 'in_session']);
        }

        // Check if session timer has expired (timer_started_at + 60 min)
        $timerExpired = false;
        if ($chatSession->timer_started_at) {
            $timerExpired = now()->gt($chatSession->timer_started_at->copy()->addMinutes(60));
        }

        $booking->load(['client:id,name', 'servicePrice', 'chatSession']);

        $messages = Message::with('sender:id,name')
            ->where('chat_session_id', $chatSession->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Counselor/Chat', [
            'booking' => $booking,
            'messages' => $messages,
            'timerStartedAt' => $chatSession->timer_started_at?->toISOString(),
            'durationMinutes' => $booking->servicePrice->duration_minutes ?? 60,
            'timerExpired' => $timerExpired,
        ]);
    }

    public function storeMessage(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->counselor_id !== $request->user()->id) {
            abort(403);
        }

        if ($booking->status !== 'in_session' || $booking->chatSession === null) {
            return back()->with('error', 'Sesi chat belum aktif.');
        }

        // Check if timer has expired
        if ($booking->chatSession->timer_started_at && 
            now()->gt($booking->chatSession->timer_started_at->copy()->addMinutes(60))) {
            return back()->with('error', 'Waktu sesi telah habis.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        // Start timer on counselor's first reply
        if ($booking->chatSession->timer_started_at === null) {
            $booking->chatSession->update([
                'timer_started_at' => now(),
            ]);
        }

        $message = Message::create([
            'chat_session_id' => $booking->chatSession->id,
            'sender_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return back();
    }

    public function complete(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->counselor_id !== $request->user()->id) {
            abort(403);
        }

        if ($booking->status !== 'in_session') {
            return back()->with('error', 'Sesi tidak dalam status berjalan.');
        }

        $booking->update(['status' => 'completed']);

        if ($booking->chatSession) {
            $booking->chatSession->update([
                'ended_at' => now(),
                'ended_by' => 'counselor',
            ]);
        }

        return redirect()->route('counselor.bookings.show', $booking->id)
            ->with('success', 'Sesi berhasil diselesaikan.');
    }
}
