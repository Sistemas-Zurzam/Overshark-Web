<?php

namespace App\Support;

/**
 * Formato de los documentos de identidad que piden el checkout y el Libro de Reclamaciones.
 * El navegador aplica las mismas reglas al escribir (resources/js/app.js, data-doc-number).
 */
class DocumentNumber
{
    public const FORMATS = [
        'DNI' => ['/^\d{8}$/', 'El DNI tiene 8 dígitos.'],
        'CE' => ['/^[A-Za-z0-9]{9,12}$/', 'El carné de extranjería tiene entre 9 y 12 caracteres.'],
        'RUC' => ['/^(10|15|17|20)\d{9}$/', 'El RUC tiene 11 dígitos y empieza con 10, 15, 17 o 20.'],
        'Pasaporte' => ['/^[A-Za-z0-9]{6,12}$/', 'El pasaporte tiene entre 6 y 12 letras o números.'],
    ];

    /** Regla de validación para el número según el tipo elegido. */
    public static function rule(?string $type): string
    {
        return 'regex:'.(self::FORMATS[$type][0] ?? self::FORMATS['DNI'][0]);
    }

    public static function message(?string $type): string
    {
        return self::FORMATS[$type][1] ?? self::FORMATS['DNI'][1];
    }
}
