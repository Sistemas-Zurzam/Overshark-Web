@extends('layouts.admin')

@section('title', 'Productos')

@section('content')
    <div class="mb-8 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Catalogo</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Productos</h1>
            <p class="mt-2 text-slate-500">Administra productos, variantes, precios, stock e imagenes.</p>
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
                <h2 class="text-lg font-black text-slate-950">Catalogo de productos</h2>
                <p class="mt-1 text-sm text-slate-500">Productos agrupados por nombre y sus variantes disponibles.</p>
            </div>
            <p class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-500">
                {{ $productos->total() }} registros
            </p>
        </div>

        <form action="{{ route('admin.productos.index') }}" method="GET" class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 lg:p-5" novalidate>
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Producto o SKU</span>
                    <input type="search" name="search" value="{{ request('search') }}" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="Buscar por nombre o SKU" autocomplete="off">
                </label>
                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Empresa</span>
                    <select name="empresa" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100">
                        <option value="">Todas las empresas</option>
                        @foreach ($empresas as $empresa)
                            <option value="{{ $empresa }}" @selected(request('empresa') === $empresa)>{{ $empresa }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Marca</span>
                    <select name="marca" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100">
                        <option value="">Todas las marcas</option>
                        @foreach ($marcas as $marca)
                            <option value="{{ $marca }}" @selected(request('marca') === $marca)>{{ $marca }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600 focus:outline-none focus:ring-4 focus:ring-cyan-100">Filtrar</button>
                    @if (request()->filled('search') || request()->filled('empresa') || request()->filled('marca'))
                        <a href="{{ route('admin.productos.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-600 transition hover:border-slate-950 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-cyan-100">Limpiar</a>
                    @endif
                </div>
            </div>
        </form>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[1080px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-3 py-3">Producto</th>
                        <th class="px-3 py-3">Marca</th>
                        <th class="px-3 py-3">Empresa</th>
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
                                <div class="mt-1 text-xs text-slate-400">{{ $producto->variant_count }} variantes</div>
                            </td>
                            <td class="px-3 py-4 text-sm font-semibold {{ $producto->marca ? 'text-slate-700' : 'text-slate-400' }}">{{ $producto->marca ?: 'Sin asignar' }}</td>
                            <td class="px-3 py-4 text-sm font-semibold text-cyan-700">{{ $producto->empresa_nombre ?? 'Overshark' }}</td>
                            <td class="px-3 py-4 text-right font-bold">{{ $producto->variant_count }}</td>
                            <td class="px-3 py-4 text-right font-bold">
                                S/ {{ number_format((float) $producto->min_price, 2) }}
                                @if ((float) $producto->min_price !== (float) $producto->max_price)
                                    - S/ {{ number_format((float) $producto->max_price, 2) }}
                                @endif
                            </td>
                            <td class="px-3 py-4 text-right">{{ number_format((float) $producto->total_stock, 2) }}</td>
                            <td class="px-3 py-4 text-xs text-slate-500">
                                {{ $producto->updated_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="px-3 py-4 text-right">
                                <a href="{{ route('admin.productos.show', $producto->id) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-cyan-300 hover:text-cyan-700">
                                    Ver variantes
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-16 text-center text-slate-400">
                                No hay productos registrados todavía.
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
