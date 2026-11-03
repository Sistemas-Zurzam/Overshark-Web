<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Miniaturas de fotos del disco público para tarjetas pequeñas.
 * Reducir una foto grande de tela con textura fina (waffle, piqué) directo a ~200 px hace que el
 * navegador genere rayas (moiré). Aquí se reduce por pasos a la mitad y con un suavizado previo,
 * y se guarda en thumbs/{ancho}/… para reutilizarla. Si algo falla se devuelve la URL original.
 */
class Thumb
{
    public static function url(?string $url, int $width = 480): ?string
    {
        if (! $url || ! str_starts_with($url, '/storage/')) {
            return $url;
        }

        $source = ltrim(substr(strtok($url, '?'), strlen('/storage/')), '/');
        $disk = Storage::disk('public');

        if (str_starts_with($source, 'thumbs/') || ! $disk->exists($source)) {
            return $url;
        }

        $target = "thumbs/{$width}/".preg_replace('/\.\w+$/', '.jpg', $source);

        if (! $disk->exists($target) || $disk->lastModified($target) < $disk->lastModified($source)) {
            try {
                if (! self::make($disk->path($source), $disk->path($target), $width)) {
                    return $url;
                }
            } catch (Throwable) {
                return $url;
            }
        }

        return '/storage/'.$target.'?v='.$disk->lastModified($target);
    }

    private static function make(string $from, string $to, int $width): bool
    {
        $image = @imagecreatefromstring((string) file_get_contents($from));

        if ($image === false) {
            return false;
        }

        $w = imagesx($image);
        $h = imagesy($image);

        if ($w <= $width) {
            imagedestroy($image);

            return false; // ya es pequeña: se usa la original
        }

        // Reducción por pasos a la mitad (cada paso promedia píxeles y elimina la textura fina).
        while ($w / 2 >= $width * 1.5) {
            $image = self::resample($image, (int) round($w / 2), (int) round($h / 2));
            $w = imagesx($image);
            $h = imagesy($image);
        }

        // Suavizado leve antes del último paso para que no queden rayas.
        imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        $image = self::resample($image, $width, (int) round($h * $width / $w));

        if (! is_dir(dirname($to))) {
            mkdir(dirname($to), 0775, true);
        }

        $ok = imagejpeg($image, $to, 86);
        imagedestroy($image);

        return $ok;
    }

    private static function resample(\GdImage $image, int $w, int $h): \GdImage
    {
        $out = imagecreatetruecolor($w, $h);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $image, 0, 0, 0, 0, $w, $h, imagesx($image), imagesy($image));
        imagedestroy($image);

        return $out;
    }
}
