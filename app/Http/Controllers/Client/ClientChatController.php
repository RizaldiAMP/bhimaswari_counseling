<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Message;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ClientChatController extends Controller
{
    public function index(Request $request): Response
    {
        $bookings = Booking::with(['counselor.counselorProfile', 'chatSession'])
            ->where('client_id', $request->user()->id)
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
                    'counselor' => [
                        'id' => $booking->counselor->id,
                        'name' => $booking->counselor->name,
                        'full_title' => $booking->counselor->counselorProfile?->full_title ?? '',
                        'photo_path' => $booking->counselor->counselorProfile?->photo_path,
                        'photo_url' => $booking->counselor->counselorProfile?->photo_url,
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

        return Inertia::render('Client/ChatIndex', [
            'bookings' => $bookings,
        ]);
    }

    public function show(Request $request, Booking $booking): Response|RedirectResponse
    {
        if ($booking->client_id !== $request->user()->id) {
            abort(403);
        }

        if (! in_array($booking->status, ['confirmed', 'in_session', 'completed'], true)) {
            return redirect()->route('client.bookings.show', $booking->id)
                ->with('error', 'Sesi chat belum tersedia untuk booking ini.');
        }

        // Client can access chat when scheduled time has arrived
        if (now()->lt($booking->schedule_start)) {
            return redirect()->route('client.bookings.show', $booking->id)
                ->with('error', 'Akses chat baru aktif saat jadwal sesi dimulai.');
        }

        // Create ChatSession if it doesn't exist yet (client initiates)
        $chatSession = $booking->chatSession;
        if ($chatSession === null) {
            $chatSession = $booking->chatSession()->create([
                'started_at' => now(),
            ]);

            // Auto-send intake_form as the first message
            if ($booking->intake_form) {
                Message::create([
                    'chat_session_id' => $chatSession->id,
                    'sender_id' => $request->user()->id,
                    'body' => $booking->intake_form,
                ]);
            }
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

        $booking->load(['counselor:id,name', 'counselor.counselorProfile:id,user_id,photo_path,full_title', 'servicePrice', 'chatSession']);

        $messages = Message::with('sender:id,name')
            ->where('chat_session_id', $chatSession->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Client/Chat', [
            'booking' => $booking,
            'messages' => $messages,
            'timerStartedAt' => $chatSession->timer_started_at?->toISOString(),
            'durationMinutes' => $booking->servicePrice->duration_minutes ?? 60,
            'timerExpired' => $timerExpired,
        ]);
    }

    public function storeMessage(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->client_id !== $request->user()->id) {
            abort(403);
        }

        if ($booking->status !== 'in_session') {
            return back()->with('error', 'Sesi chat belum aktif.');
        }

        if ($booking->chatSession === null) {
            return back()->with('error', 'Sesi chat belum dimulai.');
        }

        // Check if timer has expired
        if ($booking->chatSession->timer_started_at && 
            now()->gt($booking->chatSession->timer_started_at->copy()->addMinutes(60))) {
            return back()->with('error', 'Waktu sesi telah habis.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $message = Message::create([
            'chat_session_id' => $booking->chatSession->id,
            'sender_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return back();
    }
}
