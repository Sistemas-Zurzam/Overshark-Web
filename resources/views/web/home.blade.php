@extends('layouts.web')

@section('title', 'Overshark | Tienda')

@section('content')
    <section class="bg-[#F1F2F4]">
        <div id="home-carousel" class="relative w-full" data-carousel="slide">
            <div class="relative overflow-hidden bg-[#F1F2F4]">
                @if ($banners->isNotEmpty())
                    @foreach ($banners as $banner)
                        <div class="{{ $loop->first ? 'block' : 'hidden' }} duration-700 ease-in-out" data-carousel-item>
                            <img
                                src="{{ $banner->imageUrl() }}"
                                class="block h-auto w-full object-contain"
                                alt="{{ $banner->name }}"
                            >
                            @if (! empty($banner->buttons))
                                @foreach ($banner->buttons as $button)
                                    @php
                                        $radiusClass = [
                                            'square' => 'rounded-none',
                                            'rounded' => 'rounded-lg',
                                            'pill' => 'rounded-full',
                                        ][$button['shape'] ?? 'rounded'] ?? 'rounded-lg';
                                        $sizeClass = [
                                            'sm' => 'px-4 py-2 text-xs',
                                            'md' => 'px-7 py-3 text-sm',
                                            'lg' => 'px-9 py-4 text-base',
                                            'xl' => 'px-12 py-5 text-lg',
                                        ][$button['size'] ?? 'md'] ?? 'px-7 py-3 text-sm';
                                    @endphp
                                    <a href="{{ $button['url'] ?? '#productos' }}" class="absolute z-20 -translate-x-1/2 -translate-y-1/2 {{ $radiusClass }} {{ $sizeClass }} font-black uppercase shadow-lg transition hover:-translate-y-[calc(50%+2px)]" style="left: {{ $button['x'] ?? 20 }}%; top: {{ $button['y'] ?? 50 }}%; background-color: {{ $button['bg_color'] ?? '#111111' }}; color: {{ $button['text_color'] ?? '#ffffff' }};">
                                        {{ $button['text'] ?? 'Comprar ahora' }}
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="block duration-700 ease-in-out" data-carousel-item>
                        <img
                            src="{{ asset('images/default-hero-banner.png') }}"
                            class="block h-auto w-full object-contain"
                            alt="Modelos Overshark usando polos de la promocion"
                        >
                    </div>
                @endif
            </div>

            @php
                $slideCount = max($banners->count(), 1);
            @endphp
            <div class="absolute bottom-5 left-1/2 z-30 flex -translate-x-1/2 gap-3">
                @for ($index = 0; $index < $slideCount; $index++)
                    <button
                        type="button"
                        @class(['slider-dot', 'is-active' => $index === 0])
                        aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                        aria-label="Slide {{ $index + 1 }}"
                        data-carousel-slide-to="{{ $index }}"
                    ></button>
                @endfor
            </div>

            @if ($slideCount > 1)
                <button type="button" class="absolute left-0 top-0 z-30 flex h-full cursor-pointer items-center justify-center px-4 focus:outline-none" data-carousel-prev>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white/70 text-slate-950 shadow-sm transition hover:bg-white">
                        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
                        <span class="sr-only">Anterior</span>
                    </span>
                </button>
                <button type="button" class="absolute right-0 top-0 z-30 flex h-full cursor-pointer items-center justify-center px-4 focus:outline-none" data-carousel-next>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-white/70 text-slate-950 shadow-sm transition hover:bg-white">
                        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                        <span class="sr-only">Siguiente</span>
                    </span>
                </button>
            @endif
        </div>
    </section>

    <section id="combos" class="bg-white px-5 py-16 text-slate-950 sm:py-20 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-9 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-cyan-600">Promociones Overshark</p>
                    <h2 class="mt-2 text-3xl font-black uppercase sm:text-4xl">Combos mas pedidos</h2>
                    <p class="mt-2 text-slate-500">Encuentra promociones listas para elegir y comprar.</p>
                </div>
                <button type="button" data-combos-toggle class="btn-secondary hidden px-5 py-3 sm:inline-flex">
                    Ver todos los combos
                </button>
            </div>

            @if ($combos->isEmpty())
                <div class="grid min-h-64 place-items-center rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-5 text-center">
                    <div>
                        <p class="text-lg font-black text-slate-700">Proximamente nuevos combos</p>
                        <p class="mt-1 text-sm text-slate-400">Las promociones activas apareceran en esta seccion.</p>
                    </div>
                </div>
            @else
                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach ($combos as $combo)
                        <article class="group relative min-h-[390px] overflow-hidden rounded-3xl border border-slate-200 bg-[#F1F2F4] shadow-lg shadow-slate-200/60">
                            <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-r from-white via-white/90 via-42% to-transparent"></div>
                            <div class="relative flex min-h-[390px] max-w-sm flex-col justify-center p-7 sm:p-10">
                                <span class="flex w-fit items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-black uppercase tracking-wide shadow-sm">
                                    <svg class="h-5 w-5 text-amber-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 2.5 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.1l6.2-.9Z"/></svg>
                                    Combo destacado
                                </span>
                                <h3 class="mt-6 text-4xl font-black uppercase leading-none sm:text-5xl">{{ $combo->name }}</h3>
                                <p class="mt-4 max-w-xs text-sm leading-6 text-slate-600">Descubre todos los productos incluidos y arma tu pedido ideal.</p>
                                <a href="{{ $combo->url }}" class="btn-primary mt-8 w-fit px-7 py-3.5 shadow-lg">
                                    Ver productos
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section id="categorias" class="bg-white px-5 py-16 text-slate-950 sm:py-20 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-8">
                <h2 class="text-2xl font-black uppercase tracking-tight">Encuentra tu estilo</h2>
                <p class="mt-2 text-sm text-slate-500">Elige el fit, cuello y tela ideal para ti.</p>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                @foreach ([
                    ['tag' => 'Imprescindible', 'title' => 'Polos', 'type' => 'Clasicos', 'copy' => 'El basico de todos los dias.', 'position' => 'object-[32%_center]'],
                    ['tag' => 'Elegante', 'title' => 'Polos', 'type' => 'Camiseros', 'copy' => 'Mas elegante y versatil.', 'position' => 'object-[52%_center]'],
                    ['tag' => 'Moderno', 'title' => 'Polos', 'type' => 'Notch', 'copy' => 'Cuello abierto y moderno.', 'position' => 'object-[72%_center]'],
                ] as $style)
                    <article class="group relative min-h-[370px] overflow-hidden rounded-lg bg-white shadow-xl shadow-slate-200/70 transition hover:-translate-y-1">
                        <img src="{{ asset('images/default-hero-banner.png') }}" alt="{{ $style['title'] }} {{ $style['type'] }}" class="absolute inset-0 h-full w-full object-cover {{ $style['position'] }} transition duration-700 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-r from-white via-white/90 via-45% to-white/10"></div>
                        <div class="relative flex min-h-[370px] max-w-[210px] flex-col justify-center p-8">
                            <span class="w-fit rounded-lg bg-[#f2eee8] px-3 py-1.5 text-[11px] font-black uppercase tracking-wide text-stone-500">{{ $style['tag'] }}</span>
                            <h3 class="mt-9 text-lg font-black uppercase">{{ $style['title'] }}</h3>
                            <p class="mt-1 w-fit border-b border-slate-950 pb-1 text-sm font-medium uppercase tracking-wide text-slate-600">{{ $style['type'] }}</p>
                            <p class="mt-7 text-sm leading-5 text-slate-600">{{ $style['copy'] }}</p>
                            <a href="#productos" class="mt-auto inline-flex w-fit items-center gap-1 text-sm font-bold text-slate-950 transition hover:text-cyan-700">
                                Ver coleccion
                                <span aria-hidden="true">-&gt;</span>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="productos" class="bg-white px-5 pb-20 text-slate-950 sm:pb-24 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-9 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-cyan-600">Seleccion Overshark</p>
                    <h2 class="mt-2 text-3xl font-black uppercase sm:text-4xl">Productos mas vendidos</h2>
                    <p class="mt-2 text-slate-500">Elige el color y mira la foto disponible para ese modelo.</p>
                </div>
                <a href="#productos" class="btn-secondary hidden px-5 py-3 sm:inline-flex">
                    Ver todos los productos
                </a>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @if ($bestSellingProducts->isNotEmpty())
                    @foreach ($bestSellingProducts as $product)
                    @php
                        $swatches = [
                            'azul' => '#1d4f91',
                            'beige' => '#ddcdbd',
                            'perla' => '#e8e0d6',
                            'cemento' => '#9b9b95',
                            'negro' => '#111111',
                            'vino' => '#7b1028',
                            'botella' => '#0f4f3b',
                            'plomo' => '#9a9aa0',
                            'pacay' => '#8c9b73',
                            'denim' => '#526f91',
                            'blanco' => '#f7f7f2',
                            'p. rosa' => '#e8b8bd',
                        ];
                        $displayColors = collect($product->display_colors ?? []);
                        $oldPrice = (float) $product->min_price > 0 ? ((float) $product->min_price / 0.8) : 0;
                    @endphp
                    <article class="group rounded-2xl bg-white p-4 shadow-xl shadow-slate-200/70 transition hover:-translate-y-1">
                        <a href="{{ route('web.products.show', $product->id) }}" class="relative block aspect-[4/5] overflow-hidden rounded-xl bg-[#F7F7F7]">
                            <img data-product-card-image src="{{ $product->display_image }}" alt="{{ $product->name }}" class="h-full w-full object-contain object-center transition duration-500 group-hover:scale-105">
                            <span class="absolute left-0 top-0 rounded-br-lg bg-red-50 px-3 py-1.5 text-base font-medium text-red-600">-20%</span>
                        </a>
                        <div class="p-5">
                            <a href="{{ route('web.products.show', $product->id) }}" class="line-clamp-2 min-h-10 text-base font-bold text-slate-950 transition hover:text-cyan-700">{{ $product->name }}</a>
                            <div class="mt-2 flex items-baseline gap-2">
                                <p class="text-xl font-black">S/ {{ number_format((float) $product->min_price, 2) }}</p>
                                @if ($oldPrice > 0)
                                    <p class="text-sm text-slate-400 line-through">S/ {{ number_format($oldPrice, 2) }}</p>
                                @endif
                            </div>
                            <div class="mt-4 flex flex-wrap items-center gap-1.5">
                                @foreach ($displayColors as $color)
                                    @php
                                        $colorName = mb_strtolower($color['name']);
                                        $swatchColor = $swatches[$colorName] ?? '#b8b8bd';
                                    @endphp
                                    @if ($loop->iteration === 9 && $displayColors->count() > 8)
                                        <button type="button" data-product-colors-expand data-collapsed-label="+{{ $displayColors->count() - 8 }}" data-expanded-label="-" class="grid h-5 min-w-5 place-items-center rounded-full border border-slate-300 bg-white px-1 text-[11px] font-black leading-none text-slate-700 transition hover:border-slate-950 hover:text-slate-950" aria-label="Mostrar colores extra" aria-expanded="false">
                                            +{{ $displayColors->count() - 8 }}
                                        </button>
                                    @endif
                                    <button
                                        type="button"
                                        data-product-color
                                        data-image="{{ $color['image'] }}"
                                        @class([
                                            'h-5 w-5 rounded-full border border-[#8E8E8E] ring-offset-2 transition hover:ring-2 hover:ring-slate-300',
                                            'hidden' => $loop->iteration > 8,
                                        ])
                                        @if ($loop->iteration > 8) data-extra-product-color @endif
                                        style="background-color: {{ $swatchColor }}"
                                        aria-label="Ver color {{ $color['name'] }}"
                                    ></button>
                                @endforeach

                            </div>
                        </div>
                    </article>
                    @endforeach
                @else
                    <div class="col-span-full grid min-h-64 place-items-center rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-5 text-center">
                        <div>
                            <p class="text-lg font-black text-slate-700">Sin productos sincronizados</p>
                            <p class="mt-1 text-sm text-slate-400">Sincroniza Odoo para mostrar los mas vendidos.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section id="manga-corta" class="bg-white px-5 pb-8 text-slate-950 sm:pb-10 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-8">
                <h2 class="text-2xl font-black uppercase tracking-tight">Estilo manga corta</h2>
                <p class="mt-2 text-sm text-slate-500">Polos frescos y versatiles para todos los dias.</p>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                @if ($shortSleeveProducts->isNotEmpty())
                    @foreach ($shortSleeveProducts as $product)
                    @php
                        $swatches = [
                            'azul' => '#1d4f91',
                            'beige' => '#ddcdbd',
                            'perla' => '#e8e0d6',
                            'cemento' => '#9b9b95',
                            'negro' => '#111111',
                            'vino' => '#7b1028',
                            'botella' => '#0f4f3b',
                            'plomo' => '#9a9aa0',
                            'pacay' => '#8c9b73',
                            'denim' => '#526f91',
                            'blanco' => '#f7f7f2',
                            'p. rosa' => '#e8b8bd',
                        ];
                        $displayColors = collect($product->display_colors ?? []);
                        $oldPrice = (float) $product->min_price > 0 ? ((float) $product->min_price / 0.8) : 0;
                    @endphp
                    <article class="group rounded-2xl bg-white p-4 shadow-xl shadow-slate-200/70 transition hover:-translate-y-1">
                        <a href="{{ route('web.products.show', $product->id) }}" class="relative block aspect-[4/5] overflow-hidden rounded-xl bg-[#F7F7F7]">
                            <img data-product-card-image src="{{ $product->display_image }}" alt="{{ $product->name }}" class="h-full w-full object-contain object-center transition duration-500 group-hover:scale-105">
                            <span class="absolute left-0 top-0 rounded-br-lg bg-red-50 px-3 py-1.5 text-base font-medium text-red-600">-20%</span>
                            <span class="absolute bottom-4 right-4 grid h-10 w-10 place-items-center rounded-full bg-slate-950 text-white shadow-lg" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
                            </span>
                        </a>
                        <div class="p-5">
                            <a href="{{ route('web.products.show', $product->id) }}" class="line-clamp-2 min-h-10 text-base font-bold text-slate-950 transition hover:text-cyan-700">{{ $product->name }}</a>
                            <div class="mt-2 flex items-baseline gap-2">
                                <p class="text-xl font-black">S/ {{ number_format((float) $product->min_price, 2) }}</p>
                                @if ($oldPrice > 0)
                                    <p class="text-sm text-slate-400 line-through">S/ {{ number_format($oldPrice, 2) }}</p>
                                @endif
                            </div>
                            <div class="mt-4 flex flex-wrap items-center gap-1.5">
                                @foreach ($displayColors as $color)
                                    @php
                                        $colorName = mb_strtolower($color['name']);
                                        $swatchColor = $swatches[$colorName] ?? '#b8b8bd';
                                    @endphp
                                    @if ($loop->iteration === 9 && $displayColors->count() > 8)
                                        <button type="button" data-product-colors-expand data-collapsed-label="+{{ $displayColors->count() - 8 }}" data-expanded-label="-" class="grid h-5 min-w-5 place-items-center rounded-full border border-slate-300 bg-white px-1 text-[11px] font-black leading-none text-slate-700 transition hover:border-slate-950 hover:text-slate-950" aria-label="Mostrar colores extra" aria-expanded="false">
                                            +{{ $displayColors->count() - 8 }}
                                        </button>
                                    @endif
                                    <button
                                        type="button"
                                        data-product-color
                                        data-image="{{ $color['image'] }}"
                                        @class([
                                            'h-5 w-5 rounded-full border border-[#8E8E8E] ring-offset-2 transition hover:ring-2 hover:ring-slate-300',
                                            'hidden' => $loop->iteration > 8,
                                        ])
                                        @if ($loop->iteration > 8) data-extra-product-color @endif
                                        style="background-color: {{ $swatchColor }}"
                                        aria-label="Ver color {{ $color['name'] }}"
                                    ></button>
                                @endforeach

                            </div>
                        </div>
                    </article>
                    @endforeach
                @else
                    <div class="col-span-full grid min-h-64 place-items-center rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-5 text-center">
                        <div>
                            <p class="text-lg font-black text-slate-700">Sin productos de manga corta</p>
                            <p class="mt-1 text-sm text-slate-400">Sincroniza Odoo para mostrar esta seccion.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section id="calidad" class="bg-white px-5 py-12 text-slate-950 sm:py-16 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-lg bg-white px-7 py-12 shadow-[0_18px_55px_rgba(17,17,17,0.06)] sm:px-12 lg:px-20 lg:py-20">
            <div class="grid items-center gap-12 lg:grid-cols-[1fr_1.05fr]">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-cyan-600">"Por que elegir Overshark"</p>
                    <h2 class="mt-5 max-w-xl text-3xl font-black leading-tight tracking-normal sm:text-4xl lg:text-5xl">
                        Calidad que se nota,<br class="hidden sm:block">
                        comodidad que se siente
                    </h2>
                    <div class="mt-6 h-0.5 w-14 bg-amber-400"></div>
                    <p class="mt-8 max-w-lg text-base leading-8 text-slate-500 sm:text-lg">
                        Polos disenados para el uso diario con materiales que garantizan durabilidad, frescura y confort.
                    </p>
                </div>

                <div class="grid gap-8 sm:grid-cols-3">
                    @foreach ([
                        [
                            'title' => 'No destine',
                            'copy' => 'Mantiene su color lavado tras lavado.',
                            'icon' => 'drop',
                        ],
                        [
                            'title' => 'No encoge',
                            'copy' => 'Conserva su forma original siempre.',
                            'icon' => 'shirt',
                        ],
                        [
                            'title' => 'No hace bolitas',
                            'copy' => 'Mayor durabilidad en el uso diario.',
                            'icon' => 'no-pilling',
                        ],
                    ] as $benefit)
                        <article class="text-center">
                            <div class="relative mx-auto grid h-20 w-20 place-items-center rounded-full bg-[#F7F7F7]">
                                <span class="absolute -right-0.5 top-0 h-9 w-9 rounded-full border-r-2 border-t-2 border-cyan-600"></span>
                                @if ($benefit['icon'] === 'drop')
                                    <svg class="h-10 w-10 text-slate-700" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M24 5C18 14 12 21.5 12 30a12 12 0 0 0 24 0C36 21.5 30 14 24 5Z"/>
                                        <path d="M18 31c1.2 4 4.2 6 8.8 5.7"/>
                                    </svg>
                                @elseif ($benefit['icon'] === 'shirt')
                                    <svg class="h-10 w-10 text-slate-700" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M17 8l7 4 7-4 9 5-5 10-4-2v19H17V21l-4 2-5-10 9-5Z"/>
                                        <path d="M20 9c.8 2.8 2.2 4.2 4 4.2S27.2 11.8 28 9"/>
                                        <path d="M19 20h10"/>
                                    </svg>
                                @else
                                    <svg class="h-10 w-10 text-slate-700" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="24" cy="24" r="15"/>
                                        <path d="M13 35 35 13"/>
                                        <path d="M17 22c3-2 7-2 11 0 2.4 1.2 4.2 1.2 6 0"/>
                                        <path d="M16 29c2.4-1.6 5.2-1.6 8.4 0 2.2 1.1 4.6 1.1 7.2 0"/>
                                    </svg>
                                @endif
                            </div>
                            <h3 class="mt-6 text-base font-black">{{ $benefit['title'] }}</h3>
                            <p class="mx-auto mt-4 max-w-[150px] text-sm leading-6 text-slate-500">{{ $benefit['copy'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section id="telas" class="bg-white px-5 pb-24 text-slate-950 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-5 lg:grid-cols-3">
                @foreach ([
                    ['name' => 'Pique', 'texture' => 'fabric-pique', 'icon' => 'waves'],
                    ['name' => 'Waffle', 'texture' => 'fabric-waffle', 'icon' => null],
                    ['name' => 'Jersey', 'texture' => 'fabric-jersey', 'icon' => 'feather'],
                ] as $fabric)
                    <article class="grid min-h-[360px] overflow-hidden rounded-lg bg-white shadow-xl shadow-slate-200/70 sm:grid-cols-[1.25fr_1fr]">
                        <div class="flex flex-col items-center justify-center p-8 text-center">
                            @if ($fabric['icon'])
                                <div class="mb-10 grid h-16 w-16 place-items-center rounded-full border border-slate-200 bg-white">
                                    @if ($fabric['icon'] === 'waves')
                                        <svg class="h-8 w-8 text-slate-950" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                            <path d="M4 8c2 0 2-1.2 4-1.2S10 8 12 8s2-1.2 4-1.2S18 8 20 8"/>
                                            <path d="M4 12c2 0 2-1.2 4-1.2S10 12 12 12s2-1.2 4-1.2S18 12 20 12"/>
                                            <path d="M4 16c2 0 2-1.2 4-1.2S10 16 12 16s2-1.2 4-1.2S18 16 20 16"/>
                                        </svg>
                                    @else
                                        <svg class="h-8 w-8 text-slate-950" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                            <path d="M20 4C12 4 6 9.5 5 20"/>
                                            <path d="M20 4c-1 8-6.5 13-15 16"/>
                                            <path d="M8 15c2.2-.3 4.2-1 6-2.4"/>
                                        </svg>
                                    @endif
                                </div>
                            @endif
                            <h2 class="text-xl font-black uppercase tracking-tight">{{ $fabric['name'] }}</h2>
                        </div>
                        <div class="fabric-texture {{ $fabric['texture'] }} min-h-[220px]"></div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
