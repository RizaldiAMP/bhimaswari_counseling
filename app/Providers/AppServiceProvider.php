<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        // OPTIMASI: Cegah lazy loading (N+1 query problem) di non-production
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventAccessingMissingAttributes(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        // Rate Limiting (PRD Seksi 6.1)
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        if (app()->environment('testing')) {
            RateLimiter::for('login', fn (Request $request) => Limit::none());
            RateLimiter::for('register', fn (Request $request) => Limit::none());

            return;
        }

        // Global: 60 request per menit per IP
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Login: 5 percobaan / 10 menit per kombinasi IP+email, lock 15 menit
        RateLimiter::for('login', function (Request $request) {
            $key = $request->ip() . '|' . $request->input('email', '');

            return Limit::perMinutes(10, 5)->by($key);
        });

        // Register: 3 percobaan / jam per IP
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });
    }
}
