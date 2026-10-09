<?php

namespace App\Support;

/**
 * Color aproximado de una prenda a partir del nombre de color de ZAZU, para las ilustraciones
 * que reemplazan a la foto cuando el producto aún no tiene imagen.
 */
class GarmentSwatch
{
    private const COLORS = [
        'negro' => '#1b1f27', 'blanco' => '#e9ecef', 'perla' => '#e6e0d6', 'crema' => '#e6dcc4', 'hueso' => '#e4dccb',
        'azul' => '#1f3f8f', 'marino' => '#18233d', 'navy' => '#18233d', 'celeste' => '#8fb6dd', 'denim' => '#4f6d91',
        'verde' => '#5f7f5c', 'botella' => '#174a39', 'olivo' => '#5f6a3c', 'pacay' => '#8c9b73', 'militar' => '#4f5a3a',
        'rojo' => '#9b2333', 'vino' => '#6b1a2a', 'guinda' => '#6b1a2a', 'rosa' => '#e3a6b5', 'palo rosa' => '#d9a5a5',
        'gris' => '#8a8f98', 'plomo' => '#8a8f98', 'melange' => '#a9adb4', 'cemento' => '#9b9b95', 'humo' => '#6f747c',
        'beige' => '#cdb894', 'arena' => '#cbb48c', 'khaki' => '#b6a27a', 'camel' => '#b98c5a', 'marron' => '#5a3b28',
        'chocolate' => '#4a2f22', 'mostaza' => '#c49a2c', 'amarillo' => '#e1c542', 'naranja' => '#d9772b', 'coral' => '#e57a6b',
        'morado' => '#5d3f8f', 'lila' => '#a58fcf', 'lavanda' => '#b7a8dd', 'turquesa' => '#2bb3b1', 'menta' => '#9fd8c2',
    ];

    public static function hex(?string $colorName): string
    {
        $name = mb_strtolower((string) $colorName);

        foreach (self::COLORS as $key => $hex) {
            if ($name !== '' && str_contains($name, $key)) {
                return $hex;
            }
        }

        return '#3b4c6b';
    }

    public static function garmentFor(?string $category): string
    {
        return match ($category) {
            'camisas' => 'camisa',
            'pantalones' => 'pantalon',
            'accesorios' => 'media',
            default => 'polo',
        };
    }
}
