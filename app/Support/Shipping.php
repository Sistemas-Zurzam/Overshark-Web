<?php

namespace App\Support;

/**
 * Costo de envío de la tienda. Es gratis desde services.storefront.free_shipping_from
 * (STOREFRONT_FREE_SHIPPING_FROM, en soles sobre el subtotal); con 0 no hay envío gratis.
 * El recojo en almacén (la clienta pasa a recoger su pedido) no tiene costo.
 */
class Shipping
{
    // Envío S/ 14 a todo el Perú (confirmado en el diseño del checkout; Lima delivery o agencia, provincia agencia).
    public const COST = 14;

    public const PICKUP = 'almacen';

    public static function freeFrom(): float
    {
        return (float) config('services.storefront.free_shipping_from', 0);
    }

    public static function costFor(float $subtotal, ?string $delivery = null): float
    {
        if ($delivery === self::PICKUP) {
            return 0.0;
        }

        $from = self::freeFrom();

        return $from > 0 && $subtotal >= $from ? 0.0 : (float) self::COST;
    }

    /** Dirección y horario del almacén (STOREFRONT_PICKUP_ADDRESS); vacío si aún no se configuró. */
    public static function pickupAddress(): string
    {
        return trim((string) config('services.storefront.pickup_address', ''));
    }

    /** Texto de la entrega elegida, para el pago, la confirmación y el admin. */
    public static function deliveryLabel(?string $delivery, ?string $agency = null, ?string $address = null): string
    {
        return match ($delivery) {
            'agencia' => 'Recojo en agencia: '.$agency,
            self::PICKUP => 'Recojo en almacén (gratis)'.(self::pickupAddress() !== '' ? ': '.self::pickupAddress() : ''),
            default => 'Envío a domicilio: '.$address,
        };
    }
}
