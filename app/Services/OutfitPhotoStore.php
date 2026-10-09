<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Fotos y resultados del probador. Se guardan en el disco privado "local" (storage/app/private/outfit),
 * con nombres aleatorios, y nunca se publican: solo se sirven a la sesión que las subió.
 */
class OutfitPhotoStore
{
    public const DIRECTORY = 'outfit';

    /** Horas que se conservan los archivos antes de que outfit:purge los elimine. */
    public const RETENTION_HOURS = 24;

    private const MAX_SIDE = 2048;

    public function store(UploadedFile $file): string
    {
        $path = self::DIRECTORY.'/photos/'.Str::random(40).'.jpg';

        // Se re-codifica para descartar metadatos (EXIF, ubicación GPS) y limitar el tamaño.
        if (function_exists('imagecreatefromstring')) {
            Storage::disk('local')->put($path, $this->reencode($file));

            return $path;
        }

        $path = self::DIRECTORY.'/photos/'.Str::random(40).'.'.$file->guessExtension();
        Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && str_starts_with($path, self::DIRECTORY.'/')) {
            Storage::disk('local')->delete($path);
        }
    }

    public function exists(?string $path): bool
    {
        return $path !== null && str_starts_with($path, self::DIRECTORY.'/') && Storage::disk('local')->exists($path);
    }

    public function absolutePath(string $path): string
    {
        return Storage::disk('local')->path($path);
    }

    /**
     * Elimina los archivos del probador más antiguos que la retención y devuelve cuántos borró.
     */
    public function purgeOlderThan(int $hours = self::RETENTION_HOURS): int
    {
        $disk = Storage::disk('local');
        $limit = now()->subHours($hours)->getTimestamp();
        $deleted = 0;

        foreach ($disk->allFiles(self::DIRECTORY) as $file) {
            if ($disk->lastModified($file) < $limit && $disk->delete($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private function reencode(UploadedFile $file): string
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($image === false) {
            throw ValidationException::withMessages(['photo' => 'No pudimos leer la imagen. Prueba con otra foto.']);
        }

        $image = $this->applyExifOrientation($image, $file);
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, self::MAX_SIDE / max($width, $height));
        $targetWidth = (int) round($width * $scale);
        $targetHeight = (int) round($height * $scale);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 88);
        $jpeg = (string) ob_get_clean();

        imagedestroy($image);
        imagedestroy($canvas);

        return $jpeg;
    }

    private function applyExifOrientation(\GdImage $image, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || $file->getMimeType() !== 'image/jpeg') {
            return $image;
        }

        $orientation = (int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated === false ? $image : $rotated;
    }
}
