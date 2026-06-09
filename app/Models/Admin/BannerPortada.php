<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BannerPortada extends Model
{
    private const ACTIVE_CACHE_KEY = 'banners_portada.active';

    protected $table = 'banners_portada';

    protected $fillable = ['name', 'image_path', 'status', 'time', 'modo', 'buttons', 'buttons_position'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::ACTIVE_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::ACTIVE_CACHE_KEY));
    }

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'buttons' => 'array',
        ];
    }

    public static function activeForHome()
    {
        return Cache::remember(self::ACTIVE_CACHE_KEY, now()->addMinutes(30), fn () => static::query()
            ->where('status', true)
            ->whereNotNull('image_path')
            ->latest()
            ->get());
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? '/storage/'.ltrim($this->image_path, '/') : null;
    }
}
