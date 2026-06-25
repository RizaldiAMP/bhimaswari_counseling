<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'bhimaswarifamily@gmail.com'],
            [
                'name' => 'Admin Bhimaswari',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'whatsapp' => '082311467657',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
