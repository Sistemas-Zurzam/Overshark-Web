<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderPaidMail;
use App\Models\Admin\Order;
use App\Models\Admin\OrderTask;
use App\Models\Admin\Producto;
use App\Models\Admin\StatusOrder;
use App\Models\Brand;
use App\Services\Orders\OrderFulfillment;
use App\Services\Orders\OrderPayments;
use App\Support\Checkout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Pedidos de la tienda web (creados en el checkout, App\Support\Checkout). El equipo revisa la captura del
 * pago y cambia el estado; con "Pago confirmado" la clienta ve "¡Tu pedido está confirmado!".
 */
class OrderController extends Controller
{
    public const STATUSES = [Checkout::STATUS_PENDING, Checkout::STATUS_AWAITING_QR, Checkout::STATUS_PAID, Checkout::STATUS_DEPOSIT_PAID, 'Enviado', 'Entregado', Checkout::STATUS_CANCELLED];

    /** Agrupa los estados internos del checkout en las columnas de estado que ve el equipo. */
    private const STATUS_GROUPS = [
        'por_confirmar' => ['statuses' => [Checkout::STATUS_PENDING, Checkout::STATUS_AWAITING_QR], 'label' => 'Por confirmar pago', 'tone' => 'warning'],
        'preparacion' => ['statuses' => [Checkout::STATUS_PAID, Checkout::STATUS_DEPOSIT_PAID], 'label' => 'En preparación', 'tone' => 'info'],
        'enviado' => ['statuses' => ['Enviado'], 'label' => 'Enviado', 'tone' => 'info'],
        'entregado' => ['statuses' => ['Entregado'], 'label' => 'Entregado', 'tone' => 'success'],
        'anulado' => ['statuses' => [Checkout::STATUS_CANCELLED], 'label' => 'Anulado', 'tone' => 'neutral'],
    ];

    public function index(Request $request): View
    {
        $filters = $this->filtersFrom($request);
        $tab = array_key_exists((string) $request->query('tab'), self::STATUS_GROUPS) ? $request->query('tab') : '';

        $afterFilters = $this->filteredOrders($filters);

        $counts = ['all' => $afterFilters->count()];
        foreach (self::STATUS_GROUPS as $key => $group) {
            $counts[$key] = $afterFilters->where('status_group', $key)->count();
        }

        $filtered = $tab !== '' ? $afterFilters->where('status_group', $tab)->values() : $afterFilters;

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $orders = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'page', 'query' => $request->except('page')],
        );

        return view('admin.pedidos.index', [
            'orders' => $orders,
            'filters' => $filters,
            'tab' => $tab,
            'counts' => $counts,
            'statusGroups' => self::STATUS_GROUPS,
            'metodosPago' => $this->metodosPagoDisponibles(),
            'marcaOptions' => $this->marcaOptions(),
        ]);
    }

    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = $this->filtersFrom($request);
        $tab = array_key_exists((string) $request->query('tab'), self::STATUS_GROUPS) ? $request->query('tab') : '';

        $orders = $this->filteredOrders($filters);
        if ($tab !== '') {
            $orders = $orders->where('status_group', $tab)->values();
        }

        return response()->streamDownload(function () use ($orders): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Pedido', 'Cliente', 'Email', 'Marca', 'Medio de pago', 'Total', 'Estado', 'Fecha']);
            foreach ($orders as $order) {
                $group = self::STATUS_GROUPS[$order->status_group] ?? null;
                fputcsv($handle, [
                    $order->displayNumber(),
                    $order->cliente_name,
                    $order->email,
                    $order->marca_computed,
                    $order->metodo_pago_nombre,
                    number_format((float) $order->precio_total, 2),
                    $group['label'] ?? $order->statusOrder?->name,
                    $order->created_at?->format('d/m/Y H:i'),
                ]);
            }
            fclose($handle);
        }, 'pedidos-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
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

    private function filtersFrom(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'pago' => trim((string) $request->query('pago', '')),
            'marca' => trim((string) $request->query('marca', '')),
            'periodo' => in_array($request->query('periodo'), ['7', '30', 'mes'], true) ? $request->query('periodo') : '',
        ];
    }

    /** Pedidos filtrados por búsqueda/pago/período (SQL) + marca (calculada), con estado de grupo ya resuelto. */
    private function filteredOrders(array $filters): Collection
    {
        $periodStart = match ($filters['periodo']) {
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            'mes' => now()->startOfMonth(),
            default => null,
        };

        $orders = Order::query()
            ->with('statusOrder')
            ->when($filters['search'] !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('number_zazu', 'like', "%{$filters['search']}%")
                ->orWhere('nota_venta', 'like', "%{$filters['search']}%")
                ->orWhere('cliente_name', 'like', "%{$filters['search']}%")
                ->orWhere('email', 'like', "%{$filters['search']}%")
                ->orWhere('cliente_cel', 'like', "%{$filters['search']}%")
                ->orWhere('cliente_doc_identidad', 'like', "%{$filters['search']}%")))
            ->when($filters['pago'] !== '', fn ($query) => $query->where('metodo_pago_nombre', $filters['pago']))
            ->when($periodStart !== null, fn ($query) => $query->where('created_at', '>=', $periodStart))
            ->latest()
            ->get();

        $this->attachComputedFields($orders);

        return $filters['marca'] !== '' ? $orders->where('marca_computed', $filters['marca'])->values() : $orders;
    }

    /** Resuelve, en lote, la marca de cada pedido (según los productos de la venta) y su grupo de estado. */
    private function attachComputedFields(Collection $orders): void
    {
        $variantIds = collect();
        foreach ($orders as $order) {
            foreach ((array) $order->productos_json as $line) {
                $variantIds->push($line['variant_id'] ?? null);
                foreach ((array) ($line['selecciones'] ?? []) as $seleccion) {
                    $variantIds->push($seleccion['variant_id'] ?? null);
                }
            }
        }
        $marcaById = Producto::query()->whereIn('id', $variantIds->filter()->unique()->values())->pluck('marca', 'id');

        foreach ($orders as $order) {
            $marcas = collect();
            foreach ((array) $order->productos_json as $line) {
                $marcas->push(trim((string) ($marcaById[$line['variant_id'] ?? null] ?? '')));
                foreach ((array) ($line['selecciones'] ?? []) as $seleccion) {
                    $marcas->push(trim((string) ($marcaById[$seleccion['variant_id'] ?? null] ?? '')));
                }
            }
            $distinct = $marcas->filter()->unique()->values();

            $order->marca_computed = match (true) {
                $distinct->count() === 1 => $distinct->first(),
                $distinct->count() > 1 => 'Multimarca',
                default => 'Overshark',
            };
            $order->status_group = $this->statusGroupFor($order->statusOrder?->name);
        }
    }

    private function statusGroupFor(?string $statusName): ?string
    {
        foreach (self::STATUS_GROUPS as $key => $group) {
            if (in_array($statusName, $group['statuses'], true)) {
                return $key;
            }
        }

        return null;
    }

    private function metodosPagoDisponibles(): Collection
    {
        return Order::query()
            ->whereNotNull('metodo_pago_nombre')
            ->where('metodo_pago_nombre', '<>', '')
            ->distinct()
            ->orderBy('metodo_pago_nombre')
            ->pluck('metodo_pago_nombre');
    }

    private function marcaOptions(): Collection
    {
        return collect(['Overshark'])
            ->merge(Brand::query()->active()->orderBy('sort_order')->pluck('name'))
            ->push('Multimarca')
            ->unique()
            ->values();
    }
}
