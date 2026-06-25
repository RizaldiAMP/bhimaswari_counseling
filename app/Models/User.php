<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'google_id',
        'name',
        'email',
        'email_verified_at',
        'whatsapp',
        'password',
        'role',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ── Helpers ──

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCounselor(): bool
    {
        return $this->role === 'counselor';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    // ── Relations ──

    public function counselorProfile(): HasOne
    {
        return $this->hasOne(CounselorProfile::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'client_id');
    }

    public function bookingsAsClient(): HasMany
    {
        return $this->bookings();
    }

    public function bookingsAsCounselor(): HasMany
    {
        return $this->hasMany(Booking::class, 'counselor_id');
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class, 'counselor_id');
    }

    public function availabilityExceptions(): HasMany
    {
        return $this->hasMany(AvailabilityException::class, 'counselor_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
