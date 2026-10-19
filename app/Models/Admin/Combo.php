<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Combo extends Model
{
    private const ACTIVE_CACHE_KEY = 'combos.active';

    protected $fillable = [
        'source_key',
        'name',
        'brand',
        'modality',
        'price',
        'imagen',
        'items',
        'selection_mode',
        'selection_limit',
        'notes',
        'status',
        'url',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::ACTIVE_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::ACTIVE_CACHE_KEY));
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'items' => 'array',
            'selection_limit' => 'integer',
            'status' => 'boolean',
        ];
    }

    public static function activeForMenu()
    {
        return Cache::remember(self::ACTIVE_CACHE_KEY, now()->addMinutes(30), fn () => static::query()
            ->where('status', true)
            ->where(fn ($q) => $q->whereNull('modality')->orWhere('modality', '!=', 'Outfit'))
            ->whereNotNull('imagen')
            ->latest()
            ->get());
    }

    public function imageUrl(): ?string
    {
        if (! $this->imagen) {
            return asset('images/default-hero-banner.png');
        }

        if (filter_var($this->imagen, FILTER_VALIDATE_URL)) {
            return $this->imagen;
        }

        if (str_starts_with($this->imagen, 'images/')) {
            return asset($this->imagen);
        }

        return '/storage/'.ltrim($this->imagen, '/');
    }

    public function displayItems(): \Illuminate\Support\Collection
    {
        return collect($this->items ?? []);
    }

    public function quantityLabel(): string
    {
        if ($this->selection_mode === 'choice' && $this->selection_limit) {
            return "Elige {$this->selection_limit} unidades";
        }

        // Las filas con la misma "prenda" (grupo) son alternativas: cuentan una sola vez.
        $quantity = $this->displayItems()
            ->groupBy(fn (array $item) => ! empty($item['grupo']) ? 'grupo:'.mb_strtoupper($item['grupo']) : 'item:'.spl_object_id((object) $item))
            ->sum(fn ($rows) => ! empty($rows->first()['grupo']) ? $rows->max('cantidad') : $rows->sum('cantidad'));

        if ($quantity === 0 && $this->selection_limit) {
            return "{$this->selection_limit} unidades";
        }

        return $quantity.' unidades';
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'combo_producto')->withPivot('cantidad');
    }
}
