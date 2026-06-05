@extends('layouts.admin')

@section('title', 'Productos')

@section('content')
    <div class="mb-8 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Catalogo</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Productos</h1>
            <p class="mt-2 text-slate-500">Sincroniza productos desde Odoo y revisa codigo, variantes, precios y stock.</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <form action="{{ route('admin.productos.odoo.sync') }}" method="POST">
                @csrf
                <button type="submit" class="w-full rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600 sm:w-auto">
                    Traer datos de Odoo
                </button>
            </form>
            <form action="{{ route('admin.productos.odoo.auto-sync') }}" method="POST">
                @csrf
                <button type="submit" @class([
                    'w-full rounded-xl border px-5 py-3 text-sm font-bold transition sm:w-auto',
                    'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' => $autoSyncEnabled,
                    'border-slate-200 bg-white text-slate-700 hover:border-cyan-300 hover:text-cyan-700' => ! $autoSyncEnabled,
                ])>
                    {{ $autoSyncEnabled ? 'Cron activo cada 1 min' : 'Activar cron cada 1 min' }}
                </button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-950">Productos sincronizados</h2>
                <p class="mt-1 text-sm text-slate-500">Origen: product.product de Odoo, agrupado por producto.</p>
            </div>
            <p class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-500">
                {{ $productos->total() }} registros
            </p>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-3 py-3">Producto</th>
                        <th class="px-3 py-3 text-right">Variantes</th>
                        <th class="px-3 py-3 text-right">Rango precio</th>
                        <th class="px-3 py-3 text-right">Stock</th>
                        <th class="px-3 py-3">Actualizado</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($productos as $producto)
                        <tr class="text-slate-700">
                            <td class="px-3 py-4">
                                <div class="font-bold text-slate-950">{{ $producto->name }}</div>
                                <div class="mt-1 text-xs text-slate-400">Template Odoo: {{ $producto->odoo_template_id ?? '-' }}</div>
                            </td>
                            <td class="px-3 py-4 text-right font-bold">{{ $producto->variant_count }}</td>
                            <td class="px-3 py-4 text-right font-bold">
                                S/ {{ number_format((float) $producto->min_price, 2) }}
                                @if ((float) $producto->min_price !== (float) $producto->max_price)
                                    - S/ {{ number_format((float) $producto->max_price, 2) }}
                                @endif
                            </td>
                            <td class="px-3 py-4 text-right">{{ number_format((float) $producto->total_stock, 2) }}</td>
                            <td class="px-3 py-4 text-xs text-slate-500">
                                {{ $producto->odoo_synced_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="px-3 py-4 text-right">
                                <a href="{{ route('admin.productos.show', $producto->id) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-cyan-300 hover:text-cyan-700">
                                    Ver variantes
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-16 text-center text-slate-400">
                                No hay productos sincronizados. Usa "Traer datos de Odoo".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($productos->hasPages())
            <div class="mt-6">
                {{ $productos->links() }}
            </div>
        @endif
    </section>
@endsection
