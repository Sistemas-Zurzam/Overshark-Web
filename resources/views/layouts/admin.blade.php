<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Administración') | Overshark</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <header class="fixed inset-x-0 top-0 z-40 h-16 border-b border-slate-200 bg-white">
        <div class="flex h-full items-center justify-between px-4 lg:px-6">
            <div class="flex items-center gap-3">
                <button type="button" data-sidebar-toggle class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden" aria-label="Abrir menú">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 font-black tracking-[0.2em] text-slate-950">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-cyan-500 text-sm text-slate-950">OS</span>
                    <span>OVERSHARK</span>
                </a>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('web.home') }}" class="hidden text-sm font-semibold text-slate-500 hover:text-cyan-600 sm:block">Ver tienda</a>
                <div class="grid h-9 w-9 place-items-center rounded-full bg-slate-900 text-xs font-bold text-white">AD</div>
            </div>
        </div>
    </header>

    <div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-slate-950/50 md:hidden"></div>
    <aside data-sidebar class="fixed bottom-0 left-0 top-16 z-50 w-72 -translate-x-full overflow-y-auto border-r border-slate-200 bg-white p-4 transition-transform md:translate-x-0">
        <p class="mb-2 px-3 text-xs font-bold uppercase tracking-widest text-slate-400">Principal</p>
        <nav class="space-y-1">
            <a href="{{ route('admin.dashboard') }}" class="admin-nav-link bg-cyan-50 text-cyan-700">
                <span>▦</span><span>Dashboard</span>
            </a>
        </nav>

        @foreach ([
            'Ventas' => [['Pedidos', 'orders'], ['Clientes', 'clientes'], ['Estados de pedido', 'status-orders']],
            'Catálogo' => [['Productos', 'productos'], ['Categorías', 'categorias'], ['Variantes', 'variantes'], ['Combos', 'combos']],
            'Configuración' => [['Métodos de pago', 'metodos-pago'], ['Tipos de envío', 'tipos-envio'], ['Banners', 'banners'], ['Redes sociales', 'redes-sociales']],
            'Ubicación y acceso' => [['Departamentos', 'departamentos'], ['Provincias', 'provincias'], ['Distritos', 'distritos'], ['Usuarios y roles', 'usuarios']],
        ] as $section => $items)
            <p class="mb-2 mt-7 px-3 text-xs font-bold uppercase tracking-widest text-slate-400">{{ $section }}</p>
            <nav class="space-y-1">
                @foreach ($items as [$label, $anchor])
                    <a href="{{ route('admin.dashboard') }}#{{ $anchor }}" class="admin-nav-link">
                        <span class="text-cyan-500">•</span><span>{{ $label }}</span>
                    </a>
                @endforeach
            </nav>
        @endforeach
    </aside>

    <main class="pt-16 md:pl-72">
        <div class="p-5 lg:p-8">@yield('content')</div>
    </main>
</body>
</html>
