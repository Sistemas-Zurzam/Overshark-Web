<?php

namespace App\Support;

/**
 * Costo de envío según la entrega: a domicilio (solo Lima), en agencia Shalom y gratis en el recojo en almacén.
 * Los importes se editan en Admin → Contenido de la tienda (SiteContent); sin editar valen
 * STOREFRONT_SHIPPING_HOME (15) y STOREFRONT_SHIPPING_AGENCY (14). Es gratis desde
 * services.storefront.free_shipping_from (STOREFRONT_FREE_SHIPPING_FROM, en soles sobre el subtotal); con 0
 * no hay envío gratis.
 */
class Shipping
{
    public const PICKUP = 'almacen';

    public static function freeFrom(): float
    {
        return (float) config('services.storefront.free_shipping_from', 0);
    }

    /** Tarifa de cada entrega, sin aplicar el envío gratis. Sin entrega elegida se usa la de domicilio. */
    public static function rate(?string $delivery): float
    {
        // Los importes se editan en Admin → Contenido de la tienda; el .env es el valor de partida.
        return match ($delivery) {
            self::PICKUP => 0.0,
            'agencia' => SiteContent::number('envio.agency'),
            default => SiteContent::number('envio.home'),
        };
    }

    /** El envío pagado más barato (para «Envío desde S/ X a todo el Perú»); el almacén gratis no cuenta. */
    public static function cheapest(): float
    {
        $paid = array_filter([self::rate('domicilio'), self::rate('agencia')], fn (float $rate) => $rate > 0);

        return $paid === [] ? 0.0 : min($paid);
    }

    public static function costFor(float $subtotal, ?string $delivery = null): float
    {
        $from = self::freeFrom();

        return $from > 0 && $subtotal >= $from ? 0.0 : self::rate($delivery);
    }

    /** Costo de cada entrega para este subtotal (la vista de "Datos y entrega" lo usa para recalcular). */
    public static function costsFor(float $subtotal): array
    {
        return [
            'domicilio' => self::costFor($subtotal, 'domicilio'),
            'agencia' => self::costFor($subtotal, 'agencia'),
            self::PICKUP => 0.0,
        ];
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
