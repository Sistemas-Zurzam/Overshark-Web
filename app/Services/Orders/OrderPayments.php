<?php

namespace App\Services\Orders;

use App\Models\Admin\Order;
use App\Models\Admin\StatusOrder;
use App\Services\Payments\YapeQr;
use App\Support\Checkout;

/**
 * Cambios de estado del pago de un pedido web. Toda confirmación de pago pasa por confirm() —cobro con
 * tarjeta, QR de Yape pagado (simulado o, al integrarse, aviso de Ligo vía Zazu) o validación manual en el
 * admin— y arranca lo que sigue (OrderFulfillment).
 */
class OrderPayments
{
    public const PAID_STATUSES = [Checkout::STATUS_PAID, Checkout::STATUS_DEPOSIT_PAID];

    /** Marca el pago como confirmado: el total ("Pago confirmado") o el separo de contraentrega ("Separo pagado"). */
    public static function confirm(Order $order, ?string $reference = null): void
    {
        $order->loadMissing('statusOrder');
        if (in_array($order->statusName(), self::PAID_STATUSES, true)) {
            return;
        }

        $status = $order->isCashOnDelivery() && (float) $order->separo > 0 ? Checkout::STATUS_DEPOSIT_PAID : Checkout::STATUS_PAID;
        $order->update([
            'status_order_id' => self::status($status),
            'pago_referencia' => $reference ?? $order->pago_referencia,
            'pago_qr' => null,
            'pago_qr_expira' => null,
            'fecha_envio' => $order->fecha_envio ?? Checkout::shippingDate($order->entrega),
        ]);

        OrderFulfillment::start($order);
    }

    /** Genera el QR de Yape por lo que hay que pagar ahora (el total o el separo) y deja el pedido esperando. */
    public static function startQr(Order $order, YapeQr $qr): void
    {
        $data = $qr->create($order, (float) $order->pago_monto);

        $order->update([
            'status_order_id' => self::status(Checkout::STATUS_AWAITING_QR),
            'pago_qr' => $data['qr_image'],
            'pago_qr_expira' => $data['expires_at'],
            'pago_referencia' => $data['reference'],
        ]);
    }

    /** Cancela los pedidos cuyo QR venció sin pagarse. Devuelve cuántos. */
    public static function expireQrs(): int
    {
        $expired = Order::query()
            ->whereHas('statusOrder', fn ($query) => $query->where('name', Checkout::STATUS_AWAITING_QR))
            ->where('pago_qr_expira', '<', now())
            ->get();

        $expired->each(fn (Order $order) => $order->update([
            'status_order_id' => self::status(Checkout::STATUS_CANCELLED),
            'pago_qr' => null,
        ]));

        return $expired->count();
    }

    /** Contraentrega sin cobro en línea: el pedido pasa directo a preparación (no hay pago que confirmar) y se envía al CRM. */
    public static function markCashOnDelivery(Order $order): void
    {
        $order->update([
            'status_order_id' => self::status(Checkout::STATUS_COD),
            'fecha_envio' => $order->fecha_envio ?? Checkout::shippingDate($order->entrega),
        ]);

        OrderFulfillment::start($order);
    }

    private static function status(string $name): int
    {
        return StatusOrder::query()->firstOrCreate(['name' => $name])->id;
    }
}
