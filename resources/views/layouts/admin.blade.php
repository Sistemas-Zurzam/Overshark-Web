@php($brand = \App\Models\BrandSetting::current())
@php($userName = trim((string) request()->user()->name))
@php($nameParts = preg_split('/\s+/', $userName))
@php($initials = mb_strtoupper(count($nameParts) > 1 ? mb_substr($nameParts[0], 0, 1).mb_substr($nameParts[1], 0, 1) : mb_substr($nameParts[0] ?? '', 0, 2)))
@php($navGroups = [
    null => [
        ['admin.dashboard', 'Dashboard', 'dashboard'],
    ],
    'VENTAS' => [
        ['admin.pedidos.index', 'Pedidos', 'bag'],
        ['admin.clientes.index', 'Clientes', 'users'],
        ['admin.reclamos.index', 'Reclamos', 'book'],
        ['admin.messages.index', 'Mensajes', 'mail'],
    ],
    'CATÁLOGO' => [
        ['admin.productos.index', 'Productos', 'package'],
        ['admin.combos.index', 'Combos', 'gift'],
        ['admin.promociones.index', 'Promociones', 'percent'],
        ['admin.sugerencias.index', 'Sugerencias', 'lightbulb'],
        ['admin.outfits.index', 'Arma tu outfit', 'shirt'],
    ],
    'CONTENIDO' => [
        ['admin.banners.index', 'Banners', 'image'],
        ['admin.contenido.edit', 'Contenido de la tienda', 'file'],
        ['admin.about.edit', 'Página Nosotros', 'info'],
    ],
    'CONFIGURACIÓN' => [
        ['admin.brands.index', 'Marcas', 'tag'],
        ['admin.medios-pago.index', 'Medios de pago', 'card'],
        ['admin.agencias.index', 'Agencias Shalom', 'truck'],
        ['admin.usuarios.index', 'Usuarios y roles', 'user'],
    ],
])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administracion') | Overshark</title>
    @if ($brand->iconUrl())
        <link rel="icon" href="{{ $brand->iconUrl() }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@900&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell min-h-screen bg-admin-canvas font-admin-sans text-admin-text antialiased">
    <header class="admin-topbar fixed inset-x-0 top-0 z-40 h-16">
        <div class="flex h-full items-center justify-between px-4 lg:px-6">
            <div class="flex items-center gap-3">
                <button type="button" data-sidebar-toggle class="rounded-lg p-2 text-white transition hover:bg-white/10 md:hidden" aria-label="Abrir menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/admin/logo-overshark-blanco.svg') }}" alt="Overshark" class="h-7 w-auto">
                </a>
                <span class="rounded-sm bg-admin-accent px-2 py-0.5 text-[11px] font-extrabold uppercase tracking-[1.5px] text-white">Admin</span>
            </div>
            <div class="flex items-center gap-5">
                <a href="{{ route('web.home') }}" class="hidden items-center gap-1.5 text-xs font-extrabold uppercase tracking-[1px] text-white transition hover:text-admin-accent-soft sm:flex">
                    Ver tienda
                    <svg class="size-4" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M10 2H14V6" stroke="currentColor" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6.66667 9.33333L14 2" stroke="currentColor" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 8.66667V12.6667C12 13.0203 11.8595 13.3594 11.6095 13.6095C11.3594 13.8595 11.0203 14 10.6667 14H3.33333C2.97971 14 2.64057 13.8595 2.39052 13.6095C2.14048 13.3594 2 13.0203 2 12.6667V5.33333C2 4.97971 2.14048 4.64057 2.39052 4.39052C2.64057 4.14048 2.97971 4 3.33333 4H7.33333" stroke="currentColor" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <div class="hidden h-6 w-px bg-white/30 sm:block"></div>
                <div class="hidden items-center gap-2.5 sm:flex">
                    <span class="grid size-8 place-items-center rounded-sm bg-admin-accent text-xs font-extrabold text-white">{{ $initials }}</span>
                    <div class="hidden leading-tight md:block">
                        <span class="block text-sm font-bold text-white">{{ $userName }}</span>
                        <span class="mt-0.5 block text-xs text-[#9fb4cb]">{{ request()->user()->email }}</span>
                    </div>
                </div>
                <form action="{{ route('admin.logout') }}" method="POST" class="flex items-center">
                    @csrf
                    <button type="submit" class="rounded-lg p-1 text-white/80 transition hover:text-white" aria-label="Cerrar sesión" title="Cerrar sesión">
                        <img src="{{ asset('images/admin/icon-logout.svg') }}" alt="" class="size-5">
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-slate-950/60 backdrop-blur-sm md:hidden"></div>
    <aside data-sidebar class="admin-sidebar fixed bottom-0 left-0 top-16 z-50 w-66 -translate-x-full overflow-y-auto px-4 py-5 transition-transform md:translate-x-0">
        @php($navBadges = \App\Support\AdminNav::badges())
        <nav class="flex flex-col gap-3" aria-label="Menú del panel">
            @foreach ($navGroups as $groupLabel => $items)
                @php($groupActive = collect($items)->contains(fn ($item) => \App\Support\AdminNav::isActive($item[0])))
                @if ($groupLabel)
                    {{-- Cada grupo se pliega (se recuerda en el navegador); el que contiene la sección actual siempre queda abierto. --}}
                    <details class="admin-nav-group" data-nav-group="{{ $groupLabel }}" @if ($groupActive) data-nav-active @endif open>
                        <summary class="admin-nav-group-label">
                            <span class="flex-1">{{ $groupLabel }}</span>
                            {{-- Con el grupo cerrado, lo pendiente de adentro se sigue viendo. --}}
                            @php($groupPending = collect($items)->sum(fn ($item) => ($navBadges[$item[0]]['tone'] ?? '') === 'success' ? 0 : ($navBadges[$item[0]]['count'] ?? 0)))
                            @if ($groupPending > 0)
                                <span class="admin-nav-group-dot" aria-hidden="true"></span>
                            @endif
                            <svg class="admin-nav-chevron size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m4 6 4 4 4-4"/></svg>
                        </summary>
                        <div class="flex flex-col gap-0.5">
                            @foreach ($items as [$routeName, $label, $icon])
                                @include('layouts.partials.admin-nav-link')
                            @endforeach
                        </div>
                    </details>
                @else
                    <div class="flex flex-col gap-0.5">
                        @foreach ($items as [$routeName, $label, $icon])
                            @include('layouts.partials.admin-nav-link')
                        @endforeach
                    </div>
                @endif
            @endforeach
        </nav>
    </aside>

    <main class="min-h-screen pt-16 md:pl-66">
        <div class="mx-auto max-w-420 p-4 sm:p-6 lg:p-9">@yield('content')</div>
    </main>
</body>
</html>
