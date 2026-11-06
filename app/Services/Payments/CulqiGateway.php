<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cobros con tarjeta y Yape por Culqi. El navegador envía los datos de la tarjeta (o el celular y el código de
 * aprobación de Yape) directo a Culqi con la llave pública y recibe un token (resources/js/checkout.js): la web
 * nunca recibe el número de tarjeta. Aquí se crea el cargo con ese token y la llave secreta: POST /charges.
 */
class CulqiGateway
{
    public static function isConfigured(): bool
    {
        return filled(config('services.culqi.public_key')) && filled(config('services.culqi.secret_key'));
    }

    public static function publicKey(): ?string
    {
        return config('services.culqi.public_key');
    }

    /** Dónde el navegador crea los tokens (tarjeta: /tokens, Yape: /tokens/yape). */
    public static function secureUrl(): string
    {
        return rtrim((string) config('services.culqi.secure_url'), '/');
    }

    /**
     * Cobra $amount soles con el token de la tarjeta.
     *
     * @return array{ok: bool, id: ?string, message: string}
     */
    public function charge(float $amount, string $token, string $email, string $description, array $metadata = []): array
    {
        // Modo simulación: los tokens de prueba no llegan a Culqi.
        if (PaymentSimulator::enabled() && PaymentSimulator::isSimulatedToken($token)) {
            return PaymentSimulator::charge($amount, $token);
        }

        try {
            $response = Http::baseUrl((string) config('services.culqi.base_url'))
                ->withToken((string) config('services.culqi.secret_key'))
                ->timeout((int) config('services.culqi.timeout', 30))
                ->acceptJson()
                ->post('/charges', [
                    'amount' => (int) round($amount * 100),
                    'currency_code' => 'PEN',
                    'email' => $email,
                    'source_id' => $token,
                    'description' => mb_substr($description, 0, 80),
                    'metadata' => $metadata,
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Culqi: sin conexión al cobrar', ['error' => $exception->getMessage()]);

            return ['ok' => false, 'id' => null, 'message' => 'No pudimos conectar con el procesador de pagos. Tu tarjeta no fue cobrada; intenta de nuevo.'];
        }

        $body = $response->json() ?? [];

        if ($response->successful() && ($body['object'] ?? null) === 'charge') {
            return ['ok' => true, 'id' => $body['id'] ?? null, 'message' => $body['outcome']['user_message'] ?? 'Pago aprobado.'];
        }

        Log::info('Culqi: cargo rechazado', ['status' => $response->status(), 'merchant_message' => $body['merchant_message'] ?? null]);

        return ['ok' => false, 'id' => null, 'message' => $body['user_message'] ?? 'Tu tarjeta fue rechazada. Prueba con otra tarjeta u otro medio de pago.'];
    }
}
