<?php

namespace App\Support;

use App\Models\Admin\Categoria;
use App\Models\Admin\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Prendas disponibles para el probador: un modelo por nombre, con sus variantes en stock.
 *
 * Se muestran también las prendas que en ZAZU aún no tienen precio (price = 0) para poder armar
 * el outfit; esas variantes llegan con price = null y el servidor no permite comprarlas.
 * Solo entran productos que encajan en una categoría de ropa (polos, camisas, pantalones, accesorios).
 */
class OutfitCatalog
{
    private const LIMIT = 120;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function products(): Collection
    {
        $fallbackImage = asset('images/default-hero-banner.png');
        $models = ProductCards::make(
            Producto::query()
                ->selectRaw('MIN(id) as id, zazu_company_id, MAX(categoria_id) as categoria_id, MAX(marca) as marca, MAX(empresa_nombre) as empresa_nombre, name, SUM(stock) as total_stock, MAX(price) as max_price, MAX(imagen) as imagen')
                ->where('stock', '>', 0)
                ->groupBy('zazu_company_id', 'name')
                ->orderByDesc('total_stock')
                ->limit(self::LIMIT)
                ->get(),
            $fallbackImage,
        );

        $categoryNames = Categoria::query()
            ->whereIn('id', $models->pluck('categoria_id')->filter()->unique())
            ->pluck('name', 'id');
        $variants = Producto::query()
            ->whereIn('name', $models->pluck('name'))
            ->where('stock', '>', 0)
            ->orderBy('color')
            ->orderBy('talla')
            ->get(['id', 'name', 'zazu_company_id', 'color', 'talla', 'price', 'stock'])
            ->groupBy(fn (Producto $variant): string => $variant->zazu_company_id.'|'.$variant->name);

        return $models
            ->map(function (Producto $model) use ($categoryNames, $variants, $fallbackImage): ?array {
                $slug = StorefrontCategories::slugFor($model->name, $categoryNames->get($model->categoria_id));

                if ($slug === null) {
                    return null;
                }

                $modelVariants = $variants->get($model->zazu_company_id.'|'.$model->name, collect())
                    ->map(fn (Producto $variant): array => [
                        'id' => $variant->id,
                        'color' => $variant->color,
                        'size' => $variant->talla,
                        'price' => (float) $variant->price > 0 ? (float) $variant->price : null,
                        'swatch' => GarmentSwatch::hex($variant->color),
                        'fitting_image' => FittingImages::url($variant->name, $variant->color),
                    ])
                    ->values();
                $prices = $modelVariants->pluck('price')->filter();

                return [
                    'id' => $model->id,
                    'name' => Str::title(mb_strtolower($model->name)),
                    'brand' => $model->marca ?: $model->empresa_nombre,
                    'category' => $slug,
                    'slot' => StorefrontCategories::slotFor($slug),
                    'price' => $prices->isEmpty() ? null : $prices->min(),
                    'image' => $model->display_image === $fallbackImage ? null : $model->display_image,
                    'garment' => GarmentSwatch::garmentFor($slug),
                    'swatch' => GarmentSwatch::hex($variants->get($model->zazu_company_id.'|'.$model->name, collect())
                        ->groupBy('color')
                        ->map(fn ($colorVariants) => $colorVariants->sum('stock'))
                        ->sortDesc()
                        ->keys()
                        ->first()),
                    'colors' => $modelVariants->pluck('color')->filter()->unique()->count(),
                    'variants' => $modelVariants->all(),
                ];
            })
            ->filter(fn (?array $product): bool => $product !== null && $product['variants'] !== [])
            ->values();
    }
}
