<?php

namespace App\Services\Integrations;

use App\Jobs\DeliverWebhook;
use App\Models\Admin\Order;
use App\Models\Admin\WebhookEndpoint;
use App\Support\Checkout;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Avisos de pedidos a sistemas externos (Admin → Integraciones → Webhooks). Cada aviso es un POST JSON
 * {id, event, created_at, data} firmado con el secreto del destino:
 *   X-Overshark-Signature: sha256=HMAC_SHA256(cuerpo, secreto)
 * Se envía por la cola y se reintenta si el destino no responde 2xx.
 */
class Webhooks
{
    /** Estado que pone el equipo (o la API) → evento que se avisa. */
    public const STATUS_EVENTS = [
        'Enviado' => 'order.shipped',
        'Entregado' => 'order.delivered',
        Checkout::STATUS_CANCELLED => 'order.cancelled',
    ];

    /** Encola el aviso del pedido para cada destino activo suscrito al evento. Nunca interrumpe el flujo que lo llama. */
    public static function dispatch(string $event, Order $order): void
    {
        try {
            $endpoints = WebhookEndpoint::query()->where('enabled', true)->get()->filter(fn (WebhookEndpoint $endpoint) => $endpoint->listensTo($event));
            if ($endpoints->isEmpty()) {
                return;
            }

            $payload = self::payload($event, OrderPayload::from($order->fresh() ?? $order));
            foreach ($endpoints as $endpoint) {
                DeliverWebhook::dispatch($endpoint->id, $payload);
            }
        } catch (\Throwable $e) {
            Log::warning('Webhooks: no se pudo encolar el aviso', ['event' => $event, 'order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    public static function payload(string $event, array $data): array
    {
        return ['id' => (string) Str::uuid(), 'event' => $event, 'created_at' => now()->toIso8601String(), 'data' => $data];
    }

    public static function signature(string $body, string $secret): string
    {
        return 'sha256='.hash_hmac('sha256', $body, $secret);
    }

    /**
     * Envía un aviso y guarda el resultado en el destino.
     *
     * @return array{ok: bool, status: ?int, message: string}
     */
    public static function send(WebhookEndpoint $endpoint, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'User-Agent' => 'Overshark-Webhooks/1.0',
                'X-Overshark-Event' => $payload['event'],
                'X-Overshark-Delivery' => $payload['id'],
                'X-Overshark-Signature' => self::signature($body, (string) $endpoint->secret),
            ])->connectTimeout(5)->timeout(10)->withBody($body, 'application/json')->post($endpoint->url);

            $result = $response->successful()
                ? ['ok' => true, 'status' => $response->status(), 'message' => 'Respondió HTTP '.$response->status().'.']
                : ['ok' => false, 'status' => $response->status(), 'message' => 'Respondió HTTP '.$response->status().'.'];
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'status' => null, 'message' => Str::limit('Sin conexión: '.$e->getMessage(), 480)];
        }

        $endpoint->forceFill([
            'last_status' => $result['status'],
            'last_error' => $result['ok'] ? null : $result['message'],
            'last_attempt_at' => now(),
        ])->saveQuietly();

        return $result;
    }
}
