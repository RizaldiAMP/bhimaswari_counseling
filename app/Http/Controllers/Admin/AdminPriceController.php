<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServicePrice;
use App\Models\ServicePriceHistory;
use App\Services\PriceManagementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminPriceController extends Controller
{
    public function index()
    {
        $prices = ServicePrice::orderBy('service_type')
            ->orderBy('practitioner_type')
            ->orderBy('duration_minutes')
            ->get();

        $history = ServicePriceHistory::with('changedByUser:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Prices', [
            'prices' => $prices,
            'history' => $history,
        ]);
    }

    public function store(Request $request, PriceManagementService $priceManagementService)
    {
        $validated = $request->validate([
            'service_type' => ['required', 'in:chat,online,offline'],
            'practitioner_type' => ['required', 'in:psychologist,counselor'],
            'duration_minutes' => ['required', 'integer', 'min:15'],
            'price' => ['required', 'integer', 'min:0'],
        ]);

        // Check for existing duplicate combo
        $exists = ServicePrice::where('service_type', $validated['service_type'])
            ->where('practitioner_type', $validated['practitioner_type'])
            ->where('duration_minutes', $validated['duration_minutes'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'service_type' => 'Kombinasi layanan, tipe praktisi, dan durasi ini sudah ada. Silakan edit harga yang sudah ada.',
            ]);
        }

        $priceManagementService->createPrice($validated, $request->user()->id);

        return back()->with('success', 'Harga layanan berhasil ditambahkan.');
    }

    public function update(Request $request, ServicePrice $price, PriceManagementService $priceManagementService)
    {
        $validated = $request->validate([
            'price' => ['required', 'integer', 'min:0'],
            'change_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $priceManagementService->updatePrice(
            price: $price,
            newPrice: $validated['price'],
            reason: $validated['change_reason'] ?? null,
            adminId: $request->user()->id,
        );

        return back()->with('success', 'Harga layanan berhasil diperbarui.');
    }

    public function toggleActive(ServicePrice $price, PriceManagementService $priceManagementService)
    {
        $isActive = $priceManagementService->toggleActive($price);

        return back()->with('success', $isActive ? 'Harga diaktifkan.' : 'Harga dinonaktifkan.');
    }

    public function destroy(ServicePrice $price, PriceManagementService $priceManagementService)
    {
        try {
            $priceManagementService->deletePrice($price);
            return back()->with('success', 'Harga layanan berhasil dihapus.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }
}
