<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): Response
    {
        $redirect = $request->query('redirect');

        if (is_string($redirect) && Str::startsWith($redirect, '/')) {
            $request->session()->put('url.intended', $redirect);
        }

        return Inertia::render('Auth/Register', [
            'redirect' => $request->session()->get('url.intended'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'whatsapp' => ['required', 'string', 'max:20', 'regex:/^(?:\+62|0)\d{9,13}$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp' => $request->whatsapp,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $redirectRoute = match ($user->role) {
            'admin' => route('admin.dashboard', absolute: false),
            'counselor' => route('counselor.dashboard', absolute: false),
            default => route('client.dashboard', absolute: false),
        };

        return redirect()->intended($redirectRoute);
    }
}
