<?php

namespace App\Services\Orders\Tasks;

use App\Models\Admin\Order;

/**
 * Una tarea posterior al pago (App\Services\Orders\OrderFulfillment). handle() devuelve el resultado
 * (status listo u omitido, mensaje y datos) o lanza una excepción para que la cola la reintente.
 */
interface OrderTaskHandler
{
    /** @return array{status: string, message: string, data?: array} */
    public function handle(Order $order): array;
}
