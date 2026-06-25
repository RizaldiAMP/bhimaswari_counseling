<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ServicePrice;
use Illuminate\Database\Seeder;

class ServicePriceSeeder extends Seeder
{
    public function run(): void
    {
        $prices = [
            // Chat (PRD 12.1)
            ['service_type' => 'chat', 'practitioner_type' => 'psychologist', 'duration_minutes' => 60, 'price' => 100000],
            ['service_type' => 'chat', 'practitioner_type' => 'counselor', 'duration_minutes' => 60, 'price' => 50000],
            // Online (PRD 12.1)
            ['service_type' => 'online', 'practitioner_type' => 'psychologist', 'duration_minutes' => 60, 'price' => 200000],
            ['service_type' => 'online', 'practitioner_type' => 'psychologist', 'duration_minutes' => 90, 'price' => 265000],
            ['service_type' => 'online', 'practitioner_type' => 'counselor', 'duration_minutes' => 60, 'price' => 150000],
            // Offline (PRD 12.1)
            ['service_type' => 'offline', 'practitioner_type' => 'psychologist', 'duration_minutes' => 60, 'price' => 250000],
            ['service_type' => 'offline', 'practitioner_type' => 'psychologist', 'duration_minutes' => 90, 'price' => 315000],
            ['service_type' => 'offline', 'practitioner_type' => 'counselor', 'duration_minutes' => 60, 'price' => 200000],
        ];

        foreach ($prices as $price) {
            ServicePrice::updateOrCreate(
                [
                    'service_type' => $price['service_type'],
                    'practitioner_type' => $price['practitioner_type'],
                    'duration_minutes' => $price['duration_minutes'],
                ],
                [
                    'price' => $price['price'],
                    'is_active' => true,
                ]
            );
        }
    }
}
