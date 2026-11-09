@extends('layouts.admin')

@section('title', 'Dashboard')

@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');

    $trendFor = function (float $current, float $previous, string $noDataLabel, string $firstLabel) {
        if ($previous > 0) {
            $change = (int) round((($current - $previous) / $previous) * 100);

            return ['text' => sprintf('%+d%% vs. periodo anterior', $change), 'muted' => false];
        }

        return $current > 0
            ? ['text' => $firstLabel, 'muted' => false]
            : ['text' => $noDataLabel, 'muted' => true];
    };

    $ventasTrend = $trendFor((float) $ventas30, (float) $ventasPrev30, 'Sin ventas en el periodo', 'Primeras ventas del periodo');
    $pedidosTrend = $trendFor((float) $pedidos30, (float) $pedidosPrev30, 'Sin pedidos en el periodo', 'Primeros pedidos del periodo');

    $kpis = [
        ['label' => 'Ventas (30 días)', 'value' => 'S/ '.number_format((float) $ventas30, 2), 'trend' => $ventasTrend['text'], 'muted' => $ventasTrend['muted']],
        ['label' => 'Pedidos', 'value' => (string) $pedidos30, 'trend' => $pedidosTrend['text'], 'muted' => $pedidosTrend['muted']],
        ['label' => 'Clientes registrados', 'value' => (string) $clientesCount, 'trend' => null, 'muted' => false],
        ['label' => 'Productos publicados', 'value' => (string) $productosCount, 'trend' => 'En '.$marcasCount.' '.\Illuminate\Support\Str::plural('marca', $marcasCount), 'muted' => false],
    ];

    $tasks = [];

    if ($reclamosPendientes > 0) {
        $tasks[] = [
            'tone' => 'danger',
            'icon' => 'book-filled',
            'title' => $reclamosPendientes.' '.\Illuminate\Support\Str::plural('reclamo', $reclamosPendientes).' pendiente'.($reclamosPendientes === 1 ? '' : 's').' de respuesta',
            'subtitle' => 'Libro de reclamaciones · plazo legal: 15 días hábiles',
            'cta' => 'Responder',
            'url' => route('admin.reclamos.index'),
        ];
    }

    if ($productosPrecioCero > 0) {
        $tasks[] = [
            'tone' => 'warning',
            'icon' => 'tag-filled',
            'title' => $productosPrecioCero.' '.\Illuminate\Support\Str::plural('producto', $productosPrecioCero).' con precio S/ 0.00',
            'subtitle' => 'Se mostrarían como gratis en la tienda',
            'cta' => 'Revisar precios',
            'url' => route('admin.productos.index'),
        ];
    }

    if ($productosSinMarca > 0) {
        $tasks[] = [
            'tone' => 'warning',
            'icon' => 'package-filled',
            'title' => $productosSinMarca.' '.\Illuminate\Support\Str::plural('producto', $productosSinMarca).' sin marca asignada',
            'subtitle' => 'No aparecen en ninguna tienda de marca',
            'cta' => 'Asignar marca',
            'url' => route('admin.productos.index'),
        ];
    }

    $urgentCount = count($tasks);

    $tasks[] = [
        'tone' => 'neutral',
        'icon' => 'mail-filled',
        'title' => 'Mensajes de contacto',
        'subtitle' => $mensajesPendientes > 0
            ? $mensajesPendientes.' '.\Illuminate\Support\Str::plural('mensaje', $mensajesPendientes).' pendiente'.($mensajesPendientes === 1 ? '' : 's').' de respuesta'
            : 'Todo al día · 0 pendientes',
        'cta' => 'Ver mensajes',
        'url' => route('admin.messages.index'),
    ];

    $taskTone = [
        'danger' => 'bg-admin-danger-soft',
        'warning' => 'bg-admin-warning-soft',
        'neutral' => 'bg-admin-subtle',
    ];
@endphp

