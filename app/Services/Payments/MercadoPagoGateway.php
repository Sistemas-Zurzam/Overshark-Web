<?php

namespace App\Services\Payments;

use App\Models\Admin\Order;
use App\Models\Admin\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MercadoPagoGateway
{
    public function createCheckout(Order $order): ?string
    {
        $gateway = PaymentGateway::query()->where('provider', 'mercadopago')->first()?->credentials ?? [];
        $gateway['environment'] = PaymentGateway::query()->where('provider', 'mercadopago')->value('environment') ?? 'test';
        $token = $gateway['access_token'] ?? null;
        if (! $token) {
            return null;
        }

        $response = Http::withToken($token)->acceptJson()->timeout(20)->withHeaders([
            'X-Idempotency-Key' => (string) Str::uuid(),
        ])->post('https://api.mercadopago.com/checkout/preferences', [
            'items' => [[
                'id' => (string) $order->id,
                'title' => 'Pedido Overshark '.$order->number_zazu,
                'quantity' => 1,
                'currency_id' => 'PEN',
                'unit_price' => round((float) $order->pago_monto, 2),
            ]],
            'external_reference' => (string) $order->id,
            'notification_url' => route('web.payments.mercadopago.webhook'),
            'back_urls' => [
                'success' => route('web.checkout.done', $order),
                'pending' => route('web.checkout.done', $order),
                'failure' => route('web.checkout.done', $order),
            ],
            'auto_return' => 'approved',
            'statement_descriptor' => 'OVERSHARK',
        ]);

        if (! $response->successful()) {
            Log::warning('Mercado Pago: no se pudo crear preferencia', ['status' => $response->status(), 'order_id' => $order->id]);
            return null;
        }

        $body = $response->json();
        return ($gateway['environment'] ?? 'test') === 'production'
            ? ($body['init_point'] ?? null)
            : ($body['sandbox_init_point'] ?? $body['init_point'] ?? null);
    }

    public function verifyNotification(string $paymentId, string $signature, string $requestId): bool
    {
        $secret = PaymentGateway::query()->where('provider', 'mercadopago')->first()?->credential('webhook_secret');
        $parts = collect(explode(',', $signature))->mapWithKeys(function ($part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            return [$key => $value];
        });
        $ts = $parts->get('ts');
        $v1 = $parts->get('v1');
        if (! $secret || ! $ts || ! $v1) {
            return false;
        }

        $manifest = "id:{$paymentId};request-id:{$requestId};ts:{$ts};";
        return hash_equals(hash_hmac('sha256', $manifest, $secret), $v1);
    }

    public function payment(string $paymentId): ?array
    {
        $token = PaymentGateway::query()->where('provider', 'mercadopago')->first()?->credential('access_token');
        if (! $token) {
            return null;
        }
        $response = Http::withToken($token)->acceptJson()->timeout(15)->get('https://api.mercadopago.com/v1/payments/'.rawurlencode($paymentId));
        if (! $response->successful()) {
            return null;
        }
        return $response->json();
    }
}
