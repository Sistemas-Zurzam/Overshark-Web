<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MetodoPago extends Model
{
    private const ACTIVE_CACHE_KEY = 'metodos_pago.active';

    protected $table = 'metodos_pago';

    protected $fillable = ['name', 'imagen', 'status'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::ACTIVE_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::ACTIVE_CACHE_KEY));
    }

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public static function active()
    {
        return Cache::remember(self::ACTIVE_CACHE_KEY, now()->addMinutes(30), fn () => static::query()
            ->where('status', true)
            ->latest()
            ->get());
    }

    public function imageUrl(): ?string
    {
        return $this->imagen ? '/storage/'.ltrim($this->imagen, '/') : null;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
