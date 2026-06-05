<?php

use App\Models\Admin\BannerPortada;
use App\Models\Admin\Combo;
use App\Models\Admin\MetodoPago;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use Illuminate\Support\Facades\Route;

Route::get('/productos/{producto}', function (Producto $producto) {
    $fallbackImage = asset('images/default-hero-banner.png');
    $variants = Producto::query()
        ->where('odoo_template_id', $producto->odoo_template_id)
        ->where('price', '>', 0)
        ->where('qty_available', '>', 0)
        ->orderBy('color')
        ->orderBy('talla')
        ->get();

    abort_if($variants->isEmpty(), 404);

    $galleries = ProductoColorImage::query()
        ->where('odoo_template_id', $producto->odoo_template_id)
        ->get()
        ->keyBy('color');
    $colorOptions = $variants
        ->whereNotNull('color')
        ->groupBy('color')
        ->map(function ($items, string $color) use ($galleries, $producto, $fallbackImage) {
            $galleryImages = $galleries->get($color)?->imageUrls() ?? [];

            return [
                'name' => $color,
                'images' => $galleryImages ?: [$producto->imageUrl() ?? $fallbackImage],
                'sizes' => $items->pluck('talla')->filter()->unique()->values(),
            ];
        })
        ->values();
    $mainImages = $colorOptions->first()['images'] ?? [$producto->imageUrl() ?? $fallbackImage];

    return view('web.product-show', [
        'producto' => $producto,
        'variants' => $variants,
        'colorOptions' => $colorOptions,
        'sizes' => $variants->pluck('talla')->filter()->unique()->values(),
        'mainImages' => $mainImages,
        'price' => (float) $variants->min('price') ?: (float) $producto->price,
        'paymentMethods' => MetodoPago::query()
            ->where('status', true)
            ->latest()
            ->get(),
    ]);
})->name('web.products.show');

Route::get('/', function () {
    $fallbackImage = asset('images/default-hero-banner.png');
    $bestSellingProducts = productCards(
        Producto::query()
            ->selectRaw('MIN(id) as id, odoo_template_id, name, SUM(qty_available) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
            ->whereNotNull('odoo_template_id')
            ->where('price', '>', 0)
            ->where('qty_available', '>', 0)
            ->groupBy('odoo_template_id', 'name')
            ->orderByDesc('total_stock')
            ->limit(4)
            ->get(),
        $fallbackImage,
    );
    $shortSleeveProducts = productCards(
        Producto::query()
            ->selectRaw('MIN(id) as id, odoo_template_id, name, SUM(qty_available) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
            ->whereNotNull('odoo_template_id')
            ->where('price', '>', 0)
            ->where('qty_available', '>', 0)
            ->where(function ($query) {
                $query->where('name', 'like', '%MANGA CORTA%')
                    ->orWhere('name', 'like', '%CLASICO%')
                    ->orWhere('name', 'like', '%WAFFLE%');
            })
            ->groupBy('odoo_template_id', 'name')
            ->orderByDesc('total_stock')
            ->limit(3)
            ->get(),
        $fallbackImage,
    );

    if ($shortSleeveProducts->isEmpty()) {
        $shortSleeveProducts = $bestSellingProducts->take(3);
    }

    return view('web.home', [
        'banners' => BannerPortada::query()
            ->where('status', true)
            ->whereNotNull('image_path')
            ->latest()
            ->get(),
        'combos' => Combo::query()
            ->where('status', true)
            ->whereNotNull('imagen')
            ->latest()
            ->get(),
        'bestSellingProducts' => $bestSellingProducts,
        'shortSleeveProducts' => $shortSleeveProducts,
    ]);
})->name('web.home');

if (! function_exists('productCards')) {
    function productCards($products, string $fallbackImage)
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
