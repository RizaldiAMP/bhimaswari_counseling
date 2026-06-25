<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminVerificationController extends Controller
{
    public function index()
    {
        $pendingVerifications = Booking::with(['client:id,name,email', 'counselor:id,name', 'payment', 'servicePrice'])
            ->where('status', 'pending_verification')
            ->orderBy('updated_at', 'asc')
            ->paginate(15);

        $recentDecisions = Payment::with(['booking.client:id,name', 'booking.counselor:id,name', 'verifier:id,name'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('verified_at', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Verifications', [
            'pendingVerifications' => $pendingVerifications,
            'recentDecisions' => $recentDecisions,
        ]);
    }

    public function approve(Request $request, Booking $booking, PaymentService $paymentService)
    {
        $paymentService->approve($booking->payment, $request->user()->id);

        return back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function reject(Request $request, Booking $booking, PaymentService $paymentService)
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $paymentService->reject($booking->payment, $request->user()->id, $request->rejection_reason);

        return back()->with('success', 'Pembayaran ditolak. Klien akan diminta upload ulang.');
    }

    public function showProof(Payment $payment)
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = \Illuminate\Support\Facades\Storage::disk('private');

        if (!$disk->exists($payment->proof_file_path)) {
            abort(404);
        }

        return $disk->response($payment->proof_file_path);
    }
}
