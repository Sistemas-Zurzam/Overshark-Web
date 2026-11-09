@extends('layouts.admin')

@section('title', 'Pedidos')

@php
    $queryWithout = fn (string ...$keys) => request()->except($keys);
@endphp

@section('content')
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-4 border-b-2 border-admin-text pb-4.5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-1.5">
                <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Ventas / Pedidos</p>
                <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">Pedidos</h1>
                <p class="max-w-160 text-sm text-admin-text-secondary">Pedidos de las tres marcas. Confirma pagos, prepara envíos y actualiza el estado.</p>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <a href="{{ route('admin.pedidos.export', request()->query()) }}" class="flex items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">
                    <img src="{{ asset('images/admin/icon-download.svg') }}" class="size-4" alt="">
                    Exportar
                </a>
                <a href="{{ route('admin.pedidos.index') }}" class="flex items-center gap-2 bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">
                    <img src="{{ asset('images/admin/icon-plus-white.svg') }}" class="size-4" alt="">
                    Nuevo pedido
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-admin-success bg-admin-success-soft px-4 py-3 text-sm font-semibold text-admin-success">{{ session('status') }}</div>
        @endif

        <div class="flex flex-col rounded-sm border border-admin-border bg-admin-surface">
            <div class="flex items-center gap-6 overflow-x-auto border-b border-admin-border px-5">
                @foreach ([
                    ['key' => '', 'label' => 'Todos', 'count' => $counts['all'], 'pill' => 'bg-admin-hover text-admin-text-secondary'],
                ] + collect($statusGroups)->filter(fn ($g) => $g['tab'] ?? true)->mapWithKeys(fn ($g, $key) => [$key => ['key' => $key, 'label' => $g['label'], 'count' => $counts[$key], 'pill' => match ($g['tone']) {
                    'warning' => 'bg-admin-warning-soft text-admin-warning',
                    'success' => 'bg-admin-success-soft text-admin-success',
                    'info' => 'bg-admin-accent-soft text-admin-accent',
                    default => 'bg-admin-hover text-admin-text-secondary',
                }]])->all() as $t)
                    <a href="{{ route('admin.pedidos.index', $queryWithout('tab', 'page') + ($t['key'] === '' ? [] : ['tab' => $t['key']])) }}"
                       class="flex shrink-0 items-center gap-2 border-b-2 py-3.5 {{ $tab === $t['key'] ? 'border-admin-accent' : 'border-transparent' }}">
                        <span class="text-sm {{ $tab === $t['key'] ? 'font-bold text-admin-text' : 'font-medium text-admin-text-secondary' }}">{{ $t['label'] }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $t['pill'] }}">{{ $t['count'] }}</span>
                    </a>
                @endforeach
            </div>

            <form action="{{ route('admin.pedidos.index') }}" method="GET" class="flex flex-col gap-3 px-5 py-4 lg:flex-row lg:items-center">
                @if ($tab !== '')
                    <input type="hidden" name="tab" value="{{ $tab }}">
                @endif
                <div class="flex h-10 flex-1 items-center gap-2 rounded-sm border border-admin-border-strong bg-admin-surface px-3">
                    <button type="submit" class="shrink-0" aria-label="Buscar">
                        <img src="{{ asset('images/admin/icon-search.svg') }}" class="size-4" alt="">
                    </button>
                    <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Buscar por N° de pedido, cliente o DNI" autocomplete="off" class="w-full text-sm font-medium text-admin-text outline-none placeholder:text-admin-text-muted">
                </div>
                <select name="pago" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-45">
                    <option value="">Pago: todos</option>
                    @foreach ($metodosPago as $metodo)
                        <option value="{{ $metodo }}" @selected($filters['pago'] === $metodo)>{{ $metodo }}</option>
                    @endforeach
                </select>
                <select name="marca" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-45">
                    <option value="">Marca: todas</option>
                    @foreach ($marcaOptions as $marca)
                        <option value="{{ $marca }}" @selected($filters['marca'] === $marca)>{{ $marca }}</option>
                    @endforeach
                </select>
                <select name="periodo" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-45">
                    <option value="">Período: todos</option>
                    <option value="7" @selected($filters['periodo'] === '7')>Últimos 7 días</option>
                    <option value="30" @selected($filters['periodo'] === '30')>Últimos 30 días</option>
                    <option value="mes" @selected($filters['periodo'] === 'mes')>Este mes</option>
                </select>
            </form>

            <div class="overflow-x-auto">
                <div class="flex min-w-270 items-center gap-0 border-y border-admin-border bg-admin-subtle px-4 py-2.5">
                    <div class="w-27.5 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Pedido</div>
                    <div class="w-46 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Cliente</div>
                    <div class="w-35 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Marca</div>
                    <div class="w-32.5 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Medio de pago</div>
                    <div class="w-25 text-right text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Total</div>
                    <div class="w-45 pl-6 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Estado</div>
                    <div class="w-27.5 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Fecha</div>
                    <div class="w-31.5"></div>
                </div>

                @forelse ($orders as $order)
                    @php($group = $statusGroups[$order->status_group] ?? null)
                    <div class="flex min-w-270 items-center gap-0 border-b border-admin-border px-4 py-3.5">
                        <div class="w-27.5 text-sm font-bold text-admin-text">
                            {{ $order->displayNumber() }}
                            @if ($order->nota_venta)
                                <span class="block text-xs font-medium text-admin-text-muted">{{ $order->number_zazu }}</span>
                            @endif
                        </div>
                        <div class="w-46 pr-2">
                            <p class="truncate text-sm font-bold text-admin-text">{{ $order->cliente_name }}</p>
                            <p class="truncate text-xs text-admin-text-muted">{{ $order->maskedEmail() }}</p>
                        </div>
                        <div class="w-35 text-sm text-admin-text">{{ $order->marca_computed }}</div>
                        <div class="w-32.5 text-sm text-admin-text">{{ $order->metodo_pago_nombre ?: '—' }}</div>
                        <div class="w-25 text-right text-sm font-bold text-admin-text">S/ {{ number_format((float) $order->precio_total, 2) }}</div>
                        <div class="w-45 pl-6"><x-admin.badge :tone="$group['tone'] ?? 'neutral'" :label="$group['label'] ?? $order->statusOrder?->name" /></div>
                        <div class="w-27.5 text-[13px] text-admin-text-secondary">{{ $order->created_at?->format('d/m/Y') }}</div>
                        <div class="flex w-31.5 justify-end">
                            <a href="{{ route('admin.pedidos.show', $order) }}" class="px-4 py-2.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-accent">Ver</a>
                        </div>
                    </div>
                @empty
                    <div class="flex min-w-270 flex-col items-center gap-2.5 px-4 py-14 text-center">
                        <span class="grid size-12 place-items-center rounded-full bg-admin-subtle">
                            <img src="{{ asset('images/admin/icon-bag-filled.svg') }}" class="size-5.5" alt="">
                        </span>
                        <p class="text-[15px] font-bold text-admin-text">Aún no hay pedidos</p>
                        <p class="max-w-75 text-[13px] text-admin-text-secondary">Cuando un cliente compre, verás aquí el pedido, su estado y el total.</p>
                    </div>
                @endforelse
            </div>

            <div class="flex flex-col items-center justify-between gap-3 px-5 py-3.5 sm:flex-row">
                <p class="text-[13px] text-admin-text-secondary">
                    @if ($orders->total() > 0)
                        Mostrando {{ $orders->firstItem() }}–{{ $orders->lastItem() }} de {{ $orders->total() }} pedidos
                    @else
                        Sin resultados
                    @endif
                </p>
                @if ($orders->lastPage() > 1)
                    <div class="flex items-center gap-2">
                        @if ($orders->onFirstPage())
                            <span class="flex items-center gap-2 border border-admin-border px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text-muted">
                                <img src="{{ asset('images/admin/icon-arrow-left.svg') }}" class="size-4 opacity-40" alt="">
                                Anterior
                            </span>
                        @else
                            <a href="{{ $orders->previousPageUrl() }}" class="flex items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">
                                <img src="{{ asset('images/admin/icon-arrow-left.svg') }}" class="size-4" alt="">
                                Anterior
                            </a>
                        @endif
                        @if ($orders->hasMorePages())
                            <a href="{{ $orders->nextPageUrl() }}" class="flex items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">
                                Siguiente
                                <img src="{{ asset('images/admin/icon-chevron-right.svg') }}" class="size-4" alt="">
                            </a>
                        @else
                            <span class="flex items-center gap-2 border border-admin-border px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text-muted">
                                Siguiente
                                <img src="{{ asset('images/admin/icon-chevron-right.svg') }}" class="size-4 opacity-40" alt="">
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
