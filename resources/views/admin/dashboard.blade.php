@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-blue-600">Resumen general</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Hola, {{ request()->user()->name }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500">Aquí tienes el estado de tu tienda y la actividad reciente.</p>
        </div>
        <div class="flex items-center gap-3 text-sm text-slate-500">
            <span class="grid h-9 w-9 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm" aria-hidden="true">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17"/></svg>
            </span>
            <span class="font-semibold text-slate-600">{{ now()->translatedFormat('l, j \d\e F Y') }}</span>
        </div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores de la tienda">
        @foreach ([
            ['Pedidos hoy', $ordersToday, 'Nuevos pedidos recibidos', 'bg-blue-50 text-blue-600 ring-blue-100', 'from-blue-500 to-sky-300', '<path d="M3 4h2l2.2 10.1a2 2 0 0 0 2 1.6h7.5a2 2 0 0 0 2-1.6L20 8H6"/><circle cx="10" cy="20" r="1"/><circle cx="17" cy="20" r="1"/>'],
            ['Clientes', $customersCount, 'En tu base de clientes', 'bg-violet-50 text-violet-600 ring-violet-100', 'from-violet-500 to-fuchsia-300', '<circle cx="9" cy="8" r="3.5"/><path d="M2.8 19a6.2 6.2 0 0 1 12.4 0M16 5.3a3.5 3.5 0 0 1 0 6.8M17.2 13.5a5.7 5.7 0 0 1 4 5.5"/>'],
            ['Productos', $productsCount, 'En el catálogo', 'bg-amber-50 text-amber-600 ring-amber-100', 'from-amber-400 to-orange-300', '<path d="m12 3 8.5 4.5v9L12 21l-8.5-4.5v-9L12 3Z"/><path d="m3.8 7.6 8.2 4.5 8.2-4.5M12 12v9M7.8 5.3l8.4 4.5"/>'],
            ['Cobrado este mes', 'S/ '.number_format((float) $collectedThisMonth, 2), 'Pagos confirmados', 'bg-emerald-50 text-emerald-600 ring-emerald-100', 'from-emerald-500 to-teal-300', '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3 9h18M7 14h4"/>'],
        ] as [$label, $value, $hint, $iconSurface, $stripe, $icon])
            <article class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_5px_20px_rgb(15_23_42_/_4%)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_12px_28px_rgb(15_23_42_/_8%)]">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $stripe }} opacity-80"></div>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ $label }}</p>
                        <p class="mt-4 text-3xl font-black tracking-tight text-slate-950">{{ $value }}</p>
                        <p class="mt-2 text-xs font-medium text-slate-400">{{ $hint }}</p>
                    </div>
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl ring-1 ring-inset {{ $iconSurface }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
                    </span>
                </div>
            </article>
        @endforeach
    </section>

    <section class="mt-7 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_5px_20px_rgb(15_23_42_/_4%)]">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-6">
            <div>
                <h2 class="text-lg font-extrabold tracking-tight text-slate-900">Pedidos recientes</h2>
                <p class="mt-1 text-sm text-slate-500">Revisa el estado de las últimas compras.</p>
            </div>
            <a href="{{ route('admin.pedidos.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                Ver todos los pedidos
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-slate-50/80 text-[10px] font-extrabold uppercase tracking-[0.14em] text-slate-400">
                    <tr>
                        <th class="px-6 py-3.5">Pedido</th>
                        <th class="px-4 py-3.5">Cliente</th>
                        <th class="px-4 py-3.5">Estado</th>
                        <th class="px-4 py-3.5">Fecha</th>
                        <th class="px-6 py-3.5 text-right">Total</th>
                        <th class="px-5 py-3.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentOrders as $order)
                        @php($status = (string) ($order->statusOrder?->name ?? 'Sin estado'))
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.pedidos.show', $order) }}" class="font-extrabold text-slate-900 hover:text-blue-700">{{ $order->displayNumber() }}</a>
                                @if ($order->nota_venta)<span class="mt-1 block text-xs font-medium text-slate-400">{{ $order->number_zazu }}</span>@endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-semibold text-slate-800">{{ $order->cliente_name ?: 'Cliente' }}</span>
                                <span class="mt-1 block text-xs text-slate-400">{{ $order->cliente_cel ?: '—' }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold',
                                    'bg-emerald-50 text-emerald-700' => in_array($status, [\App\Support\Checkout::STATUS_PAID, \App\Support\Checkout::STATUS_DEPOSIT_PAID], true),
                                    'bg-amber-50 text-amber-700' => in_array($status, [\App\Support\Checkout::STATUS_PENDING, \App\Support\Checkout::STATUS_AWAITING_QR], true),
                                    'bg-slate-100 text-slate-600' => ! in_array($status, [\App\Support\Checkout::STATUS_PAID, \App\Support\Checkout::STATUS_DEPOSIT_PAID, \App\Support\Checkout::STATUS_PENDING, \App\Support\Checkout::STATUS_AWAITING_QR], true),
                                ])>
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $status }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs font-medium text-slate-500">{{ $order->created_at?->format('d/m/Y · H:i') }}</td>
                            <td class="px-6 py-4 text-right font-extrabold text-slate-900">S/ {{ number_format((float) $order->precio_total, 2) }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.pedidos.show', $order) }}" class="rounded-lg px-2.5 py-1.5 text-xs font-bold text-blue-700 transition hover:bg-blue-50">Abrir</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-400">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 4h2l2.2 10.1a2 2 0 0 0 2 1.6h7.5a2 2 0 0 0 2-1.6L20 8H6"/><circle cx="10" cy="20" r="1"/><circle cx="17" cy="20" r="1"/></svg>
                                </span>
                                <p class="mt-3 font-bold text-slate-700">Aún no hay pedidos</p>
                                <p class="mt-1 text-sm text-slate-400">Cuando recibas una compra, aparecerá aquí.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-8">
        <div class="mb-4 flex items-end justify-between gap-3">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-slate-400">Accesos directos</p>
                <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">Configuración rápida</h2>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['admin.productos.index', 'Productos', 'Administra catálogo, precios e imágenes.', 'P', 'bg-blue-50 text-blue-700 ring-blue-100'],
                ['admin.combos.index', 'Combos', 'Organiza productos en promociones.', 'C', 'bg-violet-50 text-violet-700 ring-violet-100'],
                ['admin.medios-pago.index', 'Medios de pago', 'Configura las opciones de cobro.', '$', 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
                ['admin.brand.edit', 'Identidad visual', 'Actualiza logo, icono y datos de marca.', 'O', 'bg-amber-50 text-amber-700 ring-amber-100'],
            ] as [$route, $title, $description, $mark, $toneSurface])
                <a href="{{ route($route) }}" class="group flex min-h-40 flex-col rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_5px_20px_rgb(15_23_42_/_3%)] transition duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-[0_12px_28px_rgb(15_23_42_/_8%)]">
                    <span class="grid h-10 w-10 place-items-center rounded-xl text-sm font-black ring-1 ring-inset {{ $toneSurface }}">{{ $mark }}</span>
                    <span class="mt-4 font-extrabold text-slate-900">{{ $title }}</span>
                    <span class="mt-1.5 flex-1 text-sm leading-6 text-slate-500">{{ $description }}</span>
                    <span class="mt-4 inline-flex items-center gap-1.5 text-xs font-extrabold text-blue-700 transition group-hover:gap-2.5">Configurar <span aria-hidden="true">→</span></span>
                </a>
            @endforeach
        </div>
    </section>
@endsection
