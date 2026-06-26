<?php

namespace App\Support;

use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;

class ProductCards
{
    public static function make($products, string $fallbackImage)
    {
        $templateIds = $products->pluck('odoo_template_id')->filter()->all();
        $variantsByTemplate = Producto::query()
            ->whereIn('odoo_template_id', $templateIds)
            ->whereNotNull('color')
            ->where('price', '>', 0)
            ->where('qty_available', '>', 0)
            ->orderBy('color')
            ->get()
            ->groupBy('odoo_template_id');
        $galleriesByTemplate = ProductoColorImage::query()
            ->whereIn('odoo_template_id', $templateIds)
            ->get()
            ->groupBy('odoo_template_id');

        $products->each(function (Producto $product) use ($variantsByTemplate, $galleriesByTemplate, $fallbackImage): void {
            $galleries = $galleriesByTemplate->get($product->odoo_template_id, collect())->keyBy('color');
            $colors = $variantsByTemplate->get($product->odoo_template_id, collect())
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
}
