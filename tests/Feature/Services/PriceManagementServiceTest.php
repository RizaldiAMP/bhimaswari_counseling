<?php

namespace Tests\Feature\Services;

use App\Models\ServicePrice;
use App\Models\User;
use App\Services\PriceManagementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PriceManagementServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_creates_price_and_history(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'whatsapp' => '+628111111111',
        ]);

        $service = app(PriceManagementService::class);

        $price = $service->createPrice([
            'service_type' => 'online',
            'practitioner_type' => 'counselor',
            'duration_minutes' => 60,
            'price' => 200000,
        ], $admin->id);

        $this->assertDatabaseHas('service_prices', [
            'id' => $price->id,
            'price' => 200000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('service_price_histories', [
            'service_price_id' => $price->id,
            'changed_by' => $admin->id,
            'new_price' => 200000,
        ]);
    }

    public function test_it_updates_price_and_writes_history(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'whatsapp' => '+628122222222',
        ]);

        $price = ServicePrice::create([
            'service_type' => 'chat',
            'practitioner_type' => 'counselor',
            'duration_minutes' => 60,
            'price' => 120000,
            'is_active' => true,
        ]);

        $service = app(PriceManagementService::class);
        $service->updatePrice($price, 130000, 'Penyesuaian biaya operasional', $admin->id);

        $this->assertDatabaseHas('service_prices', [
            'id' => $price->id,
            'price' => 130000,
        ]);

        $this->assertDatabaseHas('service_price_histories', [
            'service_price_id' => $price->id,
            'old_price' => 120000,
            'new_price' => 130000,
            'changed_by' => $admin->id,
        ]);
    }

    public function test_it_toggles_active_status(): void
    {
        $price = ServicePrice::create([
            'service_type' => 'offline',
            'practitioner_type' => 'psychologist',
            'duration_minutes' => 90,
            'price' => 350000,
            'is_active' => true,
        ]);

        $service = app(PriceManagementService::class);

        $isActiveAfterFirstToggle = $service->toggleActive($price);
        $this->assertFalse($isActiveAfterFirstToggle);

        $isActiveAfterSecondToggle = $service->toggleActive($price->fresh());
        $this->assertTrue($isActiveAfterSecondToggle);
    }
}
