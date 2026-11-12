<?php

namespace Tests\Concerns;

use App\Models\Admin\Order;

/** Llaves de prueba de Izipay y respuestas firmadas como las enviaría su plataforma (kr-answer + kr-hash). */
trait PaysWithIzipay
{
    protected function configureIzipay(): void
    {
        config([
            'services.izipay.shop_id' => '12345678',
            'services.izipay.password' => 'test_password',
            'services.izipay.public_key' => 'test_public_key',
            'services.izipay.hmac_key' => 'test_hmac_key',
            'services.izipay.api_url' => 'https://api.izipay.test/api-payment/V4',
        ]);
    }

    /**
     * Campos que Izipay envía al navegador (kr-hash-key = sha256_hmac) o a la IPN (kr-hash-key = password).
     *
     * @return array<string, string>
     */
    protected function izipayPayload(Order $order, string $status = 'PAID', ?float $amount = null, string $hashKey = 'sha256_hmac', string $currency = 'PEN', ?string $orderId = null): array
    {
        $answer = json_encode([
            'orderStatus' => $status,
            'orderDetails' => [
                'orderId' => $orderId ?? $order->number_zazu,
                'orderTotalAmount' => (int) round(($amount ?? (float) $order->pago_monto) * 100),
                'orderCurrency' => $currency,
            ],
            'transactions' => [['uuid' => 'txn_'.$order->id.'_test', 'status' => $status]],
        ]);
        $secret = $hashKey === 'password' ? config('services.izipay.password') : config('services.izipay.hmac_key');

        return [
            'kr-answer' => $answer,
            'kr-hash' => hash_hmac('sha256', $answer, $secret),
            'kr-hash-algorithm' => 'sha256_hmac',
            'kr-hash-key' => $hashKey,
            'kr-answer-type' => 'V4/Payment',
        ];
    }

    /** La clienta paga en el formulario de Izipay: el navegador envía el resultado firmado. */
    protected function payWithIzipay(Order $order): \Illuminate\Testing\TestResponse
    {
        $this->configureIzipay();

        return $this->post(route('web.checkout.paid', $order), $this->izipayPayload($order));
    }
}
