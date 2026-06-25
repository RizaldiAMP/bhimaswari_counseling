<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ServicePrice;
use App\Models\ServicePriceHistory;

class PriceManagementService
{
    public function createPrice(array $data, int $adminId): ServicePrice
    {
        $price = ServicePrice::create(array_merge($data, ['is_active' => true]));

        ServicePriceHistory::create([
            'service_price_id' => $price->id,
            'changed_by' => $adminId,
            'old_price' => 0,
            'new_price' => $price->price,
            'change_reason' => 'Harga baru dibuat',
        ]);

        return $price;
    }

    public function updatePrice(ServicePrice $price, int $newPrice, ?string $reason, int $adminId): void
    {
        $oldPrice = $price->price;

        $price->update(['price' => $newPrice]);

        ServicePriceHistory::create([
            'service_price_id' => $price->id,
            'changed_by' => $adminId,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'change_reason' => $reason ?? 'Perubahan harga',
        ]);
    }

    public function toggleActive(ServicePrice $price): bool
    {
        $price->update(['is_active' => ! $price->is_active]);

        return $price->is_active;
    }

    public function deletePrice(ServicePrice $price): void
    {
        if ($price->bookings()->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'delete' => 'Tidak dapat menghapus harga ini karena sudah digunakan pada transaksi.',
            ]);
        }

        $price->delete();
    }
}
