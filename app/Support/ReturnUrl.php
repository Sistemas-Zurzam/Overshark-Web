<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * A dónde volver después de iniciar sesión o registrarse. Solo se recuerda una página de esta misma tienda
 * (el Referer lo manda el navegador y puede ser de otro sitio) y nunca las pantallas de acceso.
 */
class ReturnUrl
{
    private const AUTH_PATHS = ['registro', 'iniciar-sesion', 'cerrar-sesion', 'recuperar-contrasena', 'restablecer-contrasena'];

    public static function remember(Request $request): void
    {
        if (session()->has('url.intended')) {
            return;
        }

        // Solo el Referer real: url()->previous() cae en la última página de la sesión aunque no se haya venido de ella.
        $previous = (string) $request->headers->get('referer');
        $parts = parse_url($previous);

        if (($parts['host'] ?? null) !== $request->getHost()) {
            return;
        }

        $path = trim((string) ($parts['path'] ?? ''), '/');

        foreach (self::AUTH_PATHS as $authPath) {
            if (str_starts_with($path, $authPath)) {
                return;
            }
        }

        session()->put('url.intended', $previous);
    }
}
