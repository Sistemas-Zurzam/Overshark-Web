<?php

namespace App\Services\Orders;

use App\Jobs\RunOrderTask;
use App\Models\Admin\Order;
use App\Models\Admin\OrderTask;
use App\Services\Orders\Tasks\RegisterCrmSale;
use App\Services\Orders\Tasks\SendOrderEmail;
use Illuminate\Support\Facades\Bus;
use Throwable;

/**
 * Lo que pasa después de que un pedido queda pagado (o confirmado, en contraentrega sin separo): tareas en
 * orden. 1) Registrar la venta en CRM-LIVEX-V2, que asigna la nota de venta y desde ahí la envía a Zazu, la
 * registra en el ERP y emite la boleta. 2) Enviar a la clienta el correo de confirmación.
 *
 * Cada tarea guarda su estado en order_tasks: se corre al confirmar el pago y, si falla, se reintenta sola en
 * la cola (RunOrderTask). Una tarea omitida (sin configurar) no detiene a las siguientes; una que agota sus
 * intentos queda fallida y se reintenta desde el admin.
 */
class OrderFulfillment
{
    public const TASKS = [
        'crm_venta' => RegisterCrmSale::class,
        'correo' => SendOrderEmail::class,
    ];

    public const LABELS = [
        'crm_venta' => 'Venta en el CRM (nota de venta, Zazu, ERP y boleta)',
        'correo' => 'Correo de confirmación a la clienta',
    ];

    /** Tareas que se corren en el momento de confirmar el pedido (el resto sale por la cola). */
    public const INLINE = ['crm_venta'];

    /**
     * Arranca la cadena una sola vez por pedido. La venta en el CRM se corre en el momento (así la clienta ve su
     * nota de venta al confirmar); lo demás, como el correo, sale por la cola para no frenar el pago. Si una
     * tarea falla, ella y las siguientes pasan a la cola, que las reintenta.
     */
    public static function start(Order $order): void
    {
        if ($order->tasks()->exists()) {
            return;
        }

        foreach (array_keys(self::TASKS) as $task) {
            $order->tasks()->create(['task' => $task, 'status' => OrderTask::PENDING]);
        }

        foreach (array_keys(self::TASKS) as $task) {
            if (! in_array($task, self::INLINE, true)) {
                self::dispatchFrom($order, $task);

                return;
            }

            try {
                (new RunOrderTask($order->id, $task))->handle();
            } catch (Throwable $exception) {
                report($exception);
                self::dispatchFrom($order, $task);

                return;
            }
        }
    }

    /** Vuelve a correr una tarea ya resuelta (p. ej. reenviar el correo de confirmación desde el admin). */
    public static function resend(Order $order, string $task): void
    {
        abort_unless(isset(self::TASKS[$task]), 404);

        $order->tasks()->where('task', $task)->update(['status' => OrderTask::PENDING, 'attempts' => 0, 'message' => null]);

        Bus::dispatch(new RunOrderTask($order->id, $task));
    }

    /** Reintenta una tarea fallida y las que siguen. */
    public static function retry(OrderTask $task): void
    {
        $keys = array_keys(self::TASKS);
        $following = array_slice($keys, (int) array_search($task->task, $keys, true));
        $task->order->tasks()->whereIn('task', $following)->where('status', '!=', OrderTask::DONE)
            ->update(['status' => OrderTask::PENDING, 'attempts' => 0, 'message' => null]);

        self::dispatchFrom($task->order, $task->task);
    }

    private static function dispatchFrom(Order $order, string $first): void
    {
        $keys = array_keys(self::TASKS);
        $jobs = array_map(fn (string $task) => new RunOrderTask($order->id, $task), array_slice($keys, (int) array_search($first, $keys, true)));

        Bus::chain($jobs)->dispatch();
    }
}
