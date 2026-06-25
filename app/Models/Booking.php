<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = [
        'client_id',
        'counselor_id',
        'service_price_id',
        'service_type',
        'duration_minutes',
        'price_at_booking',
        'schedule_start',
        'schedule_end',
        'status',
        'intake_form',
        'informed_consent',
        'meeting_link',
        'meeting_location',
        'meeting_notes',
        'reschedule_count',
        'payment_deadline',
    ];

    protected function casts(): array
    {
        return [
            'schedule_start' => 'datetime',
            'schedule_end' => 'datetime',
            'payment_deadline' => 'datetime',
            'informed_consent' => 'boolean',
            'duration_minutes' => 'integer',
            'price_at_booking' => 'integer',
            'reschedule_count' => 'integer',
            'meeting_location' => 'array',
        ];
    }

    // ── Relations ──

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function servicePrice(): BelongsTo
    {
        return $this->belongsTo(ServicePrice::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function chatSession(): HasOne
    {
        return $this->hasOne(ChatSession::class);
    }

    public function reschedules()
    {
        return $this->hasMany(Reschedule::class);
    }

    public function testimonial(): HasOne
    {
        return $this->hasOne(Testimonial::class);
    }

    // ── Accessors ──

    public function getStatusAttribute($value)
    {
        if ($value === 'pending_payment' && $this->payment_deadline && now()->greaterThan($this->payment_deadline)) {
            return 'expired';
        }
        return $value;
    }

    // ── Scopes ──

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('schedule_start', '>', now())
            ->whereIn('status', ['confirmed', 'pending_payment', 'pending_verification']);
    }

    public function scopeExpiredUnpaid(Builder $query): Builder
    {
        return $query->where('status', 'pending_payment')
            ->where('payment_deadline', '<', now());
    }

    /**
     * Scope: hanya pending_payment yang deadlinenya belum lewat (masih valid).
     */
    public function scopeActivePendingPayment(Builder $query): Builder
    {
        return $query->where('status', 'pending_payment')
            ->where(function ($q) {
                $q->whereNull('payment_deadline')
                  ->orWhere('payment_deadline', '>', now());
            });
    }

    /**
     * Scope: status aktif (termasuk pending_payment yang masih valid).
     * Digunakan untuk menghitung booking yang benar-benar masih berjalan.
     */
    public function scopeActiveBookings(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereIn('status', ['pending_verification', 'confirmed', 'in_session', 'pending_reschedule'])
              ->orWhere(function ($sub) {
                  $sub->where('status', 'pending_payment')
                      ->where(function ($inner) {
                          $inner->whereNull('payment_deadline')
                                ->orWhere('payment_deadline', '>', now());
                      });
              });
        });
    }

    /**
     * Scope: booking yang secara efektif expired (termasuk pending_payment yang deadlinenya lewat).
     */
    public function scopeEffectivelyExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
              ->orWhere(function ($sub) {
                  $sub->where('status', 'pending_payment')
                      ->whereNotNull('payment_deadline')
                      ->where('payment_deadline', '<=', now());
              });
        });
    }
}
