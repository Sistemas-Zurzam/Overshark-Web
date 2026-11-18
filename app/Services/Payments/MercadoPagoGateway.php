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
 * Mercado Pago (https://www.mercadopago.com.pe/developers/es/reference).
 *
 * Con Public Key guardada el pedido se paga en la tienda con el Card Payment Brick (Checkout Bricks): los datos de la
 * tarjeta van del navegador a Mercado Pago, que devuelve un token de un solo uso, y el servidor crea el pago
 * (POST /v1/payments) por el importe del pedido. Sin Public Key se usa Checkout Pro (preferencia y redirección).
 * En ambos casos external_reference = id del pedido y el pedido se confirma con el pago leído de la API con el Access
 * Token de la tienda: por el webhook firmado, al volver la clienta o cuando pulsa "Ya pagué". Nunca se confía en el
 * estado que llega en la URL o en el aviso.
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

    public function publicKey(): ?string
    {
        return $this->settings()?->credential('public_key');
    }

    /** Con Public Key se cobra con el formulario de tarjeta incrustado (Card Payment Brick) en vez de Checkout Pro. */
    public function usesCardForm(): bool
    {
        return filled($this->publicKey());
    }

    /**
     * Crea el pago con el token del Card Payment Brick por el importe que el pedido debe pagar (nunca el que manda el
     * navegador). Devuelve el pago creado o, si Mercado Pago no lo aceptó, un mensaje para la clienta.
     *
     * @param  array{token: string, payment_method_id: string, issuer_id?: mixed, installments: int, payer: array}  $form
     * @return array{payment: ?array, message: ?string}
     */
    public function chargeCard(Order $order, array $form, ?string $deviceId = null): array
    {
        $token = $this->settings()?->credential('access_token');
        if (! $token) {
            return ['payment' => null, 'message' => 'El pago con Mercado Pago no está activo en la tienda.'];
        }

        $order->loadMissing('cliente');
        $identification = array_filter([
            'type' => data_get($form, 'payer.identification.type'),
            'number' => data_get($form, 'payer.identification.number'),
        ]);
        $body = [
            'transaction_amount' => round((float) $order->pago_monto, 2),
            'token' => $form['token'],
            'installments' => (int) $form['installments'],
            'payment_method_id' => $form['payment_method_id'],
            'issuer_id' => filled($form['issuer_id'] ?? null) ? (int) $form['issuer_id'] : null,
            'payer' => array_filter([
                'email' => data_get($form, 'payer.email') ?: $order->email,
                'first_name' => $this->payerNames($order)[0] ?: null,
                'last_name' => $this->payerNames($order)[1] ?: null,
                'identification' => count($identification) === 2 ? $identification : null,
            ]),
            'description' => $this->concept($order),
            'external_reference' => (string) $order->id,
            'statement_descriptor' => 'OVERSHARK',
            // Solo aprobado o rechazado al instante: el pedido se reserva unos minutos.
            'binary_mode' => true,
            'metadata' => ['order_id' => $order->id, 'order_number' => $order->number_zazu],
            'additional_info' => [
                'items' => collect($order->productos_json ?? [])->map(fn ($line) => [
                    'id' => (string) ($line['variant_id'] ?? $line['combo_id'] ?? ''),
                    'title' => Str::limit(trim(implode(' ', array_filter([is_string($line['producto'] ?? null) ? $line['producto'] : 'Producto Overshark', $line['color'] ?? null, $line['talla'] ?? null]))), 250, ''),
                    'quantity' => max(1, (int) ($line['cantidad'] ?? 1)),
                    'unit_price' => round((float) ($line['precio'] ?? 0), 2),
                ])->values()->all(),
            ],
        ];
        if ($webhook = $this->webhookUrl()) {
            $body['notification_url'] = $webhook;
        }

        try {
            $response = $this->http($token)
                // El token de la tarjeta es de un solo uso: el mismo envío repetido no genera dos cobros.
                ->withHeaders(array_filter([
                    'X-Idempotency-Key' => 'os-'.$order->id.'-'.sha1($form['token']),
                    'X-meli-session-id' => $deviceId ?: null,
                ]))
                ->post(self::API.'/v1/payments', array_filter($body, fn ($value) => $value !== null));
        } catch (\Throwable $e) {
            Log::warning('Mercado Pago: sin conexión al crear el pago con tarjeta', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return ['payment' => null, 'message' => 'No pudimos conectar con Mercado Pago. No se hizo ningún cobro; inténtalo de nuevo.'];
        }

        if (! $response->successful()) {
            Log::warning('Mercado Pago: no se pudo crear el pago con tarjeta', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'error' => $response->json('message') ?? $response->json('error'),
                'cause' => $response->json('cause'),
            ]);

            return ['payment' => null, 'message' => 'Mercado Pago no pudo procesar la tarjeta. Revisa los datos e inténtalo de nuevo.'];
        }

        return ['payment' => $response->json(), 'message' => null];
    }

    /** Mensaje para la clienta según el motivo de rechazo (status_detail) de Mercado Pago, con tarjeta o Yape. */
    public static function rejectionMessage(?string $detail, bool $yape = false): string
    {
        if ($yape) {
            return match ($detail) {
                'cc_rejected_insufficient_amount' => 'No tienes saldo suficiente en Yape o el monto supera tu límite por pago. Revisa tu app o paga con tarjeta.',
                'cc_rejected_bad_filled_security_code', 'cc_rejected_form_error' => 'El código de aprobación no es correcto o ya venció. Genera uno nuevo en tu app Yape e inténtalo de nuevo.',
                'cc_rejected_call_for_authorize' => 'Yape necesita que autorices este pago. Revisa tu app e inténtalo de nuevo.',
                'cc_rejected_max_attempts' => 'Llegaste al límite de intentos con Yape. Paga con tarjeta o inténtalo más tarde.',
                'cc_rejected_card_type_not_allowed' => 'Este Yape no puede usarse para pagar en línea. Paga con tarjeta.',
                default => 'Yape rechazó el pago. Genera un código de aprobación nuevo e inténtalo de nuevo, o paga con tarjeta.',
            };
        }

        return match ($detail) {
            'cc_rejected_insufficient_amount' => 'Tu tarjeta no tiene fondos suficientes. Prueba con otra tarjeta.',
            'cc_rejected_bad_filled_security_code' => 'El código de seguridad (CVV) no es correcto. Revísalo e inténtalo de nuevo.',
            'cc_rejected_bad_filled_date' => 'La fecha de vencimiento no es correcta. Revísala e inténtalo de nuevo.',
            'cc_rejected_bad_filled_card_number' => 'El número de tarjeta no es correcto. Revísalo e inténtalo de nuevo.',
            'cc_rejected_bad_filled_other' => 'Algún dato de la tarjeta no es correcto. Revísalos e inténtalo de nuevo.',
            'cc_rejected_call_for_authorize' => 'Tu banco necesita que autorices el pago. Llámalo y vuelve a intentarlo, o usa otra tarjeta.',
            'cc_rejected_card_disabled' => 'Tu tarjeta no está activa. Llama a tu banco para activarla o usa otra tarjeta.',
            'cc_rejected_duplicated_payment' => 'Ya hiciste un pago por este mismo importe. Si necesitas pagar de nuevo, usa otra tarjeta.',
            'cc_rejected_max_attempts' => 'Llegaste al límite de intentos con esta tarjeta. Usa otra tarjeta.',
            'cc_rejected_high_risk', 'rejected_high_risk', 'rejected_by_regulations' => 'Mercado Pago rechazó el pago. Prueba con otra tarjeta.',
            'cc_rejected_blacklist', 'cc_rejected_card_error', 'cc_rejected_card_type_not_allowed' => 'No pudimos procesar el pago con esta tarjeta. Prueba con otra.',
            default => 'Tu banco rechazó el pago. Prueba con otra tarjeta o con otro medio de pago.',
        };
    }

    /** Crea la preferencia del pedido y devuelve la URL del checkout de Mercado Pago (null si no se pudo). */
    public function createCheckout(Order $order): ?string
    {
        $preference = $this->createPreference($order);
        if (! $preference) {
            return null;
        }

        return $this->isProduction()
            ? ($preference['init_point'] ?? null)
            : ($preference['sandbox_init_point'] ?? $preference['init_point'] ?? null);
    }

    /**
     * Id de una preferencia nueva del pedido, para la opción "Cuenta de Mercado Pago" del Payment Brick (la clienta
     * paga con su saldo o tarjetas guardadas en Mercado Pago y vuelve por back_urls). Null si no se pudo crear.
     */
    public function createPreferenceId(Order $order): ?string
    {
        return $this->createPreference($order)['id'] ?? null;
    }

    /** POST /checkout/preferences; devuelve la respuesta de Mercado Pago o null si falló. */
    private function createPreference(Order $order): ?array
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

        return $response->json();
    }

    /** Cuerpo de POST /checkout/preferences para el importe que el pedido debe pagar en línea (total o separo). */
    public function preference(Order $order): array
    {
        $order->loadMissing('cliente');
        [$names, $surnames] = $this->payerNames($order);
        $phone = preg_replace('/\D+/', '', (string) $order->cliente_cel);
        $document = (string) $order->cliente?->documento_identidad;
        $documentType = strtoupper((string) $order->cliente?->tipoDocumento?->code);

        $preference = [
            'items' => [[
                'id' => (string) ($order->number_zazu ?: $order->id),
                'title' => $this->concept($order),
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
        if ($webhook = $this->webhookUrl()) {
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

    /** Concepto del cobro: el pedido completo o, en contraentrega, solo su separo. */
    private function concept(Order $order): string
    {
        return ($order->isCashOnDelivery() ? 'Separo del pedido Overshark ' : 'Pedido Overshark ').$order->displayNumber();
    }

    /** @return array{0: string, 1: string} nombres y apellidos de quien paga */
    private function payerNames(Order $order): array
    {
        return [
            (string) ($order->cliente?->name ?: Str::before((string) $order->cliente_name, ' ')),
            (string) ($order->cliente?->apellidos ?: Str::after((string) $order->cliente_name, ' ')),
        ];
    }

    /** URL del webhook para cada pago; null en direcciones locales (Mercado Pago solo avisa a https). */
    private function webhookUrl(): ?string
    {
        $webhook = route('web.payments.mercadopago.webhook', ['source_news' => 'webhooks']);

        return str_starts_with($webhook, 'https://') ? $webhook : null;
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
    public function checkCredentials(string $token, string $environment, ?string $publicKey = null): array
    {
        if ($environment === 'production' && str_starts_with($token, 'TEST-')) {
            return ['ok' => false, 'definitive' => true, 'message' => 'El Access Token es de prueba (TEST-) y el ambiente está en Producción. Usa las credenciales de producción (APP_USR-) o cambia el ambiente a Pruebas.'];
        }
        if ($environment === 'production' && str_starts_with((string) $publicKey, 'TEST-')) {
            return ['ok' => false, 'definitive' => true, 'message' => 'La Public Key es de prueba (TEST-) y el ambiente está en Producción. Usa la Public Key de producción (APP_USR-), de la misma aplicación que el Access Token.'];
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
