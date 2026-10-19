<?php

namespace App\Support;

/**
 * Costo de envío de la tienda. Es gratis desde services.storefront.free_shipping_from
 * (STOREFRONT_FREE_SHIPPING_FROM, en soles sobre el subtotal); con 0 no hay envío gratis.
 */
class Shipping
{
    public const COST = 12;

    public static function freeFrom(): float
    {
        return (float) config('services.storefront.free_shipping_from', 0);
    }

    public static function costFor(float $subtotal): float
    {
        $from = self::freeFrom();

        return $from > 0 && $subtotal >= $from ? 0.0 : (float) self::COST;
    }
}
