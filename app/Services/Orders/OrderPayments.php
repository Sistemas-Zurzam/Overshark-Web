<?php

namespace App\Services\Orders;

use App\Models\Admin\Order;
use App\Models\Admin\StatusOrder;
use App\Support\Checkout;

/**
 * Cambios de estado del pago de un pedido web. Toda confirmación de pago pasa por confirm() —pago con tarjeta
 * confirmado por Izipay (confirmIzipay), con firma verificada— y arranca lo que sigue (OrderFulfillment).
 * El panel no confirma pagos a mano.
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
            'pago_qr_expira' => null,
            'fecha_envio' => $order->fecha_envio ?? Checkout::shippingDate($order->entrega),
        ]);

        OrderFulfillment::start($order);
    }

    /** Deja el pedido esperando el pago con tarjeta (el total o el separo) durante PAYMENTS_WAIT_MINUTES. */
    public static function awaitCardPayment(Order $order): void
    {
        $order->update([
            'status_order_id' => self::status(Checkout::STATUS_AWAITING_QR),
            'pago_qr_expira' => now()->addMinutes((int) config('services.payments.wait_minutes', 30)),
        ]);
    }

    /**
     * Confirma un pago de Izipay ya validado por firma (IzipayGateway::verify). Solo cuenta si la plataforma lo
     * marca PAGADO, es de este pedido, por el importe exacto que debía pagar y en soles. Devuelve si quedó pagado.
     *
     * @param  array{order_id: string, paid: bool, amount: float, currency: string, uuid: ?string}  $result
     */
    public static function confirmIzipay(Order $order, array $result): bool
    {
        $order->loadMissing('statusOrder');

        if (in_array($order->statusName(), self::PAID_STATUSES, true)) {
            return true;
        }

        if (! $result['paid']
            || $result['order_id'] !== $order->number_zazu
            || $result['currency'] !== 'PEN'
            || abs($result['amount'] - (float) $order->pago_monto) >= 0.01
            || $order->statusName() === Checkout::STATUS_CANCELLED) {
            return false;
        }

        self::confirm($order, $result['uuid'] ?? $order->pago_referencia);

        return true;
    }

    /** Cancela los pedidos que esperaban el pago con tarjeta y no se pagaron a tiempo. Devuelve cuántos. */
    public static function expireQrs(): int
    {
        $expired = Order::query()
            ->whereHas('statusOrder', fn ($query) => $query->where('name', Checkout::STATUS_AWAITING_QR))
            ->where('pago_qr_expira', '<', now())
            ->get();

        $expired->each(fn (Order $order) => $order->update([
            'status_order_id' => self::status(Checkout::STATUS_CANCELLED),
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
