<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Nombre de producto tal como se muestra en la web. En Zazu algunos productos se llaman distinto a como
 * se venden (p. ej. "BABY TY" es la "Baby Tee"): el nombre de Zazu se conserva en la base, porque con
 * él se buscan y agrupan las variantes, y solo se corrige al mostrarlo.
 * Las vistas en JavaScript aplican la misma corrección (resources/js/product-name.js).
 */
class ProductName
{
    /** Nombre de Zazu (regex, sin distinguir mayúsculas) => nombre en la web. */
    private const RENAMES = [
        '/\bBABY\s+TY\b/iu' => 'BABY TEE',
    ];

    /** Nombre corregido, conservando mayúsculas (p. ej. "BABY TY MANGA" → "BABY TEE MANGA"). */
    public static function display(?string $name): string
    {
        $name = (string) $name;

        foreach (self::RENAMES as $pattern => $replacement) {
            $name = preg_replace($pattern, $replacement, $name) ?? $name;
        }

        return $name;
    }

    /** Nombre corregido en formato título (p. ej. "Baby Tee Manga"). */
    public static function title(?string $name): string
    {
        return Str::title(mb_strtolower(self::display($name)));
    }
}
