<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AvailabilityRule;
use App\Models\User;
use Illuminate\Database\Seeder;

class CounselorAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        // Get all users who are counselors
        $counselors = User::where('role', 'counselor')->get();

        if ($counselors->isEmpty()) {
            echo "Tidak ada konselor ditemukan. Silakan jalankan CounselorSeeder terlebih dahulu.\n";
            return;
        }

        // Define a clean set of availability rules
        foreach ($counselors as $index => $counselor) {
            // Hapus rule lama untuk menghindari konflik/duplikasi
            AvailabilityRule::where('counselor_id', $counselor->id)->delete();

            // Distribusikan hari aktif agar bervariasi:
            // 0=Senin, 1=Selasa, 2=Rabu, 3=Kamis, 4=Jumat, 5=Sabtu, 6=Minggu
            $mod = $index % 3;
            if ($mod === 0) {
                $days = [0, 2, 4]; // Senin, Rabu, Jumat
            } elseif ($mod === 1) {
                $days = [1, 3, 5]; // Selasa, Kamis, Sabtu
            } else {
                $days = [0, 2, 6]; // Senin, Rabu, Minggu
            }

            foreach ($days as $day) {
                // Create discrete hourly slots for unified availability
                $slotTimes = [
                    '09:00:00', '10:00:00', '11:00:00', // Pagi
                    '14:00:00', '15:00:00', '16:00:00', // Sore
                    '19:00:00', '20:00:00'              // Malam
                ];

                foreach ($slotTimes as $time) {
                    AvailabilityRule::create([
                        'counselor_id' => $counselor->id,
                        'day_of_week' => $day,
                        'start_time' => $time,
                        'is_active' => true,
                    ]);
                }
            }
        }

        echo "Jadwal availability rules (Chat, Online, & Offline) berhasil dibuat untuk " . $counselors->count() . " konselor.\n";
    }
}
