<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Cek apakah user yang sedang login masih aktif.
     * Jika admin menonaktifkan akun saat user sedang login,
     * middleware ini akan auto-logout pada request berikutnya.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Akun Anda telah dinonaktifkan oleh administrator.');
        }

        return $next($request);
    }
}
