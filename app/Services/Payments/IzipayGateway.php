<?php

namespace App\Services\Payments;

use App\Models\Admin\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cobros con tarjeta por Izipay (formulario incrustado, REST V4). El servidor crea el pago con
 * Charge/CreatePayment y recibe un formToken; el navegador muestra el formulario con ese token (los datos de la
 * tarjeta nunca pasan por la web). El resultado llega dos veces —al navegador (kr-post-url-success) y al servidor
 * (notificación IPN)— y SOLO se da por pagado si la firma kr-hash es válida: HMAC-SHA256 de kr-answer con la clave
 * que indica kr-hash-key ("sha256_hmac" = clave HMAC-SHA-256; "password" = contraseña de la API REST).
 */
class IzipayGateway
{
    public static function isConfigured(): bool
    {
        return filled(config('services.izipay.shop_id'))
            && filled(config('services.izipay.password'))
            && filled(config('services.izipay.public_key'))
            && filled(config('services.izipay.hmac_key'));
    }

    /** Llave pública del formulario: "idTienda:llavePública". */
    public static function publicKey(): ?string
    {
        return self::isConfigured() ? config('services.izipay.shop_id').':'.config('services.izipay.public_key') : null;
    }

    /** Script del formulario incrustado y su tema (estilos), según el entorno de las llaves (test o producción). */
    public static function scriptUrl(): string
    {
        return rtrim((string) config('services.izipay.static_url'), '/').'/js/krypton-client/V4.0/stable/kr-payment-form.min.js';
    }

    public static function themeCssUrl(): string
    {
        return rtrim((string) config('services.izipay.static_url'), '/').'/js/krypton-client/V4.0/ext/classic-reset.css';
    }

    public static function themeScriptUrl(): string
    {
        return rtrim((string) config('services.izipay.static_url'), '/').'/js/krypton-client/V4.0/ext/classic.js';
    }

    /**
     * Crea el pago del pedido por $amount soles y devuelve el formToken con el que el navegador abre el formulario.
     *
     * @return array{ok: bool, token: ?string, message: string}
     */
    public function createFormToken(Order $order, float $amount): array
    {
        $name = trim((string) $order->cliente_name);
        [$first, $last] = array_pad(explode(' ', $name, 2), 2, '');

        try {
            $response = Http::baseUrl(rtrim((string) config('services.izipay.api_url'), '/'))
                ->withBasicAuth((string) config('services.izipay.shop_id'), (string) config('services.izipay.password'))
                ->timeout((int) config('services.izipay.timeout', 30))
                ->acceptJson()
                ->post('/Charge/CreatePayment', [
                    'amount' => (int) round($amount * 100),
                    'currency' => 'PEN',
                    'orderId' => $order->number_zazu,
                    'customer' => [
                        'email' => $order->email,
                        'billingDetails' => array_filter([
                            'firstName' => $first,
                            'lastName' => $last,
                            'identityCode' => $order->cliente_doc_identidad,
                            'cellPhoneNumber' => $order->cliente_cel,
                            'address' => $order->direccion,
                            'country' => 'PE',
                        ], fn ($value) => filled($value)),
                    ],
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Izipay: sin conexión al crear el pago', ['error' => $exception->getMessage()]);

            return ['ok' => false, 'token' => null, 'message' => 'No pudimos conectar con el procesador de pagos. No se hizo ningún cobro; intenta de nuevo.'];
        }

        $body = $response->json() ?? [];
        $token = $body['answer']['formToken'] ?? null;

        if ($response->successful() && ($body['status'] ?? null) === 'SUCCESS' && filled($token)) {
            return ['ok' => true, 'token' => $token, 'message' => ''];
        }

        Log::warning('Izipay: no se pudo crear el pago', [
            'http' => $response->status(),
            'code' => $body['answer']['errorCode'] ?? null,
            'detail' => $body['answer']['errorMessage'] ?? null,
        ]);

        return ['ok' => false, 'token' => null, 'message' => 'No pudimos iniciar el pago con tarjeta. No se hizo ningún cobro; intenta de nuevo en unos minutos.'];
    }

    /**
     * Valida la firma de una respuesta de Izipay (navegador o IPN) y devuelve lo que importa del pago, o null si la
     * firma no es válida o la respuesta está incompleta.
     *
     * @param  array<string, mixed>  $payload  campos kr-answer, kr-hash y kr-hash-key recibidos
     * @return array{order_id: string, paid: bool, amount: float, currency: string, uuid: ?string}|null
     */
    public static function verify(array $payload): ?array
    {
        $answer = $payload['kr-answer'] ?? null;
        $hash = $payload['kr-hash'] ?? null;

        if (! is_string($answer) || ! is_string($hash) || $answer === '' || $hash === '') {
            return null;
        }

        $key = match ($payload['kr-hash-key'] ?? 'sha256_hmac') {
            'password' => config('services.izipay.password'),
            'sha256_hmac' => config('services.izipay.hmac_key'),
            default => null,
        };

        if (blank($key) || ! hash_equals(hash_hmac('sha256', $answer, (string) $key), strtolower($hash))) {
            return null;
        }

        $data = json_decode($answer, true);
        $orderId = $data['orderDetails']['orderId'] ?? null;

        if (! is_array($data) || ! is_string($orderId) || $orderId === '') {
            return null;
        }

        $transaction = $data['transactions'][0] ?? [];

        return [
            'order_id' => $orderId,
            'paid' => ($data['orderStatus'] ?? null) === 'PAID',
            'amount' => ((int) ($data['orderDetails']['orderTotalAmount'] ?? 0)) / 100,
            'currency' => (string) ($data['orderDetails']['orderCurrency'] ?? ''),
            'uuid' => $transaction['uuid'] ?? null,
        ];
    }
}
