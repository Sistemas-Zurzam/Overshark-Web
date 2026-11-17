<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateJwt;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // API de Overshark para sistemas externos (/api/v1, claves de Admin → Integraciones).
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.auth' => AuthenticateJwt::class,
            'api.key' => \App\Http\Middleware\AuthenticateApiKey::class,
        ]);

        // Resultado del pago de Izipay: lo envía su formulario (sin token CSRF) o su servidor; la ruta lo acepta solo
        // con la firma kr-hash válida.
        $middleware->validateCsrfTokens(except: ['checkout/pedido/*/pagado', 'webhooks/izipay']);

        // Encabezados de seguridad en toda la web, el panel incluido.
        $middleware->web(append: [\App\Http\Middleware\SecurityHeaders::class]);

        // www.overshark.com.pe → overshark.com.pe (301), antes de abrir sesión: Google ve una sola dirección.
        $middleware->web(prepend: [\App\Http\Middleware\CanonicalHost::class]);

        // Compradores ya conectados que abren /iniciar-sesion o /registro van a su cuenta.
        $middleware->redirectUsersTo(fn () => route('customer.account'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // La API de integraciones siempre responde JSON (404, 422 de validación, 429), aunque no envíen Accept.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // Una subida más grande que post_max_size de PHP llega aquí sin pasar por la validación del formulario.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return back()->withErrors(['images' => 'Las imágenes pesan demasiado para un solo envío. Sube menos archivos a la vez o pide subir el límite de subida del servidor (post_max_size y upload_max_filesize).']);
        });
    })->create();
