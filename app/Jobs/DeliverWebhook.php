<?php

namespace App\Jobs;

use App\Models\Admin\WebhookEndpoint;
use App\Services\Integrations\Webhooks;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

/** Entrega un aviso de pedido a un destino externo; si no responde 2xx, la cola lo reintenta con espera creciente. */
class DeliverWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    public function __construct(public int $endpointId, public array $payload) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 300, 1800, 7200, 21600];
    }

    public function handle(): void
    {
        $endpoint = WebhookEndpoint::query()->find($this->endpointId);
        if (! $endpoint || ! $endpoint->enabled) {
            return; // borrado o pausado mientras esperaba
        }

        $result = Webhooks::send($endpoint, $this->payload);
        if (! $result['ok']) {
            throw new RuntimeException("Webhook {$endpoint->url}: {$result['message']}");
        }
    }
}