@section('content')
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-4 border-b-2 border-admin-text pb-4.5 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-1.5">
                <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Inicio</p>
                <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">{{ $greeting }}, {{ request()->user()->name }}</h1>
                <p class="text-sm text-admin-text-secondary">Resumen de la tienda y tareas pendientes.</p>
            </div>
            <div class="flex h-10 w-50 items-center gap-2 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted">
                <span class="flex-1">Últimos 30 días</span>
                <img src="{{ asset('images/admin/icon-chevron-down.svg') }}" class="size-4" alt="">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($kpis as $kpi)
                <div class="flex flex-col gap-1.5 rounded-sm border border-admin-border bg-admin-surface p-5">
                    <p class="text-[11px] font-extrabold uppercase tracking-[1.5px] text-admin-text-secondary">{{ $kpi['label'] }}</p>
                    <p class="font-admin-display text-[32px] font-black leading-10.5 text-admin-text sm:text-[40px]">{{ $kpi['value'] }}</p>
                    @if ($kpi['trend'])
                        <p class="text-xs font-semibold {{ $kpi['muted'] ? 'text-admin-text-muted' : 'text-admin-success' }}">{{ $kpi['trend'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
            <div class="flex flex-1 flex-col gap-4 rounded-sm border border-admin-border bg-admin-surface p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-admin-display text-2xl font-black uppercase leading-none text-admin-text sm:text-[28px]">Requiere atención</h2>
                    <span class="flex shrink-0 items-center gap-1.5 rounded-sm bg-admin-warning-soft px-2 py-1">
                        <img src="{{ asset('images/admin/dot-warning.svg') }}" class="size-1.5" alt="">
                        <span class="text-[11px] font-extrabold uppercase tracking-[0.5px] text-admin-warning">{{ $urgentCount }} {{ \Illuminate\Support\Str::plural('tarea', $urgentCount) }}</span>
                    </span>
                </div>
                <p class="text-[13px] text-admin-text-secondary">Primero lo que tiene plazo legal o bloquea ventas.</p>

                <div class="flex flex-col gap-2">
                    @foreach ($tasks as $task)
                        <div class="flex items-center gap-3.5 rounded-sm px-3.5 py-3 {{ $taskTone[$task['tone']] }}">
                            <img src="{{ asset('images/admin/icon-'.$task['icon'].'.svg') }}" class="size-5 shrink-0" alt="">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-admin-text">{{ $task['title'] }}</p>
                                <p class="text-xs text-admin-text-secondary">{{ $task['subtitle'] }}</p>
                            </div>
                            @if ($task['tone'] === 'neutral')
                                <a href="{{ $task['url'] }}" class="shrink-0 px-4 py-2.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-accent">{{ $task['cta'] }}</a>
                            @else
                                <a href="{{ $task['url'] }}" class="shrink-0 border border-admin-border-strong bg-admin-surface px-4 py-2.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">{{ $task['cta'] }}</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex w-full flex-col gap-4 rounded-sm border border-admin-border bg-admin-surface p-6 lg:w-105 lg:shrink-0">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-admin-display text-2xl font-black uppercase leading-none text-admin-text sm:text-[28px]">Pedidos recientes</h2>
                    <a href="{{ route('admin.pedidos.index') }}" class="shrink-0 px-4 py-2.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-accent">Ver todos</a>
                </div>

                @if ($recentOrders->isEmpty())
                    <div class="flex flex-col items-center gap-2.5 rounded-sm bg-admin-subtle px-6 py-10 text-center">
                        <span class="grid size-12 place-items-center rounded-full bg-admin-surface">
                            <img src="{{ asset('images/admin/icon-bag-filled.svg') }}" class="size-5.5" alt="">
                        </span>
                        <p class="text-[15px] font-bold text-admin-text">Aún no hay pedidos</p>
                        <p class="max-w-75 text-[13px] text-admin-text-secondary">Cuando un cliente compre, verás aquí el pedido, su estado y el total.</p>
                        <a href="{{ route('admin.pedidos.index') }}" class="flex items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">
                            <img src="{{ asset('images/admin/icon-plus.svg') }}" class="size-4" alt="">
                            Registrar pedido manual
                        </a>
                    </div>
                @else
                    <div class="flex flex-col divide-y divide-admin-border">
                        @foreach ($recentOrders as $order)
                            @php($status = (string) ($order->statusOrder?->name ?? 'Sin estado'))
                            <a href="{{ route('admin.pedidos.show', $order) }}" class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-extrabold text-admin-text">{{ $order->displayNumber() }}</p>
                                    <p class="truncate text-xs text-admin-text-secondary">{{ $order->cliente_name ?: 'Cliente' }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-[11px] font-bold',
                                        'bg-emerald-50 text-emerald-700' => in_array($status, [\App\Support\Checkout::STATUS_PAID, \App\Support\Checkout::STATUS_DEPOSIT_PAID, \App\Support\Checkout::STATUS_COD], true),
                                        'bg-admin-warning-soft text-admin-warning' => in_array($status, [\App\Support\Checkout::STATUS_PENDING, \App\Support\Checkout::STATUS_AWAITING_QR], true),
                                        'bg-admin-subtle text-admin-text-secondary' => ! in_array($status, [\App\Support\Checkout::STATUS_PAID, \App\Support\Checkout::STATUS_DEPOSIT_PAID, \App\Support\Checkout::STATUS_COD, \App\Support\Checkout::STATUS_PENDING, \App\Support\Checkout::STATUS_AWAITING_QR], true),
                                    ])>{{ $status }}</span>
                                    <span class="text-sm font-extrabold text-admin-text">S/ {{ number_format((float) $order->precio_total, 2) }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
