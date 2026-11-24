<?php

namespace App\Models\Admin;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Combo extends Model
{
    private const ACTIVE_CACHE_KEY = 'combos.active';

    protected $fillable = [
        'source_key',
        'name',
        'brand',
        'brand_id',
        'modality',
        'price',
        'compare_at_price',
        'imagen',
        'imagen_movil',
        'items',
        'selection_mode',
        'selection_limit',
        'notes',
        'status',
        'is_draft',
        'url',
    ];

    protected static function booted(): void
    {
        // Al cambiar un combo se recalculan la lista activa y el combo que anuncia cada marca (ComboCatalog::headline).
        $forget = function (): void {
            Cache::forget(self::ACTIVE_CACHE_KEY);
            foreach (['OVERSHARK', 'GIRLS', 'BRAVOS'] as $brand) {
                Cache::forget('combo-headline:'.$brand);
            }
        };
        static::saved($forget);
        static::deleted($forget);
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'items' => 'array',
            'selection_limit' => 'integer',
            'status' => 'boolean',
            'is_draft' => 'boolean',
        ];
    }

    public function brandModel()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /** Precio regular a mostrar tachado, solo si es mayor que el precio actual. */
    public function compareAtPriceToShow(): ?float
    {
        return $this->compare_at_price !== null && (float) $this->compare_at_price > (float) $this->price
            ? (float) $this->compare_at_price
            : null;
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

    /** Imagen para celular (Admin → Combos), o null si el combo usa la misma en todos los tamaños. */
    public function mobileImageUrl(): ?string
    {
        if (! $this->imagen_movil) {
            return null;
        }

        return str_starts_with($this->imagen_movil, 'images/') ? asset($this->imagen_movil) : '/storage/'.ltrim($this->imagen_movil, '/');
    }

    /**
     * ¿La imagen del combo es un banner horizontal (al menos 1.6 veces más ancha que alta)? Así la tienda lo muestra a lo
     * ancho en vez de encajonarlo en una tarjeta angosta. Se mide una vez por archivo (cada subida tiene nombre nuevo).
     */
    public function hasWideImage(): bool
    {
        $path = (string) $this->imagen;

        if ($path === '' || filter_var($path, FILTER_VALIDATE_URL)) {
            return false;
        }

        $file = str_starts_with($path, 'images/') ? public_path($path) : \Illuminate\Support\Facades\Storage::disk('public')->path($path);

        return Cache::rememberForever('combo-image-wide:'.md5($file), function () use ($file): bool {
            $size = is_file($file) ? @getimagesize($file) : false;

            return $size !== false && $size[1] > 0 && $size[0] / $size[1] >= 1.6;
        });
    }

    public function displayItems(): \Illuminate\Support\Collection
    {
        return collect($this->items ?? []);
    }

    /** Prendas que lleva el combo (las que elige la clienta o la suma de su composición). */
    public function units(): int
    {
        if ($this->selection_mode === 'choice' && $this->selection_limit) {
            return (int) $this->selection_limit;
        }

        // Las filas con la misma "prenda" (grupo) son alternativas: cuentan una sola vez.
        $quantity = $this->displayItems()
            ->groupBy(fn (array $item) => ! empty($item['grupo']) ? 'grupo:'.mb_strtoupper($item['grupo']) : 'item:'.spl_object_id((object) $item))
            ->sum(fn ($rows) => ! empty($rows->first()['grupo']) ? $rows->max('cantidad') : $rows->sum('cantidad'));

        return $quantity === 0 && $this->selection_limit ? (int) $this->selection_limit : (int) $quantity;
    }

    public function quantityLabel(): string
    {
        if ($this->selection_mode === 'choice' && $this->selection_limit) {
            return "Elige {$this->selection_limit} unidades";
        }

        return $this->units().' unidades';
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'combo_producto')->withPivot('cantidad');
    }
}
