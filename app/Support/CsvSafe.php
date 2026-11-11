<?php

namespace App\Support;

/**
 * Escritura segura de CSV para el panel. Las exportaciones incluyen textos que escribe el público (nombres, correos,
 * productos): una celda que empieza con = + - @ se ejecuta como fórmula al abrir el archivo en Excel o Sheets
 * (inyección de fórmulas). Se les antepone una comilla simple para que se lean siempre como texto.
 */
class CsvSafe
{
    private const TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    public static function cell(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], self::TRIGGERS, true)) {
            return "'".$value;
        }

        return $value;
    }

    /** @param  resource  $handle */
    public static function put($handle, array $row): int|false
    {
        return fputcsv($handle, array_map(self::cell(...), $row));
    }
}
