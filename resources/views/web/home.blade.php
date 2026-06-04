@extends('layouts.web')

@section('title', 'Overshark | Tienda')

@section('content')
    @if ($banner)
        <section class="w-full overflow-hidden bg-[#edf4fb]">
            <div class="w-full">
                <img
                    src="{{ $banner->imageUrl() }}"
                    alt="{{ $banner->name }}"
                    class="block h-auto w-full"
                >
            </div>
        </section>
    @else
        <section class="bg-[#303030] text-slate-950 sm:px-4">
            <div class="relative mx-auto min-h-[610px] max-w-[1440px] overflow-hidden bg-[#edf4fb]">
                <img
                    src="{{ asset('images/default-hero-banner.png') }}"
                    alt="Modelos Overshark usando polos de la promoción"
                    class="absolute inset-0 h-full w-full object-cover object-[70%_center] max-lg:opacity-30"
                >
                <div class="absolute inset-0 bg-gradient-to-r from-[#f4f8fc] via-[#f4f8fc]/95 via-45% to-transparent max-lg:bg-[#f4f8fc]/70"></div>

                <div class="relative flex min-h-[610px] max-w-3xl flex-col justify-center px-6 py-12 sm:px-10 lg:px-20">
                <div class="mb-6 flex w-fit items-center gap-2 rounded-xl bg-white/95 px-4 py-2 text-xs font-black uppercase tracking-wide shadow-sm sm:text-sm">
                    <svg class="h-5 w-5 text-orange-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M13.5 1.8c.7 4.4-2.2 5.8-2.2 8.7 0 1.2.7 2.1 1.7 2.1 1.6 0 2.5-1.5 2.1-3.5 2.6 2 4 4.4 4 7.1a7.1 7.1 0 0 1-14.2 0c0-3.8 2-7.3 5.8-10.5-.2 2.6.5 4.1 1.5 4.1 1.4 0 2.3-2.7 1.3-8Z"/>
                    </svg>
                    Promoción por tiempo limitado
                </div>

                <h1 class="max-w-xl text-5xl font-black uppercase leading-[0.92] tracking-tight sm:text-7xl lg:text-[5.25rem]">
                    5 polos
                    <span class="mt-2 block text-cyan-600">por S/100</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-slate-700 sm:text-xl">
                    Arma tu combo con colores y tallas a elección.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:gap-5">
                    <a href="#productos" class="rounded-lg bg-slate-950 px-8 py-4 text-center text-sm font-black uppercase text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-cyan-700">
                        Comprar ahora
                    </a>
                    <a href="#categorias" class="rounded-lg border-2 border-slate-950 bg-white/40 px-8 py-4 text-center text-sm font-black uppercase transition hover:-translate-y-0.5 hover:bg-white">
                        Ver catálogo
                    </a>
                </div>

                <div class="mt-9 flex max-w-xl flex-wrap gap-x-6 gap-y-4 border-t border-slate-400/50 pt-6 text-xs font-semibold text-slate-700 sm:text-sm">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-full bg-white shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 6h11v11H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                        </span>
                        Envíos a todo el Perú
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-full bg-white shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 2.1 2.1 3-.4.4 3L20 10l-1.5 2.6.8 2.9-2.8 1.1-1.1 2.8-2.9-.8L10 21l-2.1-2.1-3 .4-.4-3L2 14l1.5-2.6L2.7 8.5l2.8-1.1 1.1-2.8 2.9.8Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>
                        </span>
                        Calidad garantizada
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-full bg-white shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3 4 6v5c0 5 3.4 8.4 8 10 4.6-1.6 8-5 8-10V6Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>
                        </span>
                        Pagos seguros
                    </div>
                </div>

                <div class="mt-7 flex gap-2" aria-label="Banner 1 de 4">
                    <span class="h-2.5 w-2.5 rounded-full bg-slate-950"></span>
                    <span class="h-2.5 w-2.5 rounded-full border border-slate-600"></span>
                    <span class="h-2.5 w-2.5 rounded-full border border-slate-600"></span>
                    <span class="h-2.5 w-2.5 rounded-full border border-slate-600"></span>
                </div>
                </div>
            </div>
        </section>
    @endif

    <section id="combos" class="bg-white px-5 py-16 text-slate-950 sm:py-20 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-9 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-cyan-600">Promociones Overshark</p>
                    <h2 class="mt-2 text-3xl font-black uppercase sm:text-4xl">Combos más pedidos</h2>
                    <p class="mt-2 text-slate-500">Encuentra promociones listas para elegir y comprar.</p>
                </div>
                <button type="button" data-combos-toggle class="hidden rounded-xl border border-slate-300 px-5 py-3 text-sm font-black uppercase transition hover:border-cyan-500 hover:text-cyan-700 sm:block">
                    Ver todos los combos
                </button>
            </div>

            @if ($combos->isEmpty())
                <div class="grid min-h-64 place-items-center rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-5 text-center">
                    <div>
                        <p class="text-lg font-black text-slate-700">Próximamente nuevos combos</p>
                        <p class="mt-1 text-sm text-slate-400">Las promociones activas aparecerán en esta sección.</p>
                    </div>
                </div>
            @else
                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach ($combos as $combo)
                        <article class="group relative min-h-[390px] overflow-hidden rounded-3xl border border-slate-200 bg-[#f3f7fb] shadow-lg shadow-slate-200/60">
                            <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-r from-white via-white/90 via-42% to-transparent"></div>
                            <div class="relative flex min-h-[390px] max-w-sm flex-col justify-center p-7 sm:p-10">
                                <span class="flex w-fit items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-black uppercase tracking-wide shadow-sm">
                                    <svg class="h-5 w-5 text-amber-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 2.5 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.1l6.2-.9Z"/></svg>
                                    Combo destacado
                                </span>
                                <h3 class="mt-6 text-4xl font-black uppercase leading-none sm:text-5xl">{{ $combo->name }}</h3>
                                <p class="mt-4 max-w-xs text-sm leading-6 text-slate-600">Descubre todos los productos incluidos y arma tu pedido ideal.</p>
                                <a href="{{ $combo->url }}" class="mt-8 w-fit rounded-xl bg-slate-950 px-7 py-3.5 text-sm font-black uppercase text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-cyan-700">
                                    Ver productos
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section id="categorias" class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
        <div class="mb-10 flex items-end justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-widest text-cyan-400">Explora</p>
                <h2 class="mt-2 text-3xl font-black">Categorías destacadas</h2>
            </div>
        </div>
        <div class="grid gap-5 md:grid-cols-3">
            @foreach (['Novedades', 'Más vendidos', 'Ofertas'] as $category)
                <article class="group min-h-56 rounded-3xl border border-white/10 bg-slate-900 p-7 transition hover:-translate-y-1 hover:border-cyan-400/60">
                    <span class="text-sm font-bold text-cyan-400">0{{ $loop->iteration }}</span>
                    <h3 class="mt-24 text-2xl font-black">{{ $category }}</h3>
                </article>
            @endforeach
        </div>
    </section>

    <section id="productos" class="border-y border-white/10 bg-slate-900/50">
        <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-400">Selección Overshark</p>
            <h2 class="mt-2 text-3xl font-black">Productos destacados</h2>
            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (range(1, 4) as $item)
                    <article class="overflow-hidden rounded-3xl border border-white/10 bg-slate-950">
                        <div class="grid aspect-square place-items-center bg-gradient-to-br from-slate-800 to-slate-900 text-4xl font-black text-slate-700">OS</div>
                        <div class="p-5">
                            <p class="text-xs font-bold uppercase tracking-widest text-cyan-400">Categoría</p>
                            <h3 class="mt-2 font-bold">Producto destacado {{ $item }}</h3>
                            <p class="mt-3 text-lg font-black">S/ 0.00</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
