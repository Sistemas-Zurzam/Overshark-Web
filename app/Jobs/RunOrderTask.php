<?php

namespace App\Jobs;

use App\Models\Admin\Order;
use App\Models\Admin\OrderTask;
use App\Services\Orders\OrderFulfillment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Ejecuta una tarea posterior al pago (OrderFulfillment::TASKS) y guarda el resultado en order_tasks.
 * Si el sistema externo falla, la cola la reintenta con espera creciente; al agotar los intentos queda
 * "fallido" y la cadena se detiene hasta reintentarla desde el admin.
 */
class RunOrderTask implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $orderId, public string $task) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(): void
    {
        $row = OrderTask::query()->where('order_id', $this->orderId)->where('task', $this->task)->first();
        $order = Order::query()->find($this->orderId);
        // Sin la tarea, sin el pedido, ya resuelta o de un tipo que ya no existe: no hay nada que hacer.
        if (! $row || ! $order || in_array($row->status, [OrderTask::DONE, OrderTask::SKIPPED], true) || ! isset(OrderFulfillment::TASKS[$this->task])) {
            return;
        }

        $row->increment('attempts');

        try {
            $result = app(OrderFulfillment::TASKS[$this->task])->handle($order);
        } catch (Throwable $exception) {
            $row->update(['message' => mb_substr($exception->getMessage(), 0, 500)]);
            throw $exception;
        }

        $row->update([
            'status' => $result['status'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
            'completed_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        OrderTask::query()->where('order_id', $this->orderId)->where('task', $this->task)
            ->update(['status' => OrderTask::FAILED]);
    }
}
