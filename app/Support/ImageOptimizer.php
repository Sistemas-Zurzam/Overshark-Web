<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guarda las imágenes que sube el admin ya optimizadas: lado mayor limitado y WebP con calidad 82, que suele
 * pesar entre 5 y 10 veces menos que el PNG original sin diferencia visible en la tienda. Si GD no puede con el
 * archivo (formato raro, imagen enorme para la memoria disponible, WebP más pesado que el original), se guarda
 * el original tal cual: subir una imagen nunca debe fallar por optimizarla.
 */
class ImageOptimizer
{
    public const PRODUCT_MAX_SIDE = 1600;

    public const BANNER_MAX_SIDE = 2400;

    public const QUALITY = 82;

    public static function storeUploaded(UploadedFile $file, string $directory, int $maxSide = self::PRODUCT_MAX_SIDE, string $disk = 'public'): string
    {
        return self::storeContents(
            (string) file_get_contents($file->getRealPath()),
            $directory,
            $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'img',
            $maxSide,
            $disk,
        );
    }

    /** $extension es la del original, para guardarlo tal cual si no se puede optimizar. */
    public static function storeContents(string $contents, string $directory, string $extension = 'img', int $maxSide = self::PRODUCT_MAX_SIDE, string $disk = 'public'): string
    {
        $optimized = self::optimize($contents, $maxSide);
        $directory = trim($directory, '/');

        if ($optimized !== null) {
            $path = $directory.'/'.Str::uuid().'.webp';
            $contents = $optimized;
        } else {
            $path = $directory.'/'.Str::uuid().'.'.$extension;
        }

        if (! Storage::disk($disk)->put($path, $contents)) {
            throw new \RuntimeException('No se pudo escribir la imagen en el almacenamiento.');
        }

        return $path;
    }

    /** El WebP optimizado, o null si hay que conservar el original. */
    public static function optimize(string $contents, int $maxSide = self::PRODUCT_MAX_SIDE): ?string
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $info = @getimagesizefromstring($contents);
        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        [$width, $height] = $info;
        if (! self::fitsInMemory($width, $height)) {
            return null;
        }

        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            return null;
        }

        try {
            $image = self::orient($image, $contents, $info[2]);
            $width = imagesx($image);
            $height = imagesy($image);

            if (max($width, $height) > $maxSide) {
                $resized = $width >= $height
                    ? imagescale($image, $maxSide, -1, IMG_BICUBIC)
                    : imagescale($image, (int) round($width * $maxSide / $height), $maxSide, IMG_BICUBIC);
                if ($resized !== false) {
                    imagedestroy($image);
                    $image = $resized;
                }
            }

            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            ob_start();
            $saved = imagewebp($image, null, self::QUALITY);
            $webp = (string) ob_get_clean();
        } finally {
            imagedestroy($image);
        }

        // Un WebP más pesado que el original (fotos ya muy comprimidas) no aporta nada.
        return $saved && $webp !== '' && strlen($webp) < strlen($contents) ? $webp : null;
    }

    /** GD descomprime a ~4 bytes por píxel y trabaja con una copia: no arriesgar un error fatal por memoria. */
    private static function fitsInMemory(int $width, int $height): bool
    {
        $limit = self::memoryLimit();

        return $limit <= 0 || ($width * $height * 4 * 2.5) + memory_get_usage() < $limit;
    }

    private static function memoryLimit(): int
    {
        $value = trim((string) ini_get('memory_limit'));
        if ($value === '' || $value === '-1') {
            return 0;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /** Las fotos de celular traen la rotación en EXIF; al reescribirlas hay que aplicarla. */
    private static function orient(\GdImage $image, string $contents, int $type): \GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($contents));
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        if ($rotated === false) {
            return $image;
        }
        imagedestroy($image);

        return $rotated;
    }
}
