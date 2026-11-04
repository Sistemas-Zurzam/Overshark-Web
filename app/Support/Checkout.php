<?php

namespace App\Support;

use App\Models\Admin\Cliente;
use App\Models\Admin\Combo;
use App\Models\Admin\MetodoPago;
use App\Models\Admin\Order;
use App\Models\Admin\Producto;
use App\Models\Admin\StatusOrder;
use App\Models\Admin\TipoDocumento;
use App\Models\Admin\TipoEnvio;
use App\Models\Admin\TipoRegistro;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Checkout web (web/checkout/*): resumen del carrito, revalidación en el servidor y creación del pedido.
 * Los precios incluyen impuestos; el total es subtotal + envío (Shipping). El stock se valida contra la
 * copia local (sincronizada desde Zazu) y no se descuenta aquí: Zazu sigue siendo la fuente del stock.
 */
class Checkout
{
    public const STATUS_PENDING = 'Pendiente de confirmación';

    public const STATUS_PAID = 'Pago confirmado';

    public const CASH_ON_DELIVERY = 'contraentrega';

    /** @return array{items: Collection, count: int, subtotal: float, shipping: float, total: float} */
    public static function summary(): array
    {
        $items = collect(session('cart.items', []));
        $subtotal = (float) $items->sum(fn (array $item) => (float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 0));
        $shipping = $items->isEmpty() ? 0.0 : Shipping::costFor($subtotal, session('checkout.customer.delivery'));

        return [
            'items' => $items,
            'count' => (int) $items->sum('qty'),
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
        ];
    }

    /**
     * Revisa cada línea del carrito contra la base: precio vigente y stock suficiente.
     * Devuelve las líneas con el precio actual o el primer problema encontrado.
     *
     * @return array{lines: Collection, error: ?string}
     */
    public static function verifyCart(): array
    {
        $lines = collect();

        foreach (session('cart.items', []) as $key => $item) {
            $qty = max(1, (int) ($item['qty'] ?? 1));

            if (($item['kind'] ?? null) === 'combo') {
                $combo = Combo::query()->find($item['combo_id'] ?? 0);
                if (! $combo || ! $combo->status || $combo->price === null) {
                    return ['lines' => $lines, 'error' => 'El combo "'.($item['producto'] ?? '').'" ya no está disponible. Quítalo del carrito para continuar.'];
                }

                $needed = collect($item['selections'] ?? [])->countBy('variant_id');
                foreach ($needed as $variantId => $units) {
                    $variant = Producto::query()->find($variantId);
                    if (! $variant || (int) $variant->stock < $units * $qty) {
                        return ['lines' => $lines, 'error' => 'Una prenda del combo "'.$combo->name.'" ya no tiene stock suficiente.'];
                    }
                }

                $lines->push(['key' => (string) $key, 'kind' => 'combo', 'qty' => $qty, 'price' => (float) $combo->price, 'item' => $item]);

                continue;
            }

            $variant = Producto::query()->find($item['variant_id'] ?? 0);
            if (! $variant || (float) $variant->price <= 0 || (int) $variant->stock < $qty) {
                $name = ProductName::title((string) ($item['producto'] ?? 'Una prenda'));

                return ['lines' => $lines, 'error' => "{$name} ({$item['color']} · {$item['talla']}) ya no tiene stock suficiente. Ajusta la cantidad o quítala del carrito."];
            }

            $lines->push(['key' => (string) $key, 'kind' => 'product', 'qty' => $qty, 'price' => (float) $variant->price, 'item' => $item]);
        }

        return ['lines' => $lines, 'error' => $lines->isEmpty() ? 'Tu carrito está vacío.' : null];
    }

    /** Medios de pago disponibles: los activos del admin (con número o QR) y el pago contraentrega. */
    public static function paymentMethods(): Collection
    {
        return MetodoPago::active()
            ->map(fn (MetodoPago $method) => [
                'id' => (string) $method->id,
                'name' => $method->name,
                'owner' => $method->titular,
                'number' => $method->numero,
                'logo' => $method->imageUrl(),
                'qr' => $method->qrImageUrl(),
                'receipt' => true,
            ])
            ->push([
                'id' => self::CASH_ON_DELIVERY,
                'name' => 'Pago contraentrega',
                'owner' => null,
                'number' => null,
                'logo' => null,
                'qr' => null,
                'receipt' => false,
            ])
            ->values();
    }

    /**
     * Crea el pedido con los datos de la clienta. Si el mismo checkout_token ya generó un pedido,
     * devuelve ese (doble clic o reenvío del formulario).
     */
    public static function place(array $customer, array $method, ?string $receiptPath, string $token, Collection $lines): Order
    {
        if ($existing = Order::query()->where('checkout_token', $token)->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($customer, $method, $receiptPath, $token, $lines) {
            $documentType = TipoDocumento::query()->firstOrCreate(['code' => $customer['doc_type']], ['name' => $customer['doc_type']]);
            $register = TipoRegistro::query()->firstOrCreate(['name' => 'Web']);
            $client = Cliente::query()->updateOrCreate(
                ['email' => $customer['email']],
                [
                    'name' => $customer['nombres'],
                    'apellidos' => $customer['apellidos'],
                    'cel' => $customer['phone'],
                    'documento_identidad' => $customer['doc_number'],
                    'tipo_documento_id' => $documentType->id,
                    'tipo_registro_id' => $register->id,
                ],
            );

            $subtotal = (float) $lines->sum(fn (array $line) => $line['price'] * $line['qty']);
            $shipping = Shipping::costFor($subtotal, $customer['delivery']);
            $delivery = match ($customer['delivery']) {
                'agencia' => 'Recojo en agencia',
                Shipping::PICKUP => 'Recojo en almacén',
                default => 'Envío a domicilio',
            };

            $order = Order::query()->create([
                'number_zazu' => 'WEB-'.Str::upper(Str::random(12)),
                'productos_json' => $lines->map(fn (array $line) => [
                    'tipo' => $line['kind'],
                    'producto' => $line['item']['producto'] ?? null,
                    'variant_id' => $line['item']['variant_id'] ?? null,
                    'combo_id' => $line['item']['combo_id'] ?? null,
                    'color' => $line['item']['color'] ?? null,
                    'talla' => $line['item']['talla'] ?? null,
                    'selecciones' => $line['item']['selections'] ?? null,
                    'cantidad' => $line['qty'],
                    'precio' => $line['price'],
                    'imagen' => $line['item']['image'] ?? null,
                ])->values()->all(),
                'subtotal' => $subtotal,
                'envio' => $shipping,
                'precio_total' => $subtotal + $shipping,
                'metodo_pago_id' => ctype_digit((string) $method['id']) ? (int) $method['id'] : null,
                'metodo_pago_nombre' => $method['name'],
                'comprobante_pago' => $receiptPath,
                'cliente_id' => $client->id,
                'cliente_name' => trim($customer['nombres'].' '.$customer['apellidos']),
                'cliente_cel' => $customer['phone'],
                'email' => $customer['email'],
                'cliente_doc_identidad' => $customer['doc_number'],
                'tipo_documento_id' => $documentType->id,
                'status_order_id' => StatusOrder::query()->firstOrCreate(['name' => self::STATUS_PENDING])->id,
                'tipo_envio_id' => TipoEnvio::query()->firstOrCreate(['name' => $delivery])->id,
                'entrega' => $customer['delivery'],
                'agencia' => $customer['agency'] ?? null,
                'tipo_registro_id' => $register->id,
                'distrito_id' => $customer['distrito_id'] ?? null,
                'direccion' => $customer['address'] ?? null,
                'referencia' => $customer['reference'] ?? null,
                'tipo_comprobante' => $customer['invoice'],
                'ruc' => $customer['ruc'] ?? null,
                'razon_social' => $customer['razon_social'] ?? null,
                'checkout_token' => $token,
            ]);

            // Número legible derivado del id (OS-000012); number_zazu queda libre para el número de Zazu.
            $order->update(['number_zazu' => $order->code()]);

            return $order;
        });
    }
}
