<?php

namespace App\Support;

use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;

class ProductCards
{
    public static function make($products, string $fallbackImage)
    {
        $productNames = $products->pluck('name')->filter()->unique()->values()->all();
        $variantsByProduct = Producto::query()
            ->whereIn('name', $productNames)
            ->whereNotNull('color')
            ->where('price', '>', 0)
            ->where('stock', '>', 0)
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
                ->map(function ($variants, string $color) use ($galleries, $product, $fallbackImage) {
                    $gallery = $galleries->get($color);
                    $galleryImages = $gallery?->imageUrls() ?? [];

                    return [
                        'name' => $color,
                        'image' => $galleryImages[0] ?? $product->imageUrl() ?? $fallbackImage,
                    ];
                })
                ->values();

            $product->setAttribute('display_colors', $colors);
            $product->setAttribute('display_image', $colors->first()['image'] ?? $product->imageUrl() ?? $fallbackImage);
        });

        return $products;
    }

    private static function productKey(string $name, mixed $companyId): string
    {
        return $name.'|'.($companyId ?? 'legacy');
    }
}
