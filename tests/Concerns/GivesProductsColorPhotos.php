<?php

namespace Tests\Concerns;

use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;

/**
 * La tienda solo muestra los colores que ya tienen imágenes subidas. Los tests de vitrina crean productos sin
 * preocuparse por eso: cada variante con color recibe su galería, salvo dentro de Producto::withoutEvents().
 */
trait GivesProductsColorPhotos
{
    protected function setUpGivesProductsColorPhotos(): void
    {
        Producto::created(function (Producto $product): void {
            if ($product->color === null) {
                return;
            }

            ProductoColorImage::query()->firstOrCreate(
                ['product_name' => $product->name, 'color' => $product->color],
                ['images' => ['productos/colores/test.webp']],
            );
        });
    }
}
