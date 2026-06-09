<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Producto extends Model
{
    private const CACHE_VERSION_KEY = 'productos.cache_version';

    protected $fillable = [
        'categoria_id',
        'odoo_product_id',
        'odoo_template_id',
        'default_code',
        'name',
        'variant_values',
        'color',
        'talla',
        'stock',
        'price',
        'standard_price',
        'qty_available',
        'imagen',
        'descripcion',
        'composicion',
        'cuidados',
        'material',
        'fit',
        'sensacion',
        'guia_tallas_imagen',
        'odoo_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'variant_values' => 'array',
            'odoo_product_id' => 'integer',
            'odoo_template_id' => 'integer',
            'price' => 'decimal:2',
            'standard_price' => 'decimal:2',
            'qty_available' => 'decimal:2',
            'odoo_synced_at' => 'datetime',
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

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
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
        return $this->imagen ? '/storage/'.ltrim($this->imagen, '/') : null;
    }

    public function sizeGuideImageUrl(): ?string
    {
        return $this->guia_tallas_imagen ? '/storage/'.ltrim($this->guia_tallas_imagen, '/') : null;
    }
}
