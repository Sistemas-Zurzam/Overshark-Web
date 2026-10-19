<?php

namespace App\Support;

use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;

class ProductCards
{
    public static function make($products, string $fallbackImage, ?string $brandName = null)
    {
        $productNames = $products->pluck('name')->filter()->unique()->values()->all();
        $variantsByProduct = Producto::query()
            ->whereIn('name', $productNames)
            ->whereNotNull('color')
            ->where('price', '>', 0)
            ->where('stock', '>', 0)
            ->when($brandName !== null, fn ($query) => $query->whereRaw('UPPER(TRIM(marca)) = ?', [mb_strtoupper(trim($brandName))]))
            ->orderBy('color')
            ->get()
            ->groupBy(fn (Producto $product): string => self::productKey($product->name, $product->zazu_company_id));
        $galleriesByProduct = ProductoColorImage::query()
            ->whereIn('product_name', $productNames)
            ->get()
            ->groupBy('product_name');

        $products->each(function (Producto $product) use ($variantsByProduct, $galleriesByProduct, $fallbackImage): void {
            $galleries = $galleriesByProduct->get($product->name, collect())->keyBy('color');
            $colors = $variantsByProduct->get(self::productKey($product->name, $product->zazu_company_id), collect())
                ->groupBy('color')
                // Primero el color con más stock (es la foto de la tarjeta): así no todos salen del mismo color.
                ->sortByDesc(fn ($variants) => $variants->sum('stock'))
                ->map(function ($variants, string $color) use ($galleries, $product, $fallbackImage) {
                    $gallery = $galleries->get($color);
                    $galleryImages = $gallery?->imageUrls() ?? [];

                    return [
                        'name' => $color,
                        'image' => $galleryImages[0] ?? $product->imageUrl() ?? $fallbackImage,
                    ];
                })
                ->values();

            // Foto principal: la de un color que tenga fotos propias (aunque ese color aún no tenga precio),
            // antes que la imagen general del producto o la de reemplazo.
            // (No usar ->only(): en una colección Eloquent filtra por id del registro, no por color.)
            $galleryImage = $colors->pluck('name')
                ->map(fn (string $color) => $galleries->get($color))
                ->concat($galleries->values())
                ->filter()
                ->map(fn (ProductoColorImage $gallery) => $gallery->imageUrls()[0] ?? null)
                ->filter()
                ->first();

            $product->setAttribute('display_colors', $colors);
            $product->setAttribute('display_image', $galleryImage ?? $colors->first()['image'] ?? $product->imageUrl() ?? $fallbackImage);
        });

        return $products;
    }

    private static function productKey(string $name, mixed $companyId): string
    {
        return $name.'|'.($companyId ?? 'legacy');
    }
}
