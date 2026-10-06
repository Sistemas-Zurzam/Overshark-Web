@extends('layouts.web')

@section('title', 'Overshark | Tienda')

@php
    $storeLine = request()->query('linea') === 'mujer' ? 'mujer' : 'hombre';
    $liveUrl = config('services.storefront.live_url');
    $chatUrl = config('services.storefront.chat_url');
    $heroSlides = $banners->isNotEmpty()
        ? $banners
        : collect([null]);
    $categories = [
        ['title' => 'Polos', 'query' => 'polo', 'position' => 'object-[64%_center]'],
        ['title' => 'Camiseros', 'query' => 'camisero', 'position' => 'object-[84%_center]'],
        ['title' => 'Waffle', 'query' => 'waffle', 'position' => 'object-[100%_center]'],
    ];
@endphp

@section('content')
    <section id="home-carousel" class="neon-stars relative overflow-hidden border-b border-edge/50" data-carousel="slide">
        <div class="pointer-events-none absolute -right-40 top-10 h-[520px] w-[520px] rounded-full bg-neon/25 blur-[120px]" aria-hidden="true"></div>

        <div class="relative mx-auto grid max-w-[1440px] grid-cols-1 lg:min-h-[640px] lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
            <div class="relative z-10 flex min-w-0 flex-col justify-center px-5 pb-10 pt-12 sm:px-10 lg:py-20 lg:pl-28 lg:pr-0">
                <p class="text-[10px] font-medium uppercase tracking-[0.42em] text-mist sm:text-[11px]">
                    Colección {{ $storeLine }} · Overshark
                </p>
                <h1 class="mt-6">
                    <span class="block text-[27px] font-light uppercase leading-none tracking-[0.05em] sm:text-5xl lg:whitespace-nowrap lg:text-[52px] xl:text-[60px]">Nacido del océano,</span>
                    <span class="mt-2 block font-display text-[40px] italic leading-[1.05] sm:text-6xl lg:whitespace-nowrap lg:text-[72px] xl:text-[84px]">hecho para <span class="neon-hero-title-accent">dominar</span></span>
                </h1>
                <p class="mt-6 max-w-md text-sm text-mist sm:text-base">Prendas esenciales con carácter, precisión y fuerza.</p>

                <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-4">
                    <a href="#categorias" class="btn-light px-6 py-3.5">
                        Ver colección
                        <span aria-hidden="true">→</span>
                    </a>
                    <nav class="flex flex-wrap items-center gap-3 text-xs text-mist" aria-label="Colecciones destacadas">
                        @foreach ($categories as $category)
                            @if (! $loop->first)
                                <span class="text-haze" aria-hidden="true">·</span>
                            @endif
                            <a href="{{ route('web.products.search', ['q' => $category['query']]) }}" class="underline decoration-edge-strong underline-offset-4 transition hover:text-white hover:decoration-neon-soft">{{ $category['title'] }}</a>
                        @endforeach
                    </nav>
                </div>

                @if ($heroSlides->count() > 1)
                    <div class="mt-10 flex gap-2">
                        @foreach ($heroSlides as $slide)
                            <button
                                type="button"
                                @class(['slider-dot transition-all', 'is-active' => $loop->first])
                                aria-current="{{ $loop->first ? 'true' : 'false' }}"
                                aria-label="Slide {{ $loop->iteration }}"
                                data-carousel-slide-to="{{ $loop->index }}"
                            ></button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="relative flex min-w-0 items-end justify-end lg:-ml-24">
                @foreach ($heroSlides as $banner)
                    <div class="{{ $loop->first ? 'block' : 'hidden' }} relative w-full" data-carousel-item>
                        <img
                            src="{{ $banner?->imageUrl() ?? asset('images/default-hero-banner.png') }}"
                            class="neon-hero-image block h-auto w-full"
                            alt="{{ $banner?->name ?? 'Modelos Overshark usando polos de la colección' }}"
                            @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                        >
                        @foreach ($banner?->buttons ?? [] as $button)
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
                    </div>
                @endforeach
                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-ink to-transparent" aria-hidden="true"></div>
            </div>
        </div>

        <div class="relative z-10 mx-auto grid max-w-7xl gap-5 px-5 pb-14 sm:px-10 md:grid-cols-2 lg:-mt-6 lg:px-8">
            <article class="flex flex-col gap-4 rounded-2xl border border-live/70 bg-gradient-to-r from-live/15 via-panel/90 to-panel/90 p-5 shadow-[0_0_40px_rgb(229_72_77/15%)] backdrop-blur sm:flex-row sm:items-center">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-live text-white shadow-[0_0_24px_rgb(229_72_77/55%)]" aria-hidden="true">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="13" height="12" rx="2.5"/><path d="m16 10 5-3v10l-5-3"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-live/15 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-[#ff8a8e]">
                        <span class="neon-live-dot h-1.5 w-1.5 rounded-full bg-live" aria-hidden="true"></span>
                        En vivo ahora
                    </span>
                    <h2 class="mt-2 text-base font-black uppercase tracking-wide">Compras en vivo</h2>
                    <p class="mt-1 text-xs leading-5 text-mist">Compra mientras transmitimos y aprovecha precios exclusivos del live.</p>
                </div>
                <a href="{{ $liveUrl ?: '#contacto' }}" @if ($liveUrl) target="_blank" rel="noopener" @endif class="inline-flex w-fit shrink-0 items-center gap-2 rounded-full bg-live px-5 py-2.5 text-xs font-black uppercase tracking-[0.12em] text-white transition hover:-translate-y-0.5 hover:shadow-[0_10px_26px_rgb(229_72_77/45%)]">
                    Ver live
                    <span aria-hidden="true">→</span>
                </a>
            </article>

            <article class="flex flex-col gap-4 rounded-2xl border border-neon/70 bg-gradient-to-r from-neon/15 via-panel/90 to-panel/90 p-5 shadow-[0_0_40px_rgb(47_111_208/18%)] backdrop-blur sm:flex-row sm:items-center">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-neon text-white shadow-[0_0_24px_rgb(47_111_208/60%)]" aria-hidden="true">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/><path d="M19 19c0 1.5-1.5 2.5-4 2.5h-2"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <span class="inline-flex rounded-full bg-neon/20 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-neon-soft">Responde en 1 min</span>
                    <h2 class="mt-2 text-base font-black uppercase tracking-wide">Asesoría personalizada</h2>
                    <p class="mt-1 text-xs leading-5 text-mist">Habla con un asesor en la web y arma tu outfit con su ayuda.</p>
                </div>
                <a href="{{ $chatUrl ?: '#contacto' }}" @if ($chatUrl) target="_blank" rel="noopener" @endif class="inline-flex w-fit shrink-0 items-center gap-2 rounded-full bg-neon px-5 py-2.5 text-xs font-black uppercase tracking-[0.12em] text-white transition hover:-translate-y-0.5 hover:bg-neon-soft hover:shadow-[0_10px_26px_rgb(47_111_208/45%)]">
                    Iniciar chat
                    <span aria-hidden="true">→</span>
                </a>
            </article>
        </div>
    </section>

    <section id="categorias" class="scroll-mt-32 px-5 py-16 sm:py-20 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <p class="text-[11px] font-medium uppercase tracking-[0.32em] text-mist">Categorías Overshark</p>
            <h2 class="mt-3 text-2xl font-black uppercase tracking-tight sm:text-4xl">Explora por <span class="text-neon-soft">categoría</span></h2>

            <div class="-mx-5 mt-9 flex snap-x snap-mandatory gap-5 overflow-x-auto px-5 pb-4 lg:mx-0 lg:grid lg:grid-cols-3 lg:overflow-visible lg:px-0 lg:pb-0">
                @foreach ($categories as $category)
                    <a href="{{ route('web.products.search', ['q' => $category['query']]) }}" class="group relative block aspect-[3/4] basis-[78vw] shrink-0 snap-start overflow-hidden rounded-2xl border border-edge bg-panel transition hover:border-neon-soft lg:basis-auto">
                        <img src="{{ asset('images/default-hero-banner.png') }}" alt="{{ $category['title'] }} Overshark" class="absolute inset-0 h-full w-full object-cover {{ $category['position'] }} transition duration-700 group-hover:scale-105" loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/10 to-transparent"></div>
                        <span class="absolute bottom-6 left-6 flex items-center gap-2 text-xl font-black uppercase tracking-wide text-white">
                            {{ $category['title'] }}
                            <span class="transition group-hover:translate-x-1" aria-hidden="true">→</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section id="combos" class="scroll-mt-32 px-5 py-16 sm:py-20 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-9 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.32em] text-mist">Promociones Overshark</p>
                    <h2 class="mt-3 text-2xl font-black uppercase tracking-tight sm:text-4xl">Combos más <span class="text-neon-soft">pedidos</span></h2>
                    <p class="mt-2 text-sm text-haze">Encuentra promociones listas para elegir y comprar.</p>
                </div>
                <button type="button" data-combos-toggle class="btn-secondary hidden px-5 py-3 sm:inline-flex" aria-expanded="false" aria-controls="combos-menu">
                    Ver todos los combos
                </button>
            </div>

            @if ($combos->isEmpty())
                <div class="grid min-h-64 place-items-center rounded-3xl border border-dashed border-edge bg-panel px-5 text-center">
                    <div>
                        <p class="text-lg font-black text-mist">Próximamente nuevos combos</p>
                        <p class="mt-1 text-sm text-haze">Las promociones activas aparecerán en esta sección.</p>
                    </div>
                </div>
            @else
                <div class="-mx-5 flex snap-x snap-mandatory gap-6 overflow-x-auto px-5 pb-4 lg:mx-0 lg:grid lg:grid-cols-2 lg:overflow-visible lg:px-0 lg:pb-0">
                    @foreach ($combos as $combo)
                        <article class="group relative min-h-[390px] basis-[86vw] shrink-0 snap-start overflow-hidden rounded-3xl border border-edge bg-panel transition hover:border-neon/70 lg:basis-auto">
                            <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-105" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/85 via-42% to-transparent"></div>
                            <div class="relative flex min-h-[390px] max-w-sm flex-col justify-center p-7 sm:p-10">
                                <span class="flex w-fit items-center gap-2 rounded-full border border-edge bg-ink/70 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-neon-soft">
                                    <img src="{{ asset('images/iconos/fuego_combo.svg') }}" alt="" class="h-4 w-4" aria-hidden="true">
                                    Combo destacado
                                </span>
                                <h3 class="mt-6 text-4xl font-black uppercase leading-none sm:text-5xl">{{ $combo->name }}</h3>
                                @if ($combo->price !== null)
                                    <p class="mt-4 text-2xl font-black">S/ {{ number_format((float) $combo->price, 2) }}</p>
                                @endif
                                <p class="mt-2 max-w-xs text-sm leading-6 text-mist">{{ $combo->modality ?: 'Promoción' }} · {{ $combo->quantityLabel() }}.</p>
                                <a href="{{ $combo->url ?: route('web.combos.show', $combo) }}" class="btn-primary mt-8 w-fit px-7 py-3.5">
                                    Ver combo
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section id="productos" class="scroll-mt-32 px-5 pb-20 pt-4 sm:pb-24 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-9 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div id="marcas" class="scroll-mt-32">
                    <p class="text-[11px] font-medium uppercase tracking-[0.32em] text-mist">Selección de marcas</p>
                    <h2 class="mt-3 text-2xl font-black uppercase tracking-tight sm:text-4xl">Productos más <span class="text-neon-soft">vendidos</span></h2>
                    <p class="mt-2 text-sm text-haze">Elige el color y mira la foto disponible para ese modelo.</p>
                </div>
                <a href="{{ route('web.products.search', ['q' => 'polo']) }}" class="btn-secondary hidden px-5 py-3 sm:inline-flex">
                    Ver todos los productos
                </a>
            </div>

            <div class="-mx-5 flex snap-x snap-mandatory gap-5 overflow-x-auto px-5 pb-4 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-4">
                @forelse ($bestSellingProducts as $product)
                    @include('web.partials.product-card', ['product' => $product])
                @empty
                    <div class="col-span-full grid min-h-64 basis-full shrink-0 place-items-center rounded-3xl border border-dashed border-edge bg-panel px-5 text-center">
                        <div>
                            <p class="text-lg font-black text-mist">Sin productos sincronizados</p>
                            <p class="mt-1 text-sm text-haze">Agrega productos al catálogo para mostrarlos aquí.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section id="manga-corta" class="px-5 pb-10 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-8">
                <p class="text-[11px] font-medium uppercase tracking-[0.32em] text-mist">Para todos los días</p>
                <h2 class="mt-3 text-2xl font-black uppercase tracking-tight sm:text-3xl">Estilo manga <span class="text-neon-soft">corta</span></h2>
                <p class="mt-2 text-sm text-haze">Polos frescos y versátiles para todos los días.</p>
            </div>

            <div class="-mx-5 flex snap-x snap-mandatory gap-6 overflow-x-auto px-5 pb-4 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-3">
                @forelse ($shortSleeveProducts as $product)
                    @include('web.partials.product-card', ['product' => $product, 'showBag' => true])
                @empty
                    <div class="col-span-full grid min-h-64 basis-full shrink-0 place-items-center rounded-3xl border border-dashed border-edge bg-panel px-5 text-center">
                        <div>
                            <p class="text-lg font-black text-mist">Sin productos de manga corta</p>
                            <p class="mt-1 text-sm text-haze">Agrega productos de manga corta para mostrarlos aquí.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section id="nosotros" class="scroll-mt-32 px-5 py-12 sm:py-16 lg:px-8">
        <div class="relative mx-auto max-w-7xl overflow-hidden rounded-3xl border border-edge bg-panel px-6 py-10 sm:px-12 sm:py-12 lg:px-20 lg:py-20">
            <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-neon/20 blur-[100px]" aria-hidden="true"></div>
            <div class="relative grid w-full items-center gap-12 lg:grid-cols-[1fr_1.05fr]">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.32em] text-neon-soft">Por qué elegir Overshark</p>
                    <h2 class="mt-5 max-w-xl text-3xl font-black leading-tight sm:text-4xl lg:text-5xl">
                        Calidad que se nota,<br class="hidden sm:block">
                        <span class="font-display font-normal italic text-mist">comodidad que se siente</span>
                    </h2>
                    <div class="mt-6 h-0.5 w-14 bg-gradient-to-r from-neon to-neon-soft"></div>
                    <p class="mt-8 max-w-lg text-base leading-8 text-mist sm:text-lg">
                        Polos diseñados para el uso diario con materiales que garantizan durabilidad, frescura y confort.
                    </p>
                </div>

                <div class="-mx-6 flex snap-x snap-mandatory gap-6 overflow-x-auto px-6 pb-4 sm:mx-0 sm:grid sm:grid-cols-3 sm:overflow-visible sm:px-0 sm:pb-0">
                    @foreach ([
                        ['title' => 'No destiñe', 'copy' => 'Mantiene su color lavado tras lavado.', 'icon' => 'gota.svg'],
                        ['title' => 'No encoge', 'copy' => 'Conserva su forma original siempre.', 'icon' => 'polo.svg'],
                        ['title' => 'No hace bolitas', 'copy' => 'Mayor durabilidad en el uso diario.', 'icon' => 'bolitas.svg'],
                    ] as $benefit)
                        <article class="basis-[54vw] shrink-0 snap-start text-center sm:basis-auto">
                            <div class="mx-auto grid h-20 w-20 place-items-center rounded-full border border-edge-strong bg-panel-2 shadow-[0_0_30px_rgb(47_111_208/25%)]">
                                <img src="{{ asset('images/iconos/'.$benefit['icon']) }}" alt="" class="h-10 w-10 object-contain brightness-0 invert" aria-hidden="true">
                            </div>
                            <h3 class="mt-6 text-base font-black">{{ $benefit['title'] }}</h3>
                            <p class="mx-auto mt-3 max-w-[150px] text-sm leading-6 text-haze">{{ $benefit['copy'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section id="telas" class="px-5 pb-24 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="-mx-5 flex snap-x snap-mandatory gap-5 overflow-x-auto px-5 pb-4 lg:mx-0 lg:grid lg:grid-cols-3 lg:overflow-visible lg:px-0 lg:pb-0">
                @foreach ([
                    ['name' => 'Pique', 'class' => 'fabric-pique'],
                    ['name' => 'Waffle', 'class' => 'fabric-waffle'],
                    ['name' => 'Jersey', 'class' => 'fabric-jersey'],
                ] as $fabric)
                    <article class="grid min-h-60 basis-[82vw] shrink-0 snap-start grid-cols-2 overflow-hidden rounded-2xl border border-edge bg-panel lg:basis-auto">
                        <div class="flex flex-col justify-center px-6">
                            <p class="text-[10px] font-medium uppercase tracking-[0.3em] text-haze">Tela</p>
                            <h3 class="mt-2 text-xl font-black uppercase tracking-wide">{{ $fabric['name'] }}</h3>
                        </div>
                        <div class="fabric-texture {{ $fabric['class'] }}" role="img" aria-label="Textura de tela {{ $fabric['name'] }}"></div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
