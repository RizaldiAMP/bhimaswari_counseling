<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $redirectRoute = match ($request->user()->role) {
            'admin' => route('admin.dashboard', absolute: false),
            'counselor' => route('counselor.dashboard', absolute: false),
            default => route('client.dashboard', absolute: false),
        };

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($redirectRoute);
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
