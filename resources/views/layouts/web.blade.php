@php
    $brand = \App\Models\BrandSetting::current();
    $menuCombos = \App\Models\Admin\Combo::query()
        ->where('status', true)
        ->whereNotNull('imagen')
        ->latest()
        ->get();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Overshark, tienda online">
    <title>@yield('title', 'Overshark')</title>
    @if ($brand->iconUrl())
        <link rel="icon" href="{{ $brand->iconUrl() }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
    <header class="fixed inset-x-0 top-0 z-50 border-b border-slate-200 bg-white/95 text-slate-950 shadow-sm backdrop-blur">
        <div class="relative mx-auto flex h-[84px] max-w-[1440px] items-center justify-between px-5 lg:px-12">
            <div class="flex items-center gap-8">
                <button type="button" data-combos-toggle class="combo-trigger group hidden items-center gap-2 rounded-full border border-blue-100 bg-white px-5 py-2.5 text-base font-semibold sm:flex" aria-expanded="false" aria-controls="combos-menu">
                    <svg class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M13.5 1.8c.7 4.4-2.2 5.8-2.2 8.7 0 1.2.7 2.1 1.7 2.1 1.6 0 2.5-1.5 2.1-3.5 2.6 2 4 4.4 4 7.1a7.1 7.1 0 0 1-14.2 0c0-3.8 2-7.3 5.8-10.5-.2 2.6.5 4.1 1.5 4.1 1.4 0 2.3-2.7 1.3-8Z"/>
                    </svg>
                    Combos
                    <svg data-combos-chevron class="h-4 w-4 text-slate-400 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                </button>

                <nav class="hidden items-center gap-8 text-base font-medium lg:flex" aria-label="Navegación principal">
                    <a href="#productos" class="flex items-center gap-1.5 transition hover:text-cyan-600">
                        Productos
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                    </a>
                    <a href="#categorias" class="transition hover:text-cyan-600">Categorías</a>
                    <a href="#contacto" class="transition hover:text-cyan-600">Nosotros</a>
                </nav>
            </div>

            <a href="{{ route('web.home') }}" class="absolute left-1/2 flex h-[76px] w-56 -translate-x-1/2 items-center justify-center overflow-hidden text-2xl font-black tracking-[-0.07em] sm:w-72 sm:text-3xl" aria-label="Overshark inicio">
                @if ($brand->logoUrl())
                    <img src="{{ $brand->logoUrl() }}" alt="Overshark" class="h-16 w-full scale-125 object-contain sm:h-20 sm:scale-150">
                @else
                    OVER<span class="text-cyan-600">SHARK</span>
                @endif
            </a>

            <div class="flex items-center gap-1 sm:gap-3">
                <a href="#productos" class="grid h-10 w-10 place-items-center rounded-full transition hover:bg-slate-100" aria-label="Buscar productos">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                </a>
                <a href="{{ route('admin.dashboard') }}" class="hidden h-10 w-10 place-items-center rounded-full transition hover:bg-slate-100 sm:grid" aria-label="Mi cuenta">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="7" r="3.5"/><path d="M5.5 21c.4-5 2.6-7.5 6.5-7.5s6.1 2.5 6.5 7.5"/></svg>
                </a>
                <a href="#productos" class="hidden h-10 w-10 place-items-center rounded-full transition hover:bg-slate-100 sm:grid" aria-label="Bolsa de compras">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 8h14l1 13H4Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                </a>
                <button type="button" data-web-menu-toggle class="grid h-10 w-10 place-items-center rounded-full transition hover:bg-slate-100 lg:hidden" aria-label="Abrir menú" aria-expanded="false">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>

        <div id="combos-menu" data-combos-menu data-state="closed" class="combos-panel absolute left-0 right-0 top-full border-t border-slate-100 bg-white shadow-2xl">
            <div class="mx-auto grid max-w-[1440px] gap-4 px-5 py-6 sm:grid-cols-2 lg:grid-cols-3 lg:px-12">
                @forelse ($menuCombos as $combo)
                    <a href="{{ $combo->url }}" class="combo-card group relative min-h-48 overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                        <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/25 to-transparent"></div>
                        <div class="relative flex h-full min-h-48 items-end p-5">
                            <div>
                                <span class="text-xs font-black uppercase tracking-[0.18em] text-cyan-300">Ver combo</span>
                                <h2 class="mt-1 text-xl font-black text-white">{{ $combo->name }}</h2>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center text-sm font-semibold text-slate-500">
                        Aún no hay combos activos.
                    </div>
                @endforelse
            </div>
        </div>

        <nav data-web-menu class="hidden border-t border-slate-100 bg-white px-5 py-4 text-sm font-semibold lg:hidden" aria-label="Navegación móvil">
            <div class="mx-auto flex max-w-[1440px] flex-col gap-1">
                <button type="button" data-combos-toggle class="flex items-center justify-between rounded-lg px-3 py-3 text-left hover:bg-slate-100" aria-expanded="false" aria-controls="combos-menu">
                    Combos y productos
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                </button>
                <a href="#categorias" class="rounded-lg px-3 py-3 hover:bg-slate-100">Categorías</a>
                <a href="#contacto" class="rounded-lg px-3 py-3 hover:bg-slate-100">Nosotros</a>
                <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-3 hover:bg-slate-100">Mi cuenta</a>
            </div>
        </nav>
    </header>

    <main class="pt-[84px]">@yield('content')</main>

    <footer id="contacto" class="border-t border-white/10 bg-slate-950">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-8 text-sm text-slate-400 md:flex-row md:items-center md:justify-between lg:px-8">
            <p>© {{ date('Y') }} Overshark. Todos los derechos reservados.</p>
            <p>Ventas y atención al cliente</p>
        </div>
    </footer>
</body>
</html>
