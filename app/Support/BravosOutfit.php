<?php

namespace App\Support;

use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use App\Models\Brand;

/**
 * Datos de "Arma tu outfit" de Bravos (web/bravos-outfit.blade.php): los modelos de la marca con stock,
 * separados en tops y pantalones, cada uno con sus colores (foto y tallas con stock).
 * El look se compra a precio individual de cada prenda (web.cart.look), sin combo.
 */
class BravosOutfit
{
    /** @return array{tops: array<int, array>, bottoms: array<int, array>} */
    public static function steps(Brand $brand): array
    {
        $variants = Producto::query()
            ->ofBrand($brand->name)
            ->where('price', '>', 0)
            ->where('stock', '>', 0)
            ->whereNotNull('color')
            ->orderBy('id')
            ->get();
        $galleries = ProductoColorImage::query()
            ->whereIn('product_name', $variants->pluck('name')->unique())
            ->get()
            ->groupBy('product_name');
        $fallback = asset('images/default-hero-banner.png');
        $sizeOrder = ['XS' => 0, 'S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4, 'XXL' => 5];

        $models = $variants
            ->groupBy(fn (Producto $variant) => $variant->zazu_company_id.'|'.$variant->name)
            ->map(function ($items) use ($galleries, $fallback, $sizeOrder) {
                $first = $items->first();
                $productGalleries = $galleries->get($first->name, collect())->keyBy('color');
                $colors = $items->groupBy('color')
                    // Primero el color con más stock, como en la tienda.
                    ->sortByDesc(fn ($rows) => $rows->sum('stock'))
                    ->map(fn ($rows, string $color) => [
                        'name' => $color,
                        'hex' => GarmentSwatch::hex($color),
                        'image' => Thumb::url($productGalleries->get($color)?->imageUrls()[0] ?? $first->imageUrl() ?? $fallback),
                        'sizes' => $rows->groupBy('talla')
                            ->map(fn ($sizeRows, $size) => ['size' => (string) $size, 'stock' => (int) $sizeRows->sum('stock')])
                            ->sortBy(fn ($size) => $sizeOrder[mb_strtoupper($size['size'])] ?? 99)
                            ->values()
                            ->all(),
                    ])
                    ->values();

                return [
                    'id' => $first->id,
                    'name' => $first->name,
                    'category' => StorefrontCategories::slugFor($first->name, null, 'hombre'),
                    'price' => (float) $items->min('price'),
                    'stock' => (int) $items->sum('stock'),
                    'colors' => $colors->all(),
                ];
            })
            ->sortByDesc('stock')
            ->values();

        return [
            'tops' => $models->filter(fn (array $model) => in_array($model['category'], ['poleras', 'polos', 'camisas'], true))->values()->all(),
            'bottoms' => $models->filter(fn (array $model) => $model['category'] === 'pantalones')->values()->all(),
        ];
    }
}
