<?php

namespace App\Services\Payments;

/**
 * Modo simulación (PAYMENTS_SIMULATE=true, nunca en producción): permite recorrer el checkout completo sin
 * llaves ni dinero real.
 *  - Tarjeta: el navegador no llama a Culqi y manda un token de prueba. 4000 0000 0000 0002 se rechaza; el
 *    resto de tarjetas válidas se aprueba.
 *  - Yape: se muestra un QR de prueba y un botón "Simular pago" en la página del pedido.
 *  - Después del pago, las tareas (nota de venta, ERP, boleta, correo) devuelven resultados de prueba.
 */
class PaymentSimulator
{
    public const CARD_OK = 'sim_tkn_ok';

    public const CARD_DECLINED = 'sim_tkn_declined';

    public static function enabled(): bool
    {
        return (bool) config('services.payments.simulate') && ! app()->isProduction();
    }

    public static function isSimulatedToken(string $token): bool
    {
        return str_starts_with($token, 'sim_');
    }

    /** @return array{ok: bool, id: ?string, message: string} */
    public static function charge(float $amount, string $token): array
    {
        if ($token === self::CARD_DECLINED) {
            return ['ok' => false, 'id' => null, 'message' => 'Tarjeta rechazada (simulación): fondos insuficientes. Prueba con 4111 1111 1111 1111.'];
        }

        return ['ok' => true, 'id' => 'sim_chr_'.bin2hex(random_bytes(6)), 'message' => 'Pago aprobado (simulación).'];
    }

    /**
     * QR de prueba: una imagen SVG que se parece a un QR y dice "SIMULACIÓN". No se puede pagar con él.
     *
     * @return array{qr_image: string, reference: string, expires_at: \Illuminate\Support\Carbon}
     */
    public static function qr(float $amount, string $reference): array
    {
        $seed = crc32($reference);
        $cells = '';
        for ($y = 0; $y < 21; $y++) {
            for ($x = 0; $x < 21; $x++) {
                $finder = ($x < 7 && $y < 7) || ($x > 13 && $y < 7) || ($x < 7 && $y > 13);
                $on = $finder
                    ? (in_array($x % 14, [0, 6], true) || in_array($y % 14, [0, 6], true) || ($x % 14 >= 2 && $x % 14 <= 4 && $y % 14 >= 2 && $y % 14 <= 4))
                    : (($seed >> (($x * 7 + $y * 3) % 31)) & 1) === 1 && (($x + $y) % 3 !== 0);
                if ($on) {
                    $cells .= "<rect x=\"{$x}\" y=\"{$y}\" width=\"1\" height=\"1\"/>";
                }
            }
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-2 -2 25 25"><rect x="-2" y="-2" width="25" height="25" fill="#fff"/>'
            .'<g fill="#3d0a49">'.$cells.'</g><rect x="3" y="9" width="15" height="3.4" fill="#fff"/>'
            .'<text x="10.5" y="11.6" font-size="2.3" font-family="Arial" font-weight="700" text-anchor="middle" fill="#742384">SIMULACIÓN</text></svg>';

        return [
            'qr_image' => 'data:image/svg+xml;base64,'.base64_encode($svg),
            'reference' => 'sim_qr_'.$reference,
            'expires_at' => now()->addMinutes((int) config('services.payments.qr_minutes', 10)),
        ];
    }
}
