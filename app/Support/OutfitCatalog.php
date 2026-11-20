<?php

namespace App\Support;

use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelos de la tienda listos para el constructor de looks: cada uno con su marca, categoría, colores (foto) y
 * tallas con stock y precio. El precio de cada talla es el que cobra el carrito (con la promoción vigente).
 */
class OutfitCatalog
{
    /**
     * @param  Builder<Producto>|null  $query  Variantes a incluir; sin él, todo lo que la línea vende.
     * @param  array<int, string>|null  $brands  Marcas a ofrecer (Admin → Arma tu outfit); sin él, todas.
     * @return array<int, array<string, mixed>>
     */
    public static function models(?Builder $query = null, string $line = 'hombre', ?array $brands = null): array
    {
        $query ??= $line === 'mujer' ? GirlsHome::onlyGirls(Producto::query()) : GirlsHome::exceptGirls(Producto::query());

        $variants = $query
            ->where('price', '>', 0)
            ->where('stock', '>', 0)
            ->withColorPhoto()
            ->whereNotNull('color')
            ->orderBy('id')
            ->get();
        $galleries = ProductoColorImage::query()
            ->whereIn('product_name', $variants->pluck('name')->unique())
            ->get()
            ->groupBy('product_name');
        $fallback = asset('images/default-hero-banner.png');
        $sizeOrder = ['XS' => 0, 'S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4, 'XXL' => 5];

        return $variants
            ->groupBy(fn (Producto $variant) => $variant->zazu_company_id.'|'.$variant->name)
            ->map(function ($items) use ($galleries, $fallback, $sizeOrder, $line) {
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
                            ->map(fn ($sizeRows, $size) => [
                                'size' => (string) $size,
                                'stock' => (int) $sizeRows->sum('stock'),
                                'price' => round((float) $sizeRows->map(fn (Producto $variant) => Promotions::priceFor($variant))->min(), 2),
                            ])
                            ->sortBy(fn ($size) => $sizeOrder[mb_strtoupper($size['size'])] ?? 99)
                            ->values()
                            ->all(),
                    ])
                    ->values();

                return [
                    'id' => $first->id,
                    'name' => $first->name,
                    'brand' => self::brandOf($first, $line),
                    'category' => StorefrontCategories::slugFor($first->name, null, $line),
                    'price' => round((float) $items->map(fn (Producto $variant) => Promotions::priceFor($variant))->min(), 2),
                    'stock' => (int) $items->sum('stock'),
                    'colors' => $colors->all(),
                ];
            })
            ->when($brands !== null, fn ($models) => $models->filter(fn (array $model) => in_array($model['brand'], $brands, true)))
            ->sortByDesc('stock')
            ->values()
            ->all();
    }

    private static function brandOf(Producto $product, string $line): string
    {
        $label = mb_strtoupper(trim((string) ($product->marca ?: $product->empresa_nombre)));

        if (str_contains($label, 'BRAVO')) {
            return 'Bravos';
        }

        // En la línea mujer lo de Overshark (BABY TY, TOP RIB...) es Overshark Girls aunque la marca venga vacía.
        return $line === 'mujer' || str_contains($label, 'GIRL') ? 'Overshark Girls' : 'Overshark';
    }
}
