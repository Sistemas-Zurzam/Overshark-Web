<?php

namespace App\Support;

use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;

/**
 * Carrito público guardado en la sesión (clave "cart.items").
 */
class SessionCart
{
    /**
     * Agrega unidades de una variante sin superar su stock y devuelve la cantidad resultante.
     */
    public static function addVariant(Producto $variant, int $qty, ?Producto $product = null): int
    {
        $product ??= $variant;
        $maxQty = max(1, (int) $variant->stock);
        $items = session('cart.items', []);
        $key = (string) $variant->id;
        $newQty = min(($items[$key]['qty'] ?? 0) + max(1, $qty), $maxQty);

        $items[$key] = [
            'producto_id' => $product->id,
            'variant_id' => $variant->id,
            'producto' => $variant->name,
            'qty' => $newQty,
            'price' => (float) $variant->price,
            'color' => $variant->color,
            'talla' => $variant->talla,
            'image' => self::imageFor($variant, $product),
        ];

        session(['cart.items' => $items]);

        return $newQty;
    }

    /**
     * Unidades de la variante que ya están en el carrito.
     */
    public static function quantityOf(int $variantId): int
    {
        return (int) (session('cart.items', [])[(string) $variantId]['qty'] ?? 0);
    }

    private static function imageFor(Producto $variant, Producto $product): string
    {
        $galleryImages = ProductoColorImage::query()
            ->where('product_name', $variant->name)
            ->where('color', $variant->color)
            ->first()
            ?->imageUrls() ?? [];

        return $galleryImages[0] ?? $variant->imageUrl() ?? $product->imageUrl() ?? asset('images/default-hero-banner.png');
    }
}
