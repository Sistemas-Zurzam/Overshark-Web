<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Textos e imágenes editables de una página institucional (ver App\Support\AboutPage). */
class PageContent extends Model
{
    protected $fillable = ['slug', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    protected static function booted(): void
    {
        static::saved(fn (self $page) => Cache::forget(self::cacheKey($page->slug)));
        static::deleted(fn (self $page) => Cache::forget(self::cacheKey($page->slug)));
    }

    /** Contenido guardado de la página (vacío si nunca se editó). */
    public static function contentFor(string $slug): array
    {
        return Cache::rememberForever(self::cacheKey($slug), fn () => static::query()->where('slug', $slug)->value('content') ?? []);
    }

    private static function cacheKey(string $slug): string
    {
        return "page_content.{$slug}";
    }
}
