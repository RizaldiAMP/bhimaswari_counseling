<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class ClientPaymentController extends Controller
{
    /**
     * Store a newly created payment proof in storage.
     */
    public function store(Request $request, Booking $booking, PaymentService $paymentService)
    {
        if ($request->user()->id !== $booking->client_id) {
            abort(403, 'Unauthorized action.');
        }

        $existingPayment = $booking->payment;
        $isRejected = $existingPayment && $existingPayment->status === 'rejected';

        if (! in_array($booking->status, ['pending_payment', 'pending_verification'], true) && ! $isRejected) {
            return back()->withErrors(['proof' => 'Status booking saat ini tidak dapat mengunggah bukti pembayaran.']);
        }

        $request->validate([
            'proof' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $paymentService->uploadProof($booking, $request->file('proof'));

        return back()->with('success', 'Bukti pembayaran berhasil diunggah dan sedang menunggu verifikasi.');
    }
}
