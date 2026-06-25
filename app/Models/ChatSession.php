<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    protected $fillable = [
        'booking_id',
        'started_at',
        'timer_started_at',
        'ended_at',
        'ended_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'timer_started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    // ── Relations ──

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
