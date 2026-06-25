<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reschedule extends Model
{
    protected $fillable = [
        'booking_id',
        'requested_by',
        'old_schedule_start',
        'old_schedule_end',
        'new_schedule_start',
        'new_schedule_end',
        'reason',
        'status',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'old_schedule_start' => 'datetime',
            'old_schedule_end' => 'datetime',
            'new_schedule_start' => 'datetime',
            'new_schedule_end' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
