<?php

namespace App\Http\Middleware;

use App\Models\Admin\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API de Overshark (/api/v1): autentica con una clave de Admin → Integraciones
 * (Authorization: Bearer osk_... o X-Api-Key: osk_...).
 *
 * api.key:permiso1,permiso2 → la clave debe tener alguno de esos permisos.
 */
class AuthenticateApiKey
{
    public const ATTRIBUTE = 'api_key';

    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $key = ApiKey::findActive((string) ($request->bearerToken() ?: $request->header('X-Api-Key')));
        if (! $key) {
            return response()->json(['message' => 'Clave API inválida o revocada.'], 401);
        }
        if ($abilities && ! $key->allows(...$abilities)) {
            return response()->json(['message' => 'La clave API no tiene permiso para esta operación ('.implode(' o ', $abilities).').'], 403);
        }

        // last_used_at como mucho una vez por minuto.
        if (! $key->last_used_at || $key->last_used_at->lt(now()->subMinute())) {
            $key->forceFill(['last_used_at' => now()])->saveQuietly();
        }
        $request->attributes->set(self::ATTRIBUTE, $key);

        return $next($request);
    }
}
