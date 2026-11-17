<?php

namespace App\Providers;

use App\Support\Integrations;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Admin → Integraciones: aplica las conexiones guardadas (CRM, Zazu) sobre el .env al arrancar y antes de cada trabajo
 * de la cola (el worker vive horas: así toma los cambios del panel sin reiniciarlo), y limita la API de Overshark.
 */
class IntegrationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $apply = function (): void {
            try {
                Integrations::apply();
            } catch (\Throwable) {
                // Antes de migrar (instalación nueva o artisan migrate) la tabla aún no existe: se usa el .env.
            }
        };

        $apply();
        Queue::before(fn () => $apply());

        // 120 solicitudes por minuto por clave API (o por IP si no envía clave).
        RateLimiter::for('overshark-api', function (Request $request) {
            $key = $request->bearerToken() ?: (string) $request->header('X-Api-Key');

            return Limit::perMinute(120)->by($key !== '' ? 'key:'.hash('sha256', $key) : 'ip:'.$request->ip());
        });
    }
}
