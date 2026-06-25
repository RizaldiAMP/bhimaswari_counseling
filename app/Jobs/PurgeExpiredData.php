<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Message;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredData implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $this->purgePaymentProofFiles();
        $this->purgeOldMessages();
        $this->purgeOldAuditLogs();
    }

    private function purgePaymentProofFiles(): void
    {
        $cutoff = Carbon::now()->subYears(2);

        Payment::query()
            ->whereNotNull('proof_file_path')
            ->where(function ($query) use ($cutoff) {
                $query->where('verified_at', '<', $cutoff)
                    ->orWhere(function ($subQuery) use ($cutoff) {
                        $subQuery->whereNull('verified_at')
                            ->where('updated_at', '<', $cutoff);
                    });
            })
            ->chunkById(200, function ($payments): void {
                foreach ($payments as $payment) {
                    if ($payment->proof_file_path) {
                        Storage::disk('private')->delete($payment->proof_file_path);
                    }

                    $payment->update([
                        'proof_file_path' => null,
                        'proof_original_name' => null,
                        'proof_mime_type' => null,
                        'proof_file_size' => null,
                    ]);
                }
            });
    }

    private function purgeOldMessages(): void
    {
        Message::query()
            ->where('created_at', '<', Carbon::now()->subYears(2))
            ->delete();
    }

    private function purgeOldAuditLogs(): void
    {
        AuditLog::query()
            ->where('created_at', '<', Carbon::now()->subYears(3))
            ->delete();
    }
}
