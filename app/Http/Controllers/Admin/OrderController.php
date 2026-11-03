<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Order;
use App\Models\Admin\StatusOrder;
use App\Support\Checkout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pedidos de la tienda web (creados en el checkout, App\Support\Checkout). El equipo revisa la captura del
 * pago y cambia el estado; con "Pago confirmado" la clienta ve "¡Tu pedido está confirmado!".
 */
class OrderController extends Controller
{
    public const STATUSES = [Checkout::STATUS_PENDING, Checkout::STATUS_PAID, 'Enviado', 'Entregado', 'Cancelado'];

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
            'order' => $order->load(['statusOrder', 'distrito.provincia.departamento']),
            'statuses' => self::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', 'in:'.implode(',', self::STATUSES)]]);

        $order->update(['status_order_id' => StatusOrder::query()->firstOrCreate(['name' => $validated['status']])->id]);

        return back()->with('status', "Pedido {$order->number_zazu}: {$validated['status']}.");
    }
}
