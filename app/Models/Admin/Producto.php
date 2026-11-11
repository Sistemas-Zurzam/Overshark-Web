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

    /**
     * Solo lo que vende la tienda: las empresas de ZAZU_COMPANIES (p. ej. OVERSHARK, BRAVOS). Los productos de otras
     * empresas (BOX PRIME) quedaron guardados de sincronizaciones anteriores y no deben verse en el admin. Un
     * producto sin empresa se mantiene; sin lista configurada no se filtra nada.
     */
    /**
     * Lo que la tienda pública puede vender: productos de las empresas de ZAZU_COMPANIES (Overshark y Bravos; nunca los de
     * BOX PRIME u otras) y que el admin no ocultó (Admin → Productos → «Ocultar de la tienda»). Se aplica en cada consulta
     * del catálogo y en el carrito, así que no depende de que la sincronización con Zazu ya haya puesto en 0 el stock de
     * lo que no se vende.
     */
    public function scopeInStore(Builder $query): Builder
    {
        return $query->where('productos.oculto', false)->soldByStore();
    }

    /**
     * Lo que la tienda puede mostrar: productos no ocultos y, de ellos, solo variantes cuyo color ya tiene imágenes
     * subidas (galería por color del admin). Un color sin fotos no se muestra hasta que se suban: sin esto aparecería
     * con la foto genérica del producto.
     */
    public function scopeWithColorPhoto(Builder $query): Builder
    {
        $query->inStore();

        // Sin color no hay galería a la que apuntar: esas variantes se siguen mostrando como antes.
        return $query->where(fn (Builder $query) => $query
            ->whereNull('productos.color')
            ->orWhere('productos.color', '')
            ->orWhereExists(fn ($gallery) => $gallery->selectRaw('1')
                ->from('producto_color_images as galleries')
                ->whereColumn('galleries.product_name', 'productos.name')
                ->whereColumn('galleries.color', 'productos.color')
                ->whereNotNull('galleries.images')
                ->whereNotIn('galleries.images', ['[]', 'null', ''])));
    }

    public function scopeSoldByStore(Builder $query): Builder
    {
        $allowed = (array) config('services.zazu.companies', []);
        if ($allowed === []) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($allowed): void {
            $query->whereNull('productos.empresa_nombre')->orWhere('productos.empresa_nombre', '');
            foreach ($allowed as $part) {
                $query->orWhereRaw('UPPER(productos.empresa_nombre) LIKE ?', ['%'.mb_strtoupper($part).'%']);
            }
        });
    }

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

    /**
     * Lo contrario de ofBrand(): productos que NO son de esa marca. Con marca y empresa vacías (datos antiguos) el producto
     * se queda, a diferencia de un NOT sobre ofBrand(), que con valores nulos lo descartaría.
     */
    public function scopeExceptBrand(Builder $query, string $brand): Builder
    {
        $name = mb_strtoupper(trim($brand));

        return $query
            ->whereRaw("UPPER(TRIM(COALESCE(productos.marca, ''))) <> ?", [$name])
            ->where(fn (Builder $inner) => $inner
                ->whereRaw("TRIM(COALESCE(productos.marca, '')) <> ''")
                ->orWhereRaw("UPPER(TRIM(COALESCE(productos.empresa_nombre, ''))) <> ?", [$name]));
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
