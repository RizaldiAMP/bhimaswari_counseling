<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityException extends Model
{
    protected $fillable = [
        'counselor_id',
        'exception_date',
        'start_time',
        'type',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'exception_date' => 'date',
        ];
    }

    // ── Relations ──

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }
}
