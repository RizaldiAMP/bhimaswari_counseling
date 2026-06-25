<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Pastikan response berupa objek yang dapat diatur headernosnya
        if (method_exists($response, 'header')) {
            $response->header('X-Frame-Options', 'SAMEORIGIN');
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('X-XSS-Protection', '1; mode=block');
            if ($request->isSecure()) {
                $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }
            
            // Content-Security-Policy (CSP) yang lebih longgar
            // Hanya aktif di production dan mengizinkan resource pihak ketiga (CDN) dengan HTTPS
            if (app()->environment('production')) {
                $csp = "default-src 'self' https:; " .
                       "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; " .
                       "style-src 'self' 'unsafe-inline' https:; " .
                       "font-src 'self' data: https:; " .
                       "img-src 'self' data: blob: https:; " .
                       "media-src 'self' data: blob: https:; " .
                       "connect-src 'self' ws: wss: https:;";
                
                $response->header('Content-Security-Policy', $csp);
            }
            
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        return $response;
    }
}
