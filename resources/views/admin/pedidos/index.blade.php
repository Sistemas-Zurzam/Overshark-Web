@extends('layouts.admin')

@section('title', 'Pedidos')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Ventas</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950">Pedidos</h1>
        <p class="mt-2 text-slate-500">Pedidos de la tienda web. Revisa la captura del pago y confirma cada pedido.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        <form action="{{ route('admin.pedidos.index') }}" method="GET" class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_auto] lg:items-end">
            <label class="block">
                <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Buscar</span>
                <input type="search" name="search" value="{{ request('search') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="Número, cliente, correo o celular">
            </label>
            <label class="block">
                <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Estado</span>
                <select name="estado" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100">
                    <option value="">Todos</option>
                    @foreach ($statuses as $statusName)
                        <option value="{{ $statusName }}" @selected(request('estado') === $statusName)>{{ $statusName }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white hover:bg-cyan-600">Filtrar</button>
        </form>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs font-black uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="py-3 pr-4">Número</th>
                        <th class="py-3 pr-4">Cliente</th>
                        <th class="py-3 pr-4">Estado</th>
                        <th class="py-3 pr-4">Medio de pago</th>
                        <th class="py-3 pr-4 text-right">Total</th>
                        <th class="py-3 pr-4">Fecha</th>
                        <th class="py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="py-3 pr-4 font-bold text-slate-950">{{ $order->displayNumber() }}@if ($order->nota_venta)<span class="block text-xs font-medium text-slate-400">{{ $order->number_zazu }}</span>@endif</td>
                            <td class="py-3 pr-4">{{ $order->cliente_name }}<span class="block text-xs text-slate-500">{{ $order->cliente_cel }}</span></td>
                            <td class="py-3 pr-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-bold', 'bg-amber-50 text-amber-700' => $order->statusOrder?->name === \App\Support\Checkout::STATUS_PENDING, 'bg-emerald-50 text-emerald-700' => $order->statusOrder?->name !== \App\Support\Checkout::STATUS_PENDING])>{{ $order->statusOrder?->name }}</span></td>
                            <td class="py-3 pr-4">{{ $order->metodo_pago_nombre ?? '—' }}</td>
                            <td class="py-3 pr-4 text-right font-bold">S/ {{ number_format((float) $order->precio_total, 2) }}</td>
                            <td class="py-3 pr-4 text-slate-500">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="py-3 text-right"><a href="{{ route('admin.pedidos.show', $order) }}" class="font-bold text-cyan-700 hover:underline">Ver</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-slate-500">Todavía no hay pedidos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $orders->links() }}</div>
    </section>
@endsection
