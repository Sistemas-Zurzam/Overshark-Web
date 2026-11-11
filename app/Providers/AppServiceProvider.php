<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        // Además del límite por correo, un tope por IP: sin él se podrían probar muchos correos distintos desde una sola dirección.
        RateLimiter::for('customer-login', fn (Request $request) => [
            Limit::perMinute(5)->by($byEmail($request)),
            Limit::perMinute(30)->by('ip|'.$request->ip()),
        ]);
        RateLimiter::for('customer-register', fn (Request $request) => Limit::perMinute(5)->by((string) $request->ip()));
        RateLimiter::for('customer-password', fn (Request $request) => Limit::perMinute(3)->by($byEmail($request)));

        // El modo simulación solo existe en local y testing: en cualquier otro entorno se apaga y se deja constancia.
        if (config('services.payments.simulate') && ! $this->app->environment(['local', 'testing'])) {
            config(['services.payments.simulate' => false]);
            Log::critical('PAYMENTS_SIMULATE=true se ignoró: solo se permite con APP_ENV=local o testing (APP_ENV actual: '.$this->app->environment().').');
        }

        // Los enlaces de los correos (verificar correo, recuperar contraseña) salen de APP_URL y no del encabezado Host
        // que manda quien hace la petición (envenenamiento de enlaces).
        if ($this->app->isProduction() && filled(config('app.url'))) {
            URL::forceRootUrl(config('app.url'));

            if (str_starts_with((string) config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
