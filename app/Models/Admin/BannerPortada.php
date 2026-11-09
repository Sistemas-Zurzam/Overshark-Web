<?php

namespace App\Models\Admin;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BannerPortada extends Model
{
    private const ACTIVE_CACHE_KEY = 'banners_portada.active';

    public const MAX_ACTIVE_PER_BRAND = 5;

    protected $table = 'banners_portada';

    protected $fillable = ['brand_id', 'name', 'image_path', 'status', 'time', 'modo', 'buttons', 'buttons_position', 'starts_at', 'ends_at', 'sort_order'];

    protected static function booted(): void
    {
        static::saved(fn () => self::flushActiveCache());
        static::deleted(fn () => self::flushActiveCache());
    }

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'buttons' => 'array',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Borra los banners activos guardados en memoria, de todas las marcas. Antes solo se borraba una clave que nadie
     * usaba, así que un banner nuevo o cambiado tardaba hasta 30 minutos en verse en la portada.
     */
    public static function flushActiveCache(): void
    {
        Cache::forget(self::ACTIVE_CACHE_KEY);
        Cache::forget(self::ACTIVE_CACHE_KEY.'.all');

        foreach (\App\Models\Brand::query()->pluck('id') as $brandId) {
            Cache::forget(self::ACTIVE_CACHE_KEY.'.'.$brandId);
        }
    }

    public static function activeForHome(?int $brandId = null)
    {
        return Cache::remember(self::ACTIVE_CACHE_KEY.'.'.($brandId ?? 'all'), now()->addMinutes(30), fn () => static::query()
            ->where('status', true)
            ->whereNotNull('image_path')
            ->when($brandId !== null, fn ($query) => $query->where('brand_id', $brandId))
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()->toDateString()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()->toDateString()))
            ->orderBy('sort_order')
            ->latest()
            ->get());
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? '/storage/'.ltrim($this->image_path, '/') : null;
    }

    /** Estado visible en el admin: inactivo (apagado a mano), programado, finalizado o activo. */
    public function scheduleState(): string
    {
        if (! $this->status) {
            return 'inactivo';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'programado';
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'finalizado';
        }

        return 'activo';
    }

    public function scheduleLabel(): string
    {
        return match ($this->scheduleState()) {
            'inactivo' => 'Inactivo',
            'programado' => 'Programado · '.$this->starts_at->format('d/m').($this->ends_at ? ' – '.$this->ends_at->format('d/m') : ''),
            'finalizado' => 'Finalizó el '.$this->ends_at->format('d/m/Y'),
            default => $this->starts_at || $this->ends_at
                ? 'Activo · hasta '.($this->ends_at?->format('d/m/Y') ?? 'siempre')
                : 'Activo · siempre',
        };
    }

    /** Primer enlace configurado en los botones del banner, si existe. */
    public function primaryLink(): ?string
    {
        foreach ($this->buttons ?? [] as $button) {
            if (! empty($button['url'])) {
                return $button['url'];
            }
        }

        return null;
    }
}
