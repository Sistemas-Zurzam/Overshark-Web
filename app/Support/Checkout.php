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
use App\Services\Payments\CulqiGateway;
use App\Services\Payments\PaymentSimulator;
use App\Services\Payments\YapeQr;
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
    /** Fecha de despacho a domicilio: antes de las 10:00 (Lima), hoy; desde las 10:00, mañana. */
    public static function shippingDate(?string $delivery, ?\Illuminate\Support\Carbon $at = null): ?string
    {
        if ($delivery !== 'domicilio') {
            return null;
        }

        $at = ($at ?? now('America/Lima'))->copy()->setTimezone('America/Lima');

        return $at->copy()->startOfDay()->addDays($at->format('H:i') >= '10:00' ? 1 : 0)->toDateString();
    }

    /** Fecha legible para mostrar el despacho exacto en el estado del pedido. */
    public static function shippingDateLabel(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        $day = \Illuminate\Support\Carbon::parse($date, 'America/Lima');
        $today = now('America/Lima')->startOfDay();
        $prefix = match ((int) $today->diffInDays($day->copy()->startOfDay())) {
            0 => 'Hoy, ',
            1 => 'Mañana, ',
            default => '',
        };
        $weekdays = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

        return $prefix.$weekdays[$day->dayOfWeek].' '.$day->format('d/m/Y');
    }

    public const STATUS_PENDING = 'Pendiente de confirmación';

    public const STATUS_PAID = 'Pago confirmado';

    // Contraentrega con el separo ya cobrado en línea (tarjeta); el saldo se paga al recibir.
    public const STATUS_DEPOSIT_PAID = 'Separo pagado';

    // Yape: el QR está generado y se espera la confirmación del pago (vence a los PAYMENTS_QR_MINUTES).
    public const STATUS_AWAITING_QR = 'Esperando pago';

    // Contraentrega sin nada que cobrar en línea (recojo en almacén): el pedido ya está confirmado y se cobra al entregar.
    public const STATUS_COD = 'Contraentrega';

    // Pedidos que el equipo debe preparar y enviar: el pago ya está resuelto (en línea) o se cobra al entregar.
    public const PREPARATION_STATUSES = [self::STATUS_PAID, self::STATUS_DEPOSIT_PAID, self::STATUS_COD];

    public const STATUS_CANCELLED = 'Cancelado';

    public const CASH_ON_DELIVERY = 'contraentrega';

    public const COD_NAME = 'Pago contraentrega';

    public const CARD = 'tarjeta';

    public const YAPE = 'yape';

    /**
     * Resumen del carrito con los precios vigentes. Si alguno cambió desde que se agregó, se actualiza
     * y se devuelve el aviso en priceChanges para mostrárselo a la clienta antes de que confirme.
     *
     * @return array{items: Collection, count: int, subtotal: float, shipping: float, total: float, priceChanges: array<int, string>}
     */
    public static function summary(): array
    {
        $priceChanges = self::syncPrices();
        $items = collect(session('cart.items', []));
        $subtotal = (float) $items->sum(fn (array $item) => (float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 0));
        $shipping = $items->isEmpty() ? 0.0 : Shipping::costFor($subtotal, session('checkout.customer.delivery'));

        return [
            'items' => $items,
            'count' => (int) $items->sum('qty'),
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
            'priceChanges' => $priceChanges,
        ];
    }

    /**
     * Pone en el carrito el precio vigente de cada prenda y combo. Devuelve un aviso por cada precio que
     * cambió. Las líneas sin precio o sin stock se dejan como están: verifyCart las rechaza al confirmar.
     *
     * @return array<int, string>
     */
    public static function syncPrices(): array
    {
        $items = session('cart.items', []);
        $changes = [];

        foreach ($items as $key => $item) {
            $isCombo = ($item['kind'] ?? null) === 'combo';
            $variant = $isCombo ? null : Producto::query()->find($item['variant_id'] ?? 0);
            $promo = $variant ? Promotions::forVariant($variant) : null;
            // Una prenda se cobra con su promoción por horas si sigue vigente; al vencer vuelve a su precio regular.
            $current = $isCombo
                ? Combo::query()->find($item['combo_id'] ?? 0)?->price
                : ($promo['price'] ?? $variant?->price);

            if ($current === null || (float) $current <= 0) {
                continue;
            }

            if (round((float) $current, 2) === round((float) ($item['price'] ?? 0), 2)) {
                continue;
            }

            if (! $isCombo) {
                $items[$key]['regular_price'] = $promo ? $promo['regular'] : null;
            }

            $name = $isCombo ? (string) ($item['producto'] ?? 'Combo') : ProductName::title((string) ($item['producto'] ?? 'Una prenda'));
            $changes[] = sprintf('El precio de %s cambió de S/ %s a S/ %s.', $name, number_format((float) $item['price'], 2), number_format((float) $current, 2));
            $items[$key]['price'] = (float) $current;
        }

        if ($changes !== []) {
            session(['cart.items' => $items]);
        }

        return $changes;
    }

    /** Total que se cobrará por estas líneas (precio vigente + envío según la entrega elegida). */
    public static function totalFor(Collection $lines, ?string $delivery): float
    {
        $subtotal = (float) $lines->sum(fn (array $line) => $line['price'] * $line['qty']);

        return round($subtotal + Shipping::costFor($subtotal, $delivery), 2);
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
        // Unidades pedidas por variante sumando todas las líneas (una prenda suelta y la misma dentro de un combo).
        $demand = [];

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
                    if (! $variant || $variant->oculto || (int) $variant->stock < $units * $qty) {
                        return ['lines' => $lines, 'error' => 'Una prenda del combo "'.$combo->name.'" ya no tiene stock suficiente.'];
                    }
                    $demand[(int) $variantId] = ($demand[(int) $variantId] ?? 0) + $units * $qty;
                }

                $lines->push(['key' => (string) $key, 'kind' => 'combo', 'qty' => $qty, 'price' => (float) $combo->price, 'item' => $item]);

                continue;
            }

            $variant = Producto::query()->find($item['variant_id'] ?? 0);
            if (! $variant || $variant->oculto || (float) $variant->price <= 0 || (int) $variant->stock < $qty) {
                $name = ProductName::title((string) ($item['producto'] ?? 'Una prenda'));

                return ['lines' => $lines, 'error' => "{$name} ({$item['color']} · {$item['talla']}) ya no tiene stock suficiente. Ajusta la cantidad o quítala del carrito."];
            }

            // Precio con la promoción por horas si sigue vigente al confirmar (si venció, se cobra el regular).
            $lines->push(['key' => (string) $key, 'kind' => 'product', 'qty' => $qty, 'price' => Promotions::priceFor($variant), 'item' => $item]);
            $demand[(int) $variant->id] = ($demand[(int) $variant->id] ?? 0) + $qty;
        }

        foreach ($demand as $variantId => $units) {
            $variant = Producto::query()->find($variantId);
            if ((int) $variant->stock < $units) {
                $name = ProductName::title((string) $variant->name);

                return ['lines' => $lines, 'error' => "Pediste {$units} unidades de {$name} ({$variant->color} · {$variant->talla}) entre prendas sueltas y combos, pero solo quedan {$variant->stock}. Ajusta tu carrito."];
            }
        }

        return ['lines' => $lines, 'error' => $lines->isEmpty() ? 'Tu carrito está vacío.' : null];
    }

    /**
     * Separo de un pedido contraentrega según la entrega (Admin → Contenido de la tienda; de partida S/ 15 a domicilio y
     * S/ 30 en agencia, que incluye el envío).
     * Se paga en el mismo checkout con tarjeta o Yape/Plin; el saldo se paga al recibir. Almacén: sin separo.
     */
    public static function depositFor(?string $delivery): float
    {
        return match ($delivery) {
            'domicilio' => SiteContent::number('envio.deposit_home'),
            'agencia' => SiteContent::number('envio.deposit_agency'),
            default => 0.0,
        };
    }

    /** Separo que se cobra en este checkout: el de la entrega, sin pasar el total. */
    public static function depositAmount(?string $delivery, float $total): float
    {
        return min(self::depositFor($delivery), $total);
    }

    /**
     * Medios de pago, en este orden:
     *  - Tarjeta de crédito o débito (Culqi).
     *  - Yape con QR de Ligo Pay (App\Services\Payments\LigoPayGateway, pendiente de su API).
     *  - Los medios activos del admin (Plin, transferencia…: número o QR) con captura del pago.
     *  - Pago contraentrega (el separo se paga con uno de los anteriores; ver onlineMethods()).
     */
    public static function paymentMethods(): Collection
    {
        // Tarjeta (Culqi) y Yape (QR de Ligo Pay) se muestran siempre; sin configurar se ven con un aviso pero
        // no se puede pagar (available false).
        $methods = collect([
            [
                'id' => self::CARD, 'kind' => 'card', 'name' => 'Tarjeta de crédito o débito', 'available' => CulqiGateway::isConfigured() || PaymentSimulator::enabled(),
                'owner' => null, 'number' => null, 'logo' => null, 'qr' => null, 'receipt' => false,
            ],
            [
                'id' => self::YAPE, 'kind' => 'yape', 'name' => 'Yape', 'available' => YapeQr::isAvailable(),
                'owner' => null, 'number' => null, 'logo' => null, 'qr' => null, 'receipt' => false,
            ],
        ]);

        MetodoPago::active()->each(fn (MetodoPago $method) => $methods->push([
            'id' => (string) $method->id,
            'kind' => 'wallet',
            'name' => $method->name,
            'available' => true,
            'owner' => $method->titular,
            'number' => $method->numero,
            'logo' => $method->imageUrl(),
            'qr' => $method->qrImageUrl(),
            'receipt' => true,
        ]));

        return $methods->push([
            'id' => self::CASH_ON_DELIVERY, 'kind' => 'cod', 'name' => 'Pago contraentrega', 'available' => true,
            'owner' => null, 'number' => null, 'logo' => null, 'qr' => null, 'receipt' => false,
        ])->values();
    }

    /** Medios que se pueden elegir hoy (los "Muy pronto" no). */
    public static function availableMethods(): Collection
    {
        return self::paymentMethods()->where('available', true)->values();
    }

    /** Medios con los que se paga en línea (el total o el separo de contraentrega). */
    public static function onlineMethods(): Collection
    {
        return self::availableMethods()->where('kind', '!=', 'cod')->values();
    }

    /**
     * Crea el pedido con los datos de la clienta. $payWith es el medio con el que paga en línea (el mismo
     * $method, o el elegido para el separo en contraentrega; null si no paga nada en línea). Si el mismo
     * checkout_token ya generó un pedido, devuelve ese (doble clic o reenvío del formulario).
     */
    public static function place(array $customer, array $method, ?array $payWith, ?string $receiptPath, string $token, Collection $lines, ?int $accountId = null): Order
    {
        if ($existing = Order::query()->where('checkout_token', $token)->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($customer, $method, $payWith, $receiptPath, $token, $lines, $accountId) {
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
                'agencia' => 'Recojo en agencia Shalom',
                Shipping::PICKUP => 'Recojo en almacén',
                default => 'Envío a domicilio',
            };
            $isCod = $method['id'] === self::CASH_ON_DELIVERY;
            $deposit = $isCod ? self::depositAmount($customer['delivery'], $subtotal + $shipping) : null;

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
                'separo' => $isCod ? $deposit : null,
                'pago_monto' => $payWith ? ($isCod ? $deposit : $subtotal + $shipping) : null,
                'metodo_pago_id' => ctype_digit((string) ($payWith['id'] ?? '')) ? (int) $payWith['id'] : null,
                'metodo_pago_nombre' => $isCod
                    ? self::COD_NAME.($payWith ? ' · separo con '.$payWith['name'] : '')
                    : $method['name'],
                'comprobante_pago' => $receiptPath,
                'cliente_id' => $client->id,
                // Cuenta del comprador que inició sesión (la pone el servidor); nula en pedidos de invitados.
                'customer_account_id' => $accountId,
                'cliente_name' => trim($customer['nombres'].' '.$customer['apellidos']),
                'cliente_cel' => $customer['phone'],
                'email' => $customer['email'],
                'cliente_doc_identidad' => $customer['doc_number'],
                'tipo_documento_id' => $documentType->id,
                'status_order_id' => StatusOrder::query()->firstOrCreate(['name' => self::STATUS_PENDING])->id,
                'tipo_envio_id' => TipoEnvio::query()->firstOrCreate(['name' => $delivery])->id,
                'entrega' => $customer['delivery'],
                'agencia' => $customer['agency'] ?? null,
                'agencia_shalom_id' => $customer['agency_shalom_id'] ?? null,
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
