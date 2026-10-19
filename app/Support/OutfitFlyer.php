<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Flyer de la página «Arma tu outfit». Se sube desde Admin → Arma tu outfit y se guarda en el disco
 * público (outfit/flyer.*); si no hay uno subido se usa el de public/images/outfit.
 */
class OutfitFlyer
{
    private const DIR = 'outfit';

    public static function url(): string
    {
        $path = self::stored();

        return $path
            ? '/storage/'.$path.'?v='.Storage::disk('public')->lastModified($path)
            : asset('images/outfit/flyer-arma-tu-outfit.webp');
    }

    public static function isCustom(): bool
    {
        return self::stored() !== null;
    }

    public static function store(UploadedFile $file): void
    {
        self::delete();
        $file->storeAs(self::DIR, 'flyer.'.$file->extension(), 'public');
    }

    public static function delete(): void
    {
        if ($path = self::stored()) {
            Storage::disk('public')->delete($path);
        }
    }

    private static function stored(): ?string
    {
        return collect(Storage::disk('public')->files(self::DIR))
            ->first(fn (string $file) => str_starts_with(basename($file), 'flyer.'));
    }
}
