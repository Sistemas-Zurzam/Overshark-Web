<?php

namespace App\Support;

use RuntimeException;

/** Cobertura de delivery dibujada en el mapa del checkout y comprobada también en el servidor. */
final class DeliveryCoverage
{
    private static ?array $geometry = null;

    public static function geometry(): array
    {
        if (self::$geometry !== null) {
            return self::$geometry;
        }

        $path = resource_path('data/delivery-coverage.json');
        $geometry = json_decode((string) file_get_contents($path), true);
        if (! is_array($geometry['coverage']['outer'] ?? null)) {
            throw new RuntimeException('No se pudo cargar el mapa de cobertura de delivery.');
        }

        return self::$geometry = $geometry;
    }

    /** Google/KML usa [longitud, latitud]; el formulario recibe latitud y longitud por separado. */
    public static function contains(float $latitude, float $longitude): bool
    {
        $geometry = self::geometry();
        $point = [$longitude, $latitude];
        $coverage = $geometry['coverage'];

        if (! self::insideRing($point, $coverage['outer'])) {
            return false;
        }

        foreach ($coverage['holes'] ?? [] as $hole) {
            if (self::insideRing($point, $hole)) {
                return false;
            }
        }

        foreach ($geometry['exclusions'] ?? [] as $exclusion) {
            if (self::insideRing($point, $exclusion)) {
                return false;
            }
        }

        return true;
    }

    private static function insideRing(array $point, array $ring): bool
    {
        [$x, $y] = $point;
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];

            if (self::onSegment($x, $y, $xi, $yi, $xj, $yj)) {
                return true;
            }

            if (($yi > $y) !== ($yj > $y) && $x < (($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: PHP_FLOAT_MIN)) + $xi) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    private static function onSegment(float $x, float $y, float $x1, float $y1, float $x2, float $y2): bool
    {
        $cross = ($y - $y1) * ($x2 - $x1) - ($x - $x1) * ($y2 - $y1);
        if (abs($cross) > 1e-8) {
            return false;
        }

        return $x >= min($x1, $x2) - 1e-8 && $x <= max($x1, $x2) + 1e-8
            && $y >= min($y1, $y2) - 1e-8 && $y <= max($y1, $y2) + 1e-8;
    }
}
