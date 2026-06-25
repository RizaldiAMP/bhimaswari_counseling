<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\CounselorProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CounselorManagementService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function createCounselor(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'whatsapp' => $data['whatsapp'] ?? '',
                'password' => Hash::make($data['password']),
                'role' => 'counselor',
            ]);

            CounselorProfile::create([
                'user_id' => $user->id,
                'practitioner_type' => $data['practitioner_type'],
                'full_title' => $data['full_title'] ?? $data['name'],
                'sipp_number' => $data['sipp_number'] ?? null,
                'bio' => '',
                'specializations' => [],
                'is_visible' => true,
            ]);

            return $user;
        });
    }

    /**
     * Update data konselor (nama, email, tipe praktisi, SIPP, bio, spesialisasi).
     */
    public function updateCounselor(User $counselor, array $data, int $adminId): User
    {
        return DB::transaction(function () use ($counselor, $data, $adminId) {
            $oldValues = [
                'name' => $counselor->name,
                'email' => $counselor->email,
            ];

            $counselor->update(array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
            ], fn ($v) => $v !== null));

            $profile = $counselor->counselorProfile;
            if ($profile) {
                $oldValues['practitioner_type'] = $profile->practitioner_type;
                $oldValues['sipp_number'] = $profile->sipp_number;
                $oldValues['bio'] = $profile->bio;

                $profileData = array_filter([
                    'practitioner_type' => $data['practitioner_type'] ?? null,
                    'sipp_number' => array_key_exists('sipp_number', $data) ? $data['sipp_number'] : null,
                    'bio' => array_key_exists('bio', $data) ? $data['bio'] : null,
                    'full_title' => $data['name'] ?? null,
                ], fn ($v) => $v !== null);

                if (!empty($profileData)) {
                    $profile->update($profileData);
                }
            }

            $this->auditService->log(
                userId: $adminId,
                action: 'counselor_updated',
                auditable: $counselor,
                oldValues: $oldValues,
                newValues: $data,
            );

            return $counselor->fresh(['counselorProfile']);
        });
    }

    /**
     * Toggle active/inactive seorang konselor.
     * Jika akan di-nonaktifkan, cek dulu apakah ada booking aktif/mendatang.
     *
     * @throws ValidationException
     */
    public function toggleActive(User $counselor, int $adminId): User
    {
        $willBeActive = !$counselor->is_active;

        // Jika akan dinonaktifkan, cek booking aktif
        if (!$willBeActive) {
            $activeBookingCount = Booking::where('counselor_id', $counselor->id)
                ->activeBookings()
                ->count();

            if ($activeBookingCount > 0) {
                throw ValidationException::withMessages([
                    'toggle' => "Tidak dapat menonaktifkan konselor. Masih ada {$activeBookingCount} jadwal aktif yang belum selesai.",
                ]);
            }
        }

        return DB::transaction(function () use ($counselor, $willBeActive, $adminId) {
            $oldActive = $counselor->is_active;

            $counselor->update(['is_active' => $willBeActive]);

            // Sinkronkan is_visible di profile
            $profile = $counselor->counselorProfile;
            if ($profile) {
                $profile->update(['is_visible' => $willBeActive]);
            }

            $this->auditService->log(
                userId: $adminId,
                action: $willBeActive ? 'counselor_activated' : 'counselor_deactivated',
                auditable: $counselor,
                oldValues: ['is_active' => $oldActive],
                newValues: ['is_active' => $willBeActive],
            );

            return $counselor->fresh(['counselorProfile']);
        });
    }

    /**
     * Hapus akun konselor.
     * Hanya bisa dihapus jika tidak ada riwayat booking sama sekali.
     *
     * @throws ValidationException
     */
    public function deleteCounselor(User $counselor): void
    {
        $hasBookings = Booking::where('counselor_id', $counselor->id)->exists();

        if ($hasBookings) {
            throw ValidationException::withMessages([
                'delete' => 'Tidak dapat menghapus konselor ini karena sudah memiliki riwayat jadwal atau transaksi. Silakan nonaktifkan saja.',
            ]);
        }

        DB::transaction(function () use ($counselor) {
            $counselor->delete();
        });
    }
}
