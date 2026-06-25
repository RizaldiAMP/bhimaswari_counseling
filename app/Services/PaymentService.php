<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\AutoStartSession;
use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\InAppNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    /**
     * Upload bukti transfer dan update status booking + payment.
     */
    public function uploadProof(Booking $booking, UploadedFile $file): Payment
    {
        $this->validateMagicNumber($file);
        $path = null;

        try {
            return DB::transaction(function () use ($booking, $file, &$path) {
                /** @var Booking $lockedBooking */
                $lockedBooking = Booking::query()
                    ->with(['payment', 'client:id,name'])
                    ->lockForUpdate()
                    ->findOrFail($booking->id);

                if (
                    $lockedBooking->status === 'pending_verification'
                    && $lockedBooking->payment?->status === 'pending_verification'
                    && $lockedBooking->payment?->proof_file_path
                ) {
                    return $lockedBooking->payment;
                }

                if (! in_array($lockedBooking->status, ['pending_payment', 'pending_verification'], true)) {
                    throw ValidationException::withMessages([
                        'proof' => 'Status booking saat ini tidak dapat mengunggah bukti pembayaran.',
                    ]);
                }

                $path = $file->store('payments', 'private');

                $oldPath = $lockedBooking->payment?->proof_file_path;

                /** @var Payment $payment */
                $payment = $lockedBooking->payment()->updateOrCreate(
                    ['booking_id' => $lockedBooking->id],
                    [
                        'status' => 'pending_verification',
                        'proof_file_path' => $path,
                        'proof_original_name' => $file->getClientOriginalName(),
                        'proof_mime_type' => $file->getMimeType(),
                        'proof_file_size' => $file->getSize(),
                    ]
                );

                $lockedBooking->update(['status' => 'pending_verification']);

                if ($oldPath && $oldPath !== $path) {
                    Storage::disk('private')->delete($oldPath);
                }

                $admins = \App\Models\User::query()
                    ->where('role', 'admin')
                    ->where('is_active', true)
                    ->get();

                foreach ($admins as $admin) {
                    /** @var \App\Models\User $admin */
                    $admin->notify(new InAppNotification(
                        event: 'payment_proof_uploaded',
                        title: 'Bukti transfer baru menunggu verifikasi',
                        message: "Booking #{$lockedBooking->id} dari {$lockedBooking->client->name} sudah mengunggah bukti transfer.",
                        url: route('admin.verifications.index', absolute: false),
                        meta: ['booking_id' => $lockedBooking->id],
                    ));
                }

                return $payment;
            });
        } catch (\Throwable $exception) {
            if ($path !== null) {
                Storage::disk('private')->delete($path);
            }

            throw $exception;
        }
    }

    /**
     * Admin approve bukti pembayaran.
     */
    public function approve(Payment $payment, int $adminId): void
    {
        DB::transaction(function () use ($payment, $adminId) {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status === 'approved') {
                return;
            }

            if ($lockedPayment->status !== 'pending_verification') {
                throw ValidationException::withMessages([
                    'payment' => 'Status pembayaran tidak valid untuk approve.',
                ]);
            }

            $booking = Booking::query()->lockForUpdate()->findOrFail($lockedPayment->booking_id);

            $lockedPayment->update([
                'status' => 'approved',
                'verified_by' => $adminId,
                'verified_at' => now(),
            ]);

            $booking->update(['status' => 'confirmed']);

            $booking->loadMissing(['client:id,name', 'counselor:id,name']);

            $booking->client->notify(new InAppNotification(
                event: 'booking_confirmed',
                title: 'Booking Anda sudah dikonfirmasi',
                message: "Pembayaran booking #{$booking->id} telah diverifikasi admin.",
                url: route('client.bookings.show', ['booking' => $booking->id], false),
                meta: ['booking_id' => $booking->id],
            ));

            if ($booking->counselor !== null) {
                $booking->counselor->notify(new InAppNotification(
                    event: 'new_confirmed_booking',
                    title: 'Booking baru siap ditangani',
                    message: "Booking #{$booking->id} dari {$booking->client->name} telah terkonfirmasi.",
                    url: route('counselor.bookings.show', ['booking' => $booking->id], false),
                    meta: ['booking_id' => $booking->id],
                ));
            }

            $this->auditService->log(
                userId: $adminId,
                action: 'payment_approved',
                auditable: $booking,
                oldValues: ['status' => 'pending_verification'],
                newValues: ['status' => 'confirmed'],
            );

            // Dispatch delayed job: auto-start session saat schedule_start tiba
            $delay = $booking->schedule_start->isFuture()
                ? $booking->schedule_start
                : now();

            AutoStartSession::dispatch($booking->id)->delay($delay);
        });
    }

    /**
     * Admin reject bukti pembayaran.
     */
    public function reject(Payment $payment, int $adminId, string $reason): void
    {
        DB::transaction(function () use ($payment, $adminId, $reason) {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status === 'rejected' && $lockedPayment->rejection_reason === $reason) {
                return;
            }

            if ($lockedPayment->status !== 'pending_verification') {
                throw ValidationException::withMessages([
                    'payment' => 'Status pembayaran tidak valid untuk reject.',
                ]);
            }

            $booking = Booking::query()->lockForUpdate()->findOrFail($lockedPayment->booking_id);

            $lockedPayment->update([
                'status' => 'rejected',
                'verified_by' => $adminId,
                'verified_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $booking->loadMissing(['client:id,name']);

            $booking->client->notify(new InAppNotification(
                event: 'payment_rejected',
                title: 'Bukti transfer ditolak',
                message: "Booking #{$booking->id} perlu upload ulang bukti transfer. Alasan: {$reason}",
                url: route('client.bookings.show', ['booking' => $booking->id], false),
                meta: ['booking_id' => $booking->id],
            ));

            $this->auditService->log(
                userId: $adminId,
                action: 'payment_rejected',
                auditable: $booking,
                oldValues: ['payment_status' => 'pending_verification'],
                newValues: ['payment_status' => 'rejected', 'reason' => $reason],
            );
        });
    }

    /**
     * Validasi magic number (hex signature) file upload.
     * PRD Seksi 6.3: Hanya jpeg, png, webp yang diizinkan.
     */
    private function validateMagicNumber(UploadedFile $file): void
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $bytes = fread($handle, 12);
        fclose($handle);

        $hex = bin2hex($bytes);

        $validSignatures = [
            'ffd8ff' => 'image/jpeg',
            '89504e47' => 'image/png',
            '52494646' => 'image/webp',
        ];

        $isValid = false;
        foreach ($validSignatures as $signature => $mime) {
            if (str_starts_with($hex, (string) $signature)) {
                $isValid = true;
                break;
            }
        }

        if (! $isValid) {
            abort(422, 'File yang diupload bukan gambar yang valid. Hanya JPEG, PNG, dan WebP yang diizinkan.');
        }
    }
}
