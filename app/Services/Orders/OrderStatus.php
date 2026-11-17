<?php

namespace App\Services\Orders;

use App\Models\Admin\Order;
use App\Models\Admin\StatusOrder;
use App\Services\Integrations\Webhooks;
use App\Support\Checkout;

/** Estados que el equipo pone a mano (panel o API de integraciones). Los de pago los pone solo el sistema. */
class OrderStatus
{
    public const MANUAL = ['Enviado', 'Entregado', Checkout::STATUS_CANCELLED];

    public static function change(Order $order, string $status): void
    {
        $previous = $order->loadMissing('statusOrder')->statusName();
        $order->update(['status_order_id' => StatusOrder::query()->firstOrCreate(['name' => $status])->id]);
        $order->load('statusOrder');

        if ($previous !== $status && isset(Webhooks::STATUS_EVENTS[$status])) {
            Webhooks::dispatch(Webhooks::STATUS_EVENTS[$status], $order);
        }
    }
}
