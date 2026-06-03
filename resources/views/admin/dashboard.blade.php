@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Panel de control</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950">Administración de Overshark</h1>
        <p class="mt-2 text-slate-500">Gestiona ventas, catálogo y configuración general desde un solo lugar.</p>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Pedidos', '0', 'orders'], ['Clientes', '0', 'clientes'], ['Productos', '0', 'productos'], ['Ventas', 'S/ 0.00', 'ventas']] as [$label, $value, $id])
            <article id="{{ $id }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div><p class="text-sm font-semibold text-slate-500">{{ $label }}</p><p class="mt-3 text-3xl font-black">{{ $value }}</p></div>
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-50 font-black text-cyan-600">+</span>
                </div>
            </article>
        @endforeach
    </section>

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="text-xl font-black">Pedidos recientes</h2><p class="text-sm text-slate-500">Seguimiento de las últimas ventas registradas.</p></div>
            <button class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-cyan-600">Nuevo pedido</button>
        </div>
        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[650px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-400">
                    <tr><th class="px-3 py-3">Pedido</th><th class="px-3 py-3">Cliente</th><th class="px-3 py-3">Estado</th><th class="px-3 py-3">Total</th><th class="px-3 py-3">Acción</th></tr>
                </thead>
                <tbody><tr><td colspan="5" class="px-3 py-12 text-center text-slate-400">Todavía no hay pedidos registrados.</td></tr></tbody>
            </table>
        </div>
    </section>

    <section class="mt-8">
        <h2 class="text-xl font-black">Configuración rápida</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['productos', 'Productos', 'Administra precios, stock e imágenes.'],
                ['categorias', 'Categorías', 'Organiza el catálogo público.'],
                ['variantes', 'Variantes', 'Configura tallas, colores y stock.'],
                ['combos', 'Combos', 'Agrupa productos para promociones.'],
                ['metodos-pago', 'Métodos de pago', 'Define opciones de cobro.'],
                ['tipos-envio', 'Tipos de envío', 'Configura modalidades de entrega.'],
                ['banners', 'Banners', 'Controla la portada de la tienda.'],
                ['redes-sociales', 'Redes sociales', 'Actualiza tus canales sociales.'],
                ['departamentos', 'Ubicaciones', 'Gestiona departamentos y provincias.'],
                ['usuarios', 'Usuarios y roles', 'Administra el acceso al panel.'],
            ] as [$id, $title, $description])
                <article id="{{ $id }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-cyan-300">
                    <div class="mb-5 grid h-10 w-10 place-items-center rounded-xl bg-cyan-50 font-black text-cyan-600">+</div>
                    <h3 class="font-black">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $description }}</p>
                    <button class="mt-5 text-sm font-bold text-cyan-600 hover:text-cyan-800">Configurar →</button>
                </article>
            @endforeach
        </div>
    </section>
@endsection
