<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Admin\Order;
use App\Services\Integrations\OrderPayload;
use App\Services\Orders\OrderStatus;
use App\Support\Checkout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Pedidos de la tienda para sistemas externos. GET /api/v1/orders lista por defecto los confirmados (pagados,
 * contraentrega, enviados, entregados); status=all incluye los que esperan pago y los anulados.
 */
class OrderController extends Controller
{
    /** Pedidos que ya existen como venta: lo que un sistema externo prepara, envía o factura. */
    private const CONFIRMED = [...Checkout::PREPARATION_STATUSES, 'Enviado', 'Entregado'];

    /** Alias en inglés aceptados por PATCH /status. */
    private const STATUS_ALIASES = ['shipped' => 'Enviado', 'delivered' => 'Entregado', 'cancelled' => Checkout::STATUS_CANCELLED];

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'max:60'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'updated_since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $status = $filters['status'] ?? null;

        $orders = Order::query()
            ->with(['statusOrder', 'distrito.provincia.departamento', 'cliente.tipoDocumento'])
            ->when($status === null, fn (Builder $query) => $query->whereHas('statusOrder', fn ($inner) => $inner->whereIn('name', self::CONFIRMED)))
            ->when($status !== null && $status !== 'all', fn (Builder $query) => $query->whereHas('statusOrder', fn ($inner) => $inner->where('name', self::STATUS_ALIASES[$status] ?? $status)))
            ->when(filled($filters['from'] ?? null), fn (Builder $query) => $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay()))
            ->when(filled($filters['to'] ?? null), fn (Builder $query) => $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay()))
            ->when(filled($filters['updated_since'] ?? null), fn (Builder $query) => $query->where('updated_at', '>=', Carbon::parse($filters['updated_since'])))
            ->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 50));

        return response()->json([
            'data' => $orders->getCollection()->map(fn (Order $order) => OrderPayload::from($order))->all(),
            'meta' => ['page' => $orders->currentPage(), 'per_page' => $orders->perPage(), 'total' => $orders->total(), 'last_page' => $orders->lastPage()],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json(['data' => OrderPayload::from($order)]);
    }

    /**
     * PATCH /api/v1/orders/{id}/status {"status": "Enviado" | "Entregado" | "Cancelado"} (o shipped, delivered,
     * cancelled). Solo para pedidos confirmados: los estados de pago los pone únicamente la tienda.
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->merge(['status' => self::STATUS_ALIASES[$request->input('status')] ?? $request->input('status')]);
        $validated = $request->validate(['status' => ['required', 'in:'.implode(',', OrderStatus::MANUAL)]], [
            'status.in' => 'El estado debe ser uno de: '.implode(', ', OrderStatus::MANUAL).' (o shipped, delivered, cancelled).',
        ]);

        if (! in_array($order->loadMissing('statusOrder')->statusName(), self::CONFIRMED, true)) {
            return response()->json(['message' => 'El pedido está «'.$order->statusName().'»: solo se cambia el estado de pedidos confirmados.'], 409);
        }

        OrderStatus::change($order, $validated['status']);

        return response()->json(['data' => OrderPayload::from($order)]);
    }
}
