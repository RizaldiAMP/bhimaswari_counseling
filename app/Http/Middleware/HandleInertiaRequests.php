<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'counselor_photo_url' => fn () => $user && $user->role === 'counselor'
                    ? $user->counselorProfile?->photo_url
                    : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'notifications' => [
                'unread_count' => fn () => $user?->unreadNotifications()->count() ?? 0,
                'items' => fn () => $user
                    ? $user->notifications()
                        ->latest()
                        ->limit(8)
                        ->get()
                        ->map(fn ($notification) => [
                            'id' => $notification->id,
                            'read_at' => $notification->read_at,
                            'created_at' => $notification->created_at,
                            'event' => $notification->data['event'] ?? null,
                            'title' => $notification->data['title'] ?? 'Notifikasi',
                            'message' => $notification->data['message'] ?? '',
                            'url' => $notification->data['url'] ?? null,
                        ])
                        ->values()
                    : [],
            ],
        ];
    }
}
