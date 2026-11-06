<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * Yape con QR de Ligo Pay. Punto de integración pendiente: falta la documentación de la API de Ligo
 * (autenticación, creación del QR dinámico por monto y confirmación del pago por webhook o consulta).
 *
 * Cuando se tenga:
 *  1. Implementar createQr() (QR por el monto del pedido o del separo) y paymentStatus().
 *  2. Mostrar el QR en web/checkout/partials/ligo-yape-qr.blade.php al crear el pedido y confirmar el pago.
 *  3. Cambiar isAvailable() a `return self::isEnabled();` y activar LIGO_PAY_ENABLED con sus credenciales.
 * Mientras tanto "Yape" se ve en el checkout con un aviso y no se puede elegir para pagar.
 */
class LigoPayGateway
{
    public static function isEnabled(): bool
    {
        return (bool) config('services.ligo.enabled');
    }

    /** Si "Yape (Ligo Pay)" se puede usar para pagar. False hasta implementar createQr() y paymentStatus(). */
    public static function isAvailable(): bool
    {
        return false;
    }

    /** @return array{qr_image: string, reference: string, expires_at: ?string} */
    public function createQr(float $amount, string $reference): array
    {
        throw new RuntimeException('Integración Ligo Pay pendiente: falta la documentación de su API.');
    }

    public function paymentStatus(string $reference): string
    {
        throw new RuntimeException('Integración Ligo Pay pendiente: falta la documentación de su API.');
    }
}
