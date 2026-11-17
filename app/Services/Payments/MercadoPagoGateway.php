<?php

namespace App\Services\Payments;

use App\Models\Admin\Order;
use App\Models\Admin\PaymentGateway;
use App\Services\Orders\OrderPayments;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Checkout Pro de Mercado Pago (https://www.mercadopago.com.pe/developers/es/reference).
 *
 * El pedido se paga en el checkout hospedado de Mercado Pago (preferencia con external_reference = id del pedido).
 * Se confirma consultando el pago a la API con el Access Token de la tienda: por el webhook firmado, al volver la
 * clienta (back_urls) o cuando pulsa "Ya pagué". Nunca se confía en el estado que llega en la URL o en el aviso.
 */
class MercadoPagoGateway
{
    public const API = 'https://api.mercadopago.com';

    /** Evento «Alertas de fraude»: Mercado Pago recomienda no despachar el pedido de ese pago. */
    public const FRAUD_ALERT = 'stop_delivery_op_wh';

    private ?PaymentGateway $settings = null;

    public function settings(): ?PaymentGateway
    {
        return $this->settings ??= PaymentGateway::query()->where('provider', 'mercadopago')->first();
    }

    public function isProduction(): bool
    {
        return $this->settings()?->environment === 'production';
    }

    /** Crea la preferencia del pedido y devuelve la URL del checkout de Mercado Pago (null si no se pudo). */
    public function createCheckout(Order $order): ?string
    {
        $token = $this->settings()?->credential('access_token');
        if (! $token) {
            return null;
        }

        try {
            $response = $this->http($token)
                ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
                ->post(self::API.'/checkout/preferences', $this->preference($order));
        } catch (\Throwable $e) {
            Log::warning('Mercado Pago: sin conexión al crear la preferencia', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Mercado Pago: no se pudo crear la preferencia', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'error' => $response->json('message') ?? $response->json('error'),
                'cause' => $response->json('cause'),
            ]);

            return null;
        }

        return $this->isProduction()
            ? $response->json('init_point')
            : ($response->json('sandbox_init_point') ?? $response->json('init_point'));
    }

    /** Cuerpo de POST /checkout/preferences para el importe que el pedido debe pagar en línea (total o separo). */
    public function preference(Order $order): array
    {
        $order->loadMissing('cliente');
        $names = $order->cliente?->name ?: Str::before((string) $order->cliente_name, ' ');
        $surnames = $order->cliente?->apellidos ?: Str::after((string) $order->cliente_name, ' ');
        $phone = preg_replace('/\D+/', '', (string) $order->cliente_cel);
        $document = (string) $order->cliente?->documento_identidad;
        $documentType = strtoupper((string) $order->cliente?->tipoDocumento?->code);
        $concept = $order->isCashOnDelivery() ? 'Separo del pedido Overshark ' : 'Pedido Overshark ';

        $preference = [
            'items' => [[
                'id' => (string) ($order->number_zazu ?: $order->id),
                'title' => $concept.$order->displayNumber(),
                'quantity' => 1,
                'currency_id' => 'PEN',
                'unit_price' => round((float) $order->pago_monto, 2),
            ]],
            'payer' => array_filter([
                'name' => $names ?: null,
                'surname' => $surnames ?: null,
                'email' => $order->email ?: null,
                'phone' => $phone !== '' ? ['area_code' => '51', 'number' => $phone] : null,
                // Solo DNI: el carné de extranjería tiene otro código en Mercado Pago y un tipo inválido rechaza la preferencia.
                'identification' => $document !== '' && $documentType === 'DNI'
                    ? ['type' => 'DNI', 'number' => $document] : null,
            ]),
            'external_reference' => (string) $order->id,
            'back_urls' => [
                'success' => route('web.checkout.done', $order),
                'pending' => route('web.checkout.done', $order),
                'failure' => route('web.checkout.done', $order),
            ],
            // Solo aprobado o rechazado al instante: el pedido se reserva unos minutos y no puede esperar pagos en efectivo.
            'binary_mode' => true,
            'statement_descriptor' => 'OVERSHARK',
            'metadata' => ['order_id' => $order->id, 'order_number' => $order->number_zazu],
        ];

        // Mercado Pago no acepta retorno automático ni avisos hacia direcciones locales (http://localhost).
        $webhook = route('web.payments.mercadopago.webhook', ['source_news' => 'webhooks']);
        if (str_starts_with($webhook, 'https://')) {
            $preference['notification_url'] = $webhook;
        }
        if (str_starts_with($preference['back_urls']['success'], 'https://')) {
            $preference['auto_return'] = 'approved';
        }

        // La preferencia vence con la reserva del pedido: después ya no se puede pagar un pedido cancelado.
        if ($order->pago_qr_expira) {
            $preference['expires'] = true;
            $preference['expiration_date_to'] = $order->pago_qr_expira->copy()->setTimezone('America/Lima')->format('Y-m-d\TH:i:s.vP');
        }

        return $preference;
    }

    /**
     * Valida x-signature (ts=...,v1=...) con el secreto del webhook: HMAC-SHA256 del manifiesto
     * "id:[data.id];request-id:[x-request-id];ts:[ts];", omitiendo las partes que no llegaron.
     * Sin secreto guardado no hay firma que validar: el pago igual se comprueba consultándolo a la API.
     */
    public function signatureIsValid(string $dataId, ?string $signature, ?string $requestId): bool
    {
        $secret = $this->settings()?->credential('webhook_secret');
        if (! $secret) {
            return true;
        }

        $parts = collect(explode(',', (string) $signature))->mapWithKeys(function (string $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

            return [trim((string) $key) => trim((string) $value)];
        });
        $ts = $parts->get('ts');
        $v1 = $parts->get('v1');
        if (! $ts || ! $v1) {
            return false;
        }

        $manifest = ($dataId !== '' ? 'id:'.(ctype_alnum($dataId) ? strtolower($dataId) : $dataId).';' : '')
            .(filled($requestId) ? 'request-id:'.$requestId.';' : '')
            .'ts:'.$ts.';';

        return hash_equals(hash_hmac('sha256', $manifest, $secret), $v1);
    }

    /**
     * Tipo de evento e id del recurso de un aviso. Webhooks: ?data.id=123&type=payment (PHP convierte "data.id" en
     * "data_id") y el mismo dato en el cuerpo JSON. Los avisos IPN (topic/id) no se usan.
     *
     * url_id es el data.id de la URL (el que firma Mercado Pago); id usa el del cuerpo si la URL no lo trae.
     *
     * @return array{type: ?string, id: string, url_id: string}
     */
    public static function notification(Request $request): array
    {
        $raw = [];
        foreach (explode('&', (string) $request->server('QUERY_STRING')) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $raw[urldecode($key)] = urldecode($value);
        }
        $urlId = trim((string) ($raw['data.id'] ?? $request->query('data_id') ?? ''));
        $bodyId = $request->json('data.id');

        return [
            'type' => $raw['type'] ?? $request->query('type') ?? $request->json('type'),
            'id' => $urlId !== '' ? $urlId : trim(is_scalar($bodyId) ? (string) $bodyId : ''),
            'url_id' => $urlId,
        ];
    }

    /**
     * Marca el pedido del pago de una alerta de fraude (data.payment_id). Lo busca por la referencia guardada al
     * confirmarlo o, si aún no la tiene, consultando el pago (external_reference). Devuelve el pedido marcado o null
     * si el pago no es de la tienda; lanza excepción si la API falla, para que el aviso se reintente.
     */
    public function flagFraud(array $data): ?Order
    {
        $paymentId = trim(is_scalar($data['payment_id'] ?? null) ? (string) $data['payment_id'] : '');
        if (! ctype_digit($paymentId)) {
            return null;
        }

        $order = Order::query()->where('payment_gateway', 'mercadopago')->where('pago_referencia', $paymentId)->first();
        if (! $order && ($payment = $this->payment($paymentId))) {
            $order = Order::query()->where('payment_gateway', 'mercadopago')->find((int) ($payment['external_reference'] ?? 0));
        }
        if (! $order) {
            Log::info('Mercado Pago: alerta de fraude de un pago que no es de la tienda', ['payment_id' => $paymentId]);

            return null;
        }

        $description = trim(is_scalar($data['description'] ?? null) ? (string) $data['description'] : '');
        $order->update([
            'fraud_alert_at' => now(),
            'fraud_alert_detail' => Str::limit('Pago '.$paymentId.($description !== '' ? ': '.$description : ''), 590),
            'fraud_alert_reviewed_at' => null,
        ]);
        Log::warning('Mercado Pago: alerta de fraude, no despachar el pedido', ['order_id' => $order->id, 'payment_id' => $paymentId]);

        return $order;
    }

    /**
     * Pago tal como lo tiene Mercado Pago. Null si no existe para esta cuenta (404/400); lanza excepción si la API
     * falla o rechaza el token, para que el aviso se reintente.
     */
    public function payment(string $paymentId): ?array
    {
        $token = $this->settings()?->credential('access_token');
        if (! $token || ! ctype_digit($paymentId)) {
            return null;
        }

        $response = $this->http($token)->get(self::API.'/v1/payments/'.$paymentId);
        if ($response->successful()) {
            return $response->json();
        }
        if (in_array($response->status(), [400, 404], true)) {
            return null;
        }
        $response->throw();

        return null;
    }

    /** Último pago aprobado del pedido (busca por external_reference), para "Ya pagué" si el aviso no llegó. */
    public function approvedPaymentFor(Order $order): ?array
    {
        $token = $this->settings()?->credential('access_token');
        if (! $token) {
            return null;
        }

        $response = $this->http($token)->get(self::API.'/v1/payments/search', [
            'external_reference' => (string) $order->id,
            'sort' => 'date_created',
            'criteria' => 'desc',
        ]);

        return $response->successful()
            ? collect($response->json('results', []))->firstWhere('status', 'approved')
            : null;
    }

    /** Confirma el pedido del pago si es aprobado y coincide (pedido, importe, soles). Devuelve si quedó pagado. */
    public function apply(array $payment): bool
    {
        $order = Order::query()->find((int) ($payment['external_reference'] ?? 0));
        if (! $order) {
            return false;
        }

        $confirmed = OrderPayments::confirmMercadoPago($order, $payment, $this->isProduction());
        if (! $confirmed && ($payment['status'] ?? null) === 'approved') {
            Log::warning('Mercado Pago: pago aprobado que no coincide con el pedido o llegó tarde', [
                'payment_id' => $payment['id'] ?? null,
                'order_id' => $order->id,
                'amount' => $payment['transaction_amount'] ?? null,
                'currency' => $payment['currency_id'] ?? null,
                'live_mode' => $payment['live_mode'] ?? null,
            ]);
        }

        return $confirmed;
    }

    /**
     * Comprueba el Access Token contra Mercado Pago (GET /users/me).
     *
     * @return array{ok: bool, definitive: bool, message: string}
     */
    public function checkCredentials(string $token, string $environment): array
    {
        if ($environment === 'production' && str_starts_with($token, 'TEST-')) {
            return ['ok' => false, 'definitive' => true, 'message' => 'El Access Token es de prueba (TEST-) y el ambiente está en Producción. Usa las credenciales de producción (APP_USR-) o cambia el ambiente a Pruebas.'];
        }

        try {
            $response = $this->http($token)->timeout(10)->get(self::API.'/users/me');
        } catch (\Throwable) {
            return ['ok' => false, 'definitive' => false, 'message' => 'No se pudo contactar a Mercado Pago para comprobar el Access Token. Inténtalo de nuevo en unos minutos.'];
        }

        if (in_array($response->status(), [401, 403], true)) {
            return ['ok' => false, 'definitive' => true, 'message' => 'Mercado Pago rechazó el Access Token. Cópialo de nuevo desde Tus integraciones > Credenciales (sin espacios).'];
        }
        if (! $response->successful()) {
            return ['ok' => false, 'definitive' => false, 'message' => 'Mercado Pago no respondió bien al comprobar el Access Token (HTTP '.$response->status().'). Inténtalo de nuevo en unos minutos.'];
        }

        $site = $response->json('site_id');
        if ($site && $site !== 'MPE') {
            return ['ok' => false, 'definitive' => true, 'message' => 'La cuenta de Mercado Pago es de otro país ('.$site.'). La tienda cobra en soles: usa una cuenta de Mercado Pago Perú.'];
        }

        $account = $response->json('nickname') ?: $response->json('email') ?: 'ID '.$response->json('id');

        return ['ok' => true, 'definitive' => true, 'message' => 'Conectado a la cuenta de Mercado Pago '.$account.($site ? ' ('.$site.')' : '').'.'];
    }

    private function http(string $token): PendingRequest
    {
        return Http::withToken($token)->acceptJson()->asJson()->timeout(20);
    }
}
