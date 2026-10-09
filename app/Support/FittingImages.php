<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Imágenes de prenda para el probador virtual: una por producto y color, de frente y con fondo
 * transparente. Se guardan como PNG en el disco público (probador/...) y no requieren tabla propia.
 */
class FittingImages
{
    public static function path(string $productName, ?string $color): string
    {
        return 'probador/'.sha1(mb_strtoupper(trim($productName))).'/'.sha1(mb_strtoupper(trim((string) $color))).'.png';
    }

    /** @var array<string, true>|null Archivos existentes, leídos una vez por petición. */
    private static ?array $existing = null;

    public static function url(string $productName, ?string $color): ?string
    {
        self::$existing ??= array_fill_keys(Storage::disk('public')->allFiles('probador'), true);
        $path = self::path($productName, $color);

        return isset(self::$existing[$path])
            ? '/storage/'.$path.'?v='.Storage::disk('public')->lastModified($path)
            : null;
    }

    public static function store(UploadedFile $file, string $productName, ?string $color): void
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($image === false) {
            throw ValidationException::withMessages(['imagen_probador' => 'No pudimos leer la imagen.']);
        }

        if (! self::hasTransparentBackground($image)) {

            throw ValidationException::withMessages([
                'imagen_probador' => 'La imagen debe tener fondo transparente (PNG o WebP recortado), con la prenda de frente.',
            ]);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        imagepng($image, null, 7);
        $png = (string) ob_get_clean();

        Storage::disk('public')->put(self::path($productName, $color), $png);
        self::$existing = null;
    }

    public static function delete(string $productName, ?string $color): void
    {
        Storage::disk('public')->delete(self::path($productName, $color));
        self::$existing = null;
    }

    /**
     * Las cuatro esquinas deben ser (casi) transparentes: así se descarta una foto con fondo.
     */
    private static function hasTransparentBackground(\GdImage $image): bool
    {
        $width = imagesx($image) - 1;
        $height = imagesy($image) - 1;

        foreach ([[0, 0], [$width, 0], [0, $height], [$width, $height]] as [$x, $y]) {
            $alpha = (imagecolorat($image, $x, $y) >> 24) & 0x7F;

            if ($alpha < 110) {
                return false;
            }
        }

        return true;
    }
}
