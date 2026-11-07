<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Producto extends Model
{
    private const CACHE_VERSION_KEY = 'productos.cache_version';

    protected $fillable = [
        'categoria_id',
        'bodega_id',
        'default_code',
        'name',
        'color',
        'talla',
        'stock',
        'price',
        'standard_price',
        'imagen',
        'descripcion',
        'composicion',
        'cuidados',
        'material',
        'fit',
        'sensacion',
        'guia_tallas_imagen',
        'zazu_source_key',
        'zazu_product_id',
        'zazu_variant_id',
        'zazu_company_id',
        'empresa_nombre',
        'marca',
        'zazu_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'standard_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::refreshCacheVersion());
        static::deleted(fn () => self::refreshCacheVersion());
    }

    public static function cacheVersion(): int
    {
        return (int) Cache::rememberForever(self::CACHE_VERSION_KEY, fn () => time());
    }

    public static function refreshCacheVersion(): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, time());
    }

    /**
     * Productos de una marca: los que tienen esa marca asignada en el admin o, si no tienen marca,
     * los que vienen de la empresa de Zazu con ese nombre (p. ej. la empresa BRAVOS).
     */
    public function scopeOfBrand(Builder $query, string $brand): Builder
    {
        $name = mb_strtoupper(trim($brand));

        return $query->where(fn (Builder $brandQuery) => $brandQuery
            ->whereRaw('UPPER(TRIM(marca)) = ?', [$name])
            ->orWhere(fn (Builder $companyQuery) => $companyQuery
                ->where(fn (Builder $empty) => $empty->whereNull('marca')->orWhere('marca', ''))
                ->whereRaw('UPPER(TRIM(empresa_nombre)) = ?', [$name])));
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class);
    }

    public function variantes()
    {
        return $this->hasMany(Variante::class);
    }

    public function combos()
    {
        return $this->belongsToMany(Combo::class, 'combo_producto')->withPivot('cantidad');
    }

    public function imageUrl(): ?string
    {
        if (! $this->imagen) {
            return null;
        }

        return filter_var($this->imagen, FILTER_VALIDATE_URL)
            ? $this->imagen
            : '/storage/'.ltrim($this->imagen, '/');
    }

    public function sizeGuideImageUrl(): ?string
    {
        return $this->guia_tallas_imagen ? '/storage/'.ltrim($this->guia_tallas_imagen, '/') : null;
    }
}
