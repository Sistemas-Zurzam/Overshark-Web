<?php

namespace App\Support;

use App\Models\Admin\Order;
use Illuminate\Support\Collection;

/**
 * Eventos de conversión para los píxeles de anuncios (Admin → Integraciones → Píxeles). Las rutas los anotan y
 * partials/tracking-pixels los envía a Meta, Google Analytics 4 y TikTok. Sin píxeles activos no se guarda nada.
 *
 * Cada evento: [nombre, {value, currency, order_id?, items: [{id, name, price, quantity}]}]
 * (ViewContent, AddToCart, InitiateCheckout, Purchase).
 */
class PixelEvents
{
    private const REQUEST = 'pixel-events.request';

    private const SESSION = 'pixel_events';

    /** Evento de la página que se está mostrando. */
    public static function now(string $event, array $data): void
    {
        if (! self::active()) {
            return;
        }
        $events = (array) request()->attributes->get(self::REQUEST, []);
        $events[] = [$event, self::normalize($data)];
        request()->attributes->set(self::REQUEST, $events);
    }

    /** Evento que se envía en la página siguiente (después de una redirección, p. ej. agregar al carrito). */
    public static function flash(string $event, array $data): void
    {
        if (! self::active()) {
            return;
        }
        session()->flash(self::SESSION, [...(array) session(self::SESSION, []), [$event, self::normalize($data)]]);
    }

    /** Eventos pendientes para esta página (los de la redirección anterior y los de esta). */
    public static function pull(): array
    {
        $events = [...(array) session()->pull(self::SESSION, []), ...(array) request()->attributes->get(self::REQUEST, [])];
        request()->attributes->set(self::REQUEST, []);

        return $events;
    }

    /** Compra del pedido, una sola vez por sesión (recargar la página de estado no la vuelve a contar). */
    public static function purchase(Order $order): void
    {
        if (! self::active() || in_array($order->id, (array) session('pixel_purchases', []), true)) {
            return;
        }
        session()->push('pixel_purchases', $order->id);

        self::now('Purchase', [
            'order_id' => $order->displayNumber(),
            'value' => (float) $order->precio_total,
            'items' => collect($order->productos_json ?? [])->map(fn (array $line) => [
                'id' => $line['variant_id'] ?? $line['combo_id'] ?? null,
                'name' => $line['producto'] ?? null,
                'price' => (float) ($line['precio'] ?? 0),
                'quantity' => (int) ($line['cantidad'] ?? 1),
            ])->all(),
        ]);
    }

    /** Líneas del carrito de la sesión (Checkout::summary()['items']) en el formato de los eventos. */
    public static function cartItems(Collection $items): array
    {
        return $items->map(fn (array $item) => [
            'id' => $item['variant_id'] ?? $item['combo_id'] ?? null,
            'name' => $item['producto'] ?? $item['name'] ?? null,
            'price' => (float) ($item['price'] ?? 0),
            'quantity' => (int) ($item['qty'] ?? 1),
        ])->values()->all();
    }

    private static function active(): bool
    {
        return array_filter(Integrations::pixels()) !== [];
    }

    private static function normalize(array $data): array
    {
        return [
            'value' => round((float) ($data['value'] ?? 0), 2),
            'currency' => 'PEN',
            'order_id' => $data['order_id'] ?? null,
            'items' => collect($data['items'] ?? [])->map(fn (array $item) => [
                'id' => (string) ($item['id'] ?? ''),
                'name' => (string) ($item['name'] ?? ''),
                'price' => round((float) ($item['price'] ?? 0), 2),
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
            ])->values()->all(),
        ];
    }
}
