<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderPaidMail;
use App\Models\Admin\Order;
use App\Models\Admin\OrderTask;
use App\Models\Admin\StatusOrder;
use App\Services\Orders\OrderFulfillment;
use App\Services\Orders\OrderPayments;
use App\Support\Checkout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Pedidos de la tienda web (creados en el checkout, App\Support\Checkout). El equipo revisa la captura del
 * pago y cambia el estado; con "Pago confirmado" la clienta ve "¡Tu pedido está confirmado!".
 */
class OrderController extends Controller
{
    public const STATUSES = [Checkout::STATUS_PENDING, Checkout::STATUS_AWAITING_QR, Checkout::STATUS_PAID, Checkout::STATUS_DEPOSIT_PAID, 'Enviado', 'Entregado', Checkout::STATUS_CANCELLED];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('estado', ''));

        $orders = Order::query()
            ->with('statusOrder')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('number_zazu', 'like', "%{$search}%")
                ->orWhere('cliente_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('cliente_cel', 'like', "%{$search}%")))
            ->when($status !== '', fn ($query) => $query->whereHas('statusOrder', fn ($inner) => $inner->where('name', $status)))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.pedidos.index', ['orders' => $orders, 'statuses' => self::STATUSES]);
    }

    public function show(Order $order): View
    {
        return view('admin.pedidos.show', [
            'order' => $order->load(['statusOrder', 'distrito.provincia.departamento', 'tasks']),
            'statuses' => self::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', 'in:'.implode(',', self::STATUSES)]]);

        $order->update(['status_order_id' => StatusOrder::query()->firstOrCreate(['name' => $validated['status']])->id]);

        // Pago validado a mano (captura de Yape/Plin): arranca la nota de venta, el ERP, la boleta y el correo.
        if (in_array($validated['status'], OrderPayments::PAID_STATUSES, true)) {
            if (! $order->fecha_envio) {
                $order->update(['fecha_envio' => Checkout::shippingDate($order->entrega)]);
            }
            OrderFulfillment::start($order);
        }

        return back()->with('status', "Pedido {$order->number_zazu}: {$validated['status']}.");
    }

    /** Vista previa del correo de confirmación tal como le llega a la clienta (no envía nada). */
    public function emailPreview(Order $order): Response
    {
        return response((new OrderPaidMail($order->load('statusOrder')))->render());
    }

    public function resendEmail(Order $order): RedirectResponse
    {
        abort_unless($order->tasks()->where('task', 'correo')->exists(), 404);

        OrderFulfillment::resend($order, 'correo');

        return back()->with('status', "Reenviando el correo de confirmación del pedido {$order->displayNumber()}.");
    }

    public function retryTask(Order $order, OrderTask $task): RedirectResponse
    {
        abort_unless($task->order_id === $order->id, 404);

        OrderFulfillment::retry($task);

        return back()->with('status', 'Reintentando: '.(OrderFulfillment::LABELS[$task->task] ?? $task->task).'.');
    }
}
