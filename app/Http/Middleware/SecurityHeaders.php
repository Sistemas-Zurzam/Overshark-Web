<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Encabezados de seguridad de todas las respuestas web (se suman a los que ponga el servidor):
 *  - nosniff: el navegador no adivina el tipo de un archivo subido.
 *  - X-Frame-Options: el sitio no se puede incrustar en otra página (clickjacking).
 *  - Referrer-Policy y Permissions-Policy: no se filtran direcciones completas ni se pide cámara o micrófono.
 *  - HSTS: solo en producción y sobre HTTPS, un año, sin subdominios (no obliga a nada fuera de este sitio).
 *  - El panel (/admin) nunca se guarda en caché del navegador: tiene datos personales de clientes.
 * No incluye Content-Security-Policy: la tienda usa scripts en línea y servicios externos; se agrega después con una lista medida.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=()');

        if ($request->isSecure() && app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        if ($request->is('admin', 'admin/*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
