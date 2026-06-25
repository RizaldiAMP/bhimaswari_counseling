<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CounselorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CounselorSeeder extends Seeder
{
    public function run(): void
    {
        $practitioners = [
            // Associate Psikolog
            ['name' => 'Joko Tri Hartanto', 'title' => 'S. Psi., M. Psi., Psikolog', 'sipp' => '20230201-2023-0367', 'type' => 'psychologist'],
            ['name' => 'Shofura Hanifah', 'title' => 'S. Psi., M. Psi., Psikolog', 'sipp' => '20231129-2023-01-1520', 'type' => 'psychologist'],
            ['name' => 'Trya Dara Ruidahasi', 'title' => 'S. Psi., M. Psi., Psikolog', 'sipp' => '20240215-2024-01-4100', 'type' => 'psychologist'],
            ['name' => 'Aisyah Tri Wardhani', 'title' => 'S. Psi., Psikolog', 'sipp' => '20240320-2024-01-4250', 'type' => 'psychologist'],
            ['name' => 'Winda Kusuma Ayu', 'title' => 'S. Psi., Psikolog', 'sipp' => '20240501-2024-01-4600', 'type' => 'psychologist'],
            ['name' => 'Nurul Nabila Annisa', 'title' => 'S. Psi., Psikolog', 'sipp' => '20240610-2024-01-4800', 'type' => 'psychologist'],
            ['name' => 'Nadya Mubaraniz', 'title' => 'S. Psi., Psikolog', 'sipp' => '20240725-2024-01-5100', 'type' => 'psychologist'],
            ['name' => 'Ghina Ciptadewi', 'title' => 'S. Psi., Psikolog', 'sipp' => '20240830-2024-01-5250', 'type' => 'psychologist'],
            ['name' => 'Ghazali Fauzia', 'title' => 'S. Psi., M. Psi., Psikolog', 'sipp' => '20241005-2024-01-5400', 'type' => 'psychologist'],
            ['name' => 'Ifti Aisha', 'title' => 'S. Psi., M. Psi., Psikolog', 'sipp' => '20241120-2024-01-5500', 'type' => 'psychologist'],
            ['name' => 'Bagas Alam', 'title' => 'S. Psi., M. Psi., Psikolog', 'sipp' => '20250110-2025-01-0100', 'type' => 'psychologist'],
            // Konselor
            ['name' => 'Rizkie Alief Madani', 'title' => 'S. Psi.', 'sipp' => null, 'type' => 'counselor'],
        ];

        foreach ($practitioners as $index => $data) {
            $email = $data['name'] === 'Joko Tri Hartanto'
                ? 'jokotrihartanto95@gmail.com'
                : strtolower(str_replace(' ', '.', $data['name'])) . '@bhimaswari.id';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => 'counselor',
                    'whatsapp' => '08' . str_pad((string) ($index + 1), 10, '0', STR_PAD_LEFT),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            CounselorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'practitioner_type' => $data['type'],
                    'full_title' => $data['title'],
                    'sipp_number' => $data['sipp'],
                    'bio' => null,
                    'photo_path' => '/images/' . strtolower(str_replace(' ', '_', $data['name'])) . '.webp',
                    'specializations' => null,
                    'display_order' => $index,
                    'is_visible' => true,
                ]
            );
        }
    }
}
