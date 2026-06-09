<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Combo extends Model
{
    private const ACTIVE_CACHE_KEY = 'combos.active';

    protected $fillable = ['name', 'imagen', 'status', 'url'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::ACTIVE_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::ACTIVE_CACHE_KEY));
    }

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public static function activeForMenu()
    {
        return Cache::remember(self::ACTIVE_CACHE_KEY, now()->addMinutes(30), fn () => static::query()
            ->where('status', true)
            ->whereNotNull('imagen')
            ->latest()
            ->get());
    }

    public function imageUrl(): ?string
    {
        return $this->imagen ? '/storage/'.ltrim($this->imagen, '/') : null;
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'combo_producto')->withPivot('cantidad');
    }
}
