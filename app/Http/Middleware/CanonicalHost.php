<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Una sola dirección para Google: www.overshark.com.pe redirige (301) a la de APP_URL (overshark.com.pe) con la misma
 * ruta. Solo GET/HEAD: los avisos por POST (webhooks de pagos) se atienden en cualquiera de las dos.
 */
class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonical = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($canonical && $request->isMethodCacheable() && strcasecmp($request->getHost(), 'www.'.$canonical) === 0) {
            $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: $request->getScheme();

            return redirect()->to($scheme.'://'.$canonical.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
