<?php

namespace App\Services\Payments;

use App\Models\Admin\Order;

/**
 * QR de Yape para un pedido: el de Ligo Pay cuando esté integrado (LigoPayGateway) o el de prueba del modo
 * simulación. La confirmación del pago entra por App\Services\Orders\OrderPayments::confirm().
 */
class YapeQr
{
    public static function isAvailable(): bool
    {
        return LigoPayGateway::isAvailable() || PaymentSimulator::enabled();
    }

    /** @return array{qr_image: string, reference: string, expires_at: \DateTimeInterface} */
    public function create(Order $order, float $amount): array
    {
        if (LigoPayGateway::isAvailable()) {
            $qr = app(LigoPayGateway::class)->createQr($amount, $order->number_zazu);

            return [...$qr, 'expires_at' => $qr['expires_at'] ?? now()->addMinutes((int) config('services.payments.qr_minutes', 10))];
        }

        return PaymentSimulator::qr($amount, $order->number_zazu);
    }
}
