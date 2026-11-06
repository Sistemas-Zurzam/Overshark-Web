@php($brand = \App\Models\BrandSetting::current())
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Administracion') | Overshark</title>
    @if ($brand->iconUrl())
        <link rel="icon" href="{{ $brand->iconUrl() }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell min-h-screen bg-[#f1f2f4] text-slate-900 antialiased">
    <header class="admin-topbar fixed inset-x-0 top-0 z-40 h-[68px] bg-white">
        <div class="flex h-full items-center justify-between px-4 lg:px-7">
            <div class="flex items-center gap-3">
                <button type="button" data-sidebar-toggle class="rounded-xl p-2 text-slate-600 transition hover:bg-slate-100 md:hidden" aria-label="Abrir menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 font-black tracking-[0.2em] text-slate-950">
                    @if ($brand->logoUrl())
                        <img src="{{ $brand->logoUrl() }}" alt="Overshark" class="h-10 max-w-48 object-contain object-left">
                    @else
                        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-[#030a1c] text-sm text-white ring-2 ring-[#2675ff]/40">OS</span>
                        <span class="text-[15px] tracking-[0.16em]">OVERSHARK</span>
                    @endif
                </a>
            </div>
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="{{ route('web.home') }}" class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-[#e8f2fc] hover:text-[#0078d7]">Ver tienda</a>
                <div class="hidden items-center gap-3 border-l border-slate-200 pl-4 sm:flex">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-[#0078d7] text-sm font-black text-white">{{ mb_strtoupper(mb_substr(request()->user()->name, 0, 1)) }}</span>
                    <div class="hidden leading-tight md:block">
                        <span class="block text-sm font-bold text-slate-800">{{ request()->user()->name }}</span>
                        <span class="mt-1 block text-[11px] font-medium text-slate-400">Administrador</span>
                    </div>
                </div>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-bold text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600">Salir</button>
                </form>
            </div>
        </div>
    </header>

    <div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-slate-950/60 backdrop-blur-sm md:hidden"></div>
    <aside data-sidebar class="admin-sidebar fixed bottom-0 left-0 top-[68px] z-50 w-72 -translate-x-full overflow-y-auto px-3 py-5 transition-transform md:translate-x-0">
        <p class="mb-3 px-3 text-[10px] font-extrabold uppercase tracking-[0.2em] text-[#5e9bea]">Menú principal</p>
        <nav class="space-y-1">
            @foreach ([
                ['admin.dashboard', 'Dashboard'],
                ['admin.pedidos.index', 'Pedidos'],
                ['admin.clientes.index', 'Clientes'],
                ['admin.reclamos.index', 'Reclamos'],
                ['admin.messages.index', 'Mensajes'],
                ['admin.usuarios.index', 'Usuarios'],
                ['admin.bodegas.index', 'Bodegas'],
                ['admin.productos.index', 'Productos'],
                ['admin.medios-pago.index', 'Medios de Pago'],
                ['admin.banners.index', 'Banners'],
                ['admin.combos.index', 'Combos'],
                ['admin.sugerencias.index', 'Sugerencias'],
                ['admin.outfits.index', 'Arma tu outfit'],
                ['admin.about.edit', 'Página Nosotros'],
                ['admin.tv.index', 'Overshark TV'],
                ['admin.brands.index', 'Marcas'],
                ['admin.brand.edit', 'Logo e Icono'],
            ] as [$routeName, $label])
                <a href="{{ route($routeName) }}" @class([
                    'admin-nav-link',
                    'is-active' => request()->routeIs($routeName),
                ]) @if (request()->routeIs($routeName)) aria-current="page" @endif>
                    <span class="admin-nav-icon" aria-hidden="true"></span>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>
        <div class="mx-3 my-5 border-t border-[#1b3366]"></div>
        <div class="rounded-2xl border border-[#1b3366] bg-[#0a1838]/70 p-4">
            <p class="text-xs font-bold text-white">Overshark Admin</p>
            <p class="mt-1 text-[11px] leading-5 text-[#8299bf]">Gestiona tu tienda y pedidos desde un solo lugar.</p>
        </div>
    </aside>

    <main class="min-h-screen pt-[68px] md:pl-72">
        <div class="mx-auto max-w-[1680px] p-4 sm:p-6 lg:p-9">@yield('content')</div>
    </main>
</body>
</html>
