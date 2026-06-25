<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminClientController extends Controller
{
    public function index()
    {
        $clients = User::where('role', 'client')
            ->withCount('bookings')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Clients', [
            'clients' => $clients,
        ]);
    }

    public function show(User $client)
    {
        abort_if($client->role !== 'client', 404);

        $bookings = $client->bookings()
            ->with(['counselor:id,name', 'payment:id,booking_id,status'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($b) => [
                'id'             => $b->id,
                'service_type'   => $b->service_type,
                'duration'       => $b->duration_minutes,
                'price'          => $b->price_at_booking,
                'status'         => $b->status,
                'schedule_start' => $b->schedule_start?->toISOString(),
                'schedule_end'   => $b->schedule_end?->toISOString(),
                'counselor_name' => $b->counselor?->name ?? '-',
                'payment_status' => $b->payment?->status ?? null,
                'created_at'     => $b->created_at->toISOString(),
            ]);

        return response()->json([
            'client'   => [
                'id'    => $client->id,
                'name'  => $client->name,
                'email' => $client->email,
            ],
            'bookings' => $bookings,
        ]);
    }
}
