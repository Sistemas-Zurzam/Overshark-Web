<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cuentas de compradores: el límite es por correo + IP para frenar la fuerza bruta sin bloquear a otras personas.
        $byEmail = fn (Request $request) => mb_strtolower((string) $request->input('email')).'|'.$request->ip();
        RateLimiter::for('customer-login', fn (Request $request) => Limit::perMinute(5)->by($byEmail($request)));
        RateLimiter::for('customer-register', fn (Request $request) => Limit::perMinute(5)->by((string) $request->ip()));
        RateLimiter::for('customer-password', fn (Request $request) => Limit::perMinute(3)->by($byEmail($request)));
    }
}
