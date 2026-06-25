<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounselorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'practitioner_type',
        'full_title',
        'sipp_number',
        'bio',
        'photo_path',
        'specializations',
        'display_order',
        'is_visible',
    ];

    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return [
            'specializations' => 'array',
            'is_visible' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /**
     * Resolve the full URL for the counselor's photo.
     * Priority: uploaded photo (counselor-photos/) > static (/images/) > null
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }

        // External URL
        if (str_starts_with($this->photo_path, 'http')) {
            return $this->photo_path;
        }

        // Static public image (e.g. /images/name.webp)
        if (str_starts_with($this->photo_path, '/images/')) {
            return $this->photo_path;
        }

        // Uploaded file in storage
        return '/storage/' . $this->photo_path;
    }

    // ── Relations ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ──

    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true)
            ->whereHas('user', function (Builder $q) {
                $q->where('is_active', true);
            })
            ->orderBy('display_order');
    }
}
