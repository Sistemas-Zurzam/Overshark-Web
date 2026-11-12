<?php

namespace App\Services\Payments;

/**
 * Modo simulación (PAYMENTS_SIMULATE=true): permite recorrer el checkout completo sin llaves ni dinero real.
 * Solo funciona en los entornos "local" y "testing" (lista permitida): con cualquier otro valor de APP_ENV —production,
 * staging, prod o uno mal escrito— queda apagado, porque encendido cualquiera podría "pagar" sus pedidos sin pagar.
 *  - Tarjeta: en lugar del formulario de Izipay se muestran los botones "Pagar (aprobado)" y "Pagar (rechazado)".
 *  - Después del pago, las tareas (nota de venta, ERP, boleta, correo) devuelven resultados de prueba.
 */
class PaymentSimulator
{
    public static function enabled(): bool
    {
        return (bool) config('services.payments.simulate') && app()->environment(['local', 'testing']);
    }
}
