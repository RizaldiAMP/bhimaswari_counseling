<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $redirectRoute = match ($request->user()->role) {
            'admin' => route('admin.dashboard', absolute: false),
            'counselor' => route('counselor.dashboard', absolute: false),
            default => route('client.dashboard', absolute: false),
        };

        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended($redirectRoute)
                    : Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }
}
