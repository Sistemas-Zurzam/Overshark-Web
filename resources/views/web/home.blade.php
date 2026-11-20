@extends('layouts.web')

@section('title', 'Overshark | Tienda')

@php
    $hero = [
        'hombre' => [
            'eyebrow' => \App\Support\SiteContent::get('hero.hombre.eyebrow'),
            'title' => \App\Support\SiteContent::get('hero.hombre.title'),
            'accent' => \App\Support\SiteContent::get('hero.hombre.accent'),
            'copy' => \App\Support\SiteContent::get('hero.hombre.copy'),
            'image' => 'images/home/hero-model-hombre.webp',
            'width' => 300,
            'height' => 510,
            'imageClass' => 'os-hero-model h-[300px] w-auto sm:h-[360px] lg:h-[min(640px,calc(100svh-var(--header-h)-70px))]',
            'alt' => 'Modelo Overshark con polo verde de la colección hombre',
        ],
        'mujer' => [
            'eyebrow' => 'Colección mujer',
            'title' => 'Nacida del océano',
            'accent' => 'Hecha para brillar',
            'copy' => 'Básicos suaves y versátiles para todos los días.',
            'image' => 'images/colecciones/mujer.jpg',
            'width' => 414,
            'height' => 320,
            'imageClass' => 'neon-hero-image--portrait mb-10 w-full max-w-[460px]',
            'alt' => 'Modelo con baby tee y buzo rosado de la colección mujer',
        ],
    ][$storeLine];
    $heroSlides = $banners->isNotEmpty() ? $banners : collect([null]);
    $catalogUrl = route('web.products.search', ['orden' => 'populares']);
@endphp

@section('content')
    @if ($storeLine === 'hombre' && $banners->isEmpty())
        {{-- Hero Hombre · Figma "F · Full-bleed Depredador / Hero imponente" (nodo 672:41266). Escena de 1440 × 880 con medidas en %. --}}
        @php
            $oceanImg = fn (string $file) => \App\Support\SiteContent::asset('images/hero-oceano/'.$file);
            $heroEyebrow = \App\Support\SiteContent::get('hero.hombre.eyebrow') ?: 'COLECCIÓN HOMBRE · 2027';
            $heroTitle = \App\Support\SiteContent::get('hero.hombre.title') ?: 'NACIDO DEL';
            $heroAccent = \App\Support\SiteContent::get('hero.hombre.accent') ?: 'OCÉANO.';
            $heroCopy = \App\Support\SiteContent::get('hero.hombre.copy') ?: 'Prendas esenciales con carácter, precisión y fuerza.';
            // La foto de Banners → «hero_hombre»: si el admin subió una, reemplaza el recorte de modelos del diseño.
            $heroUploaded = \App\Support\SiteContent::asset('images/home/hero-model-hombre.webp');
            $heroModels = str_contains($heroUploaded, '/storage/') ? $heroUploaded : $oceanImg('modelos-azul-vino.webp');
            $heroInter ="font-family:'Inter','Montserrat',sans-serif";
            $heroCta1 = [\App\Support\SiteContent::get('hero.hombre.cta1_text') ?: 'Ver colección', \App\Support\SiteContent::get('hero.hombre.cta1_url') ?: $catalogUrl];
            $heroCta2 = [\App\Support\SiteContent::get('hero.hombre.cta2_text') ?: 'Arma tu outfit', \App\Support\SiteContent::get('hero.hombre.cta2_url') ?: route('web.outfit')];
            $heroCategories = collect(explode(',', (string) (\App\Support\SiteContent::get('hero.hombre.categories') ?: 'Camisas, Poleras, Polos, Joggers')))
                ->map(fn (string $name) => trim($name))->filter()->take(6)
                ->map(fn (string $name) => [$name, route('web.products.search', ['categoria' => \Illuminate\Support\Str::slug($name)])]);
            // Tarjeta «Más vendido»: el primero de Los más vendidos de la línea (mismo orden que Tienda → populares).
            $heroTop = $bestSellers->first();
            $heroArrow = '<svg class="h-4 w-4 shrink-0" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8H13M9 12L13 8L9 4"/></svg>';
        @endphp
        <section id="home-carousel" class="relative isolate mt-[calc(var(--header-h)*-1)] overflow-hidden bg-[#030b16]" style="{{ $heroInter }}" aria-labelledby="home-hero-title">
            {{-- Capas de fondo a ancho completo --}}
            <img src="{{ $oceanImg('fondo.webp') }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover" fetchpriority="high" aria-hidden="true">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgb(3_11_22/75%)_0%,rgb(3_11_22/15%)_35%,rgb(3_11_22/55%)_70%,#030b16_100%)]" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(3_11_22/95%)_0%,rgb(3_11_22/35%)_32%,transparent_50%,transparent_80%,rgb(3_11_22/75%)_100%)]" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 h-[34%] bg-[linear-gradient(180deg,transparent_0%,rgb(3_11_22/85%)_60%,#030b16_100%)]" aria-hidden="true"></div>

            <div class="relative mx-auto flex w-full max-w-[1440px] flex-col px-5 pb-8 pt-[calc(var(--header-h)+2rem)] sm:px-8 lg:block lg:aspect-[1440/880] lg:px-0 lg:pb-0 lg:pt-0">
                {{-- Halo de bioluminiscencia --}}
                <img src="{{ $oceanImg('halo.svg') }}" alt="" class="pointer-events-none absolute left-[23.6%] top-[19.3%] hidden h-[59%] w-[62.5%] max-w-none scale-[1.356] lg:block" aria-hidden="true">

                {{-- Wordmark gigante --}}
                <p class="pointer-events-none absolute left-[2%] top-[13.4%] hidden select-none whitespace-nowrap bg-gradient-to-b from-white/95 via-[#d9ebff]/35 via-[55%] to-[#54c8ff]/[0.02] bg-clip-text font-black leading-[0.9] text-transparent lg:block" style="font-size:min(15.7vw,226px);letter-spacing:-0.04em" aria-hidden="true">OVERSHARK</p>

                {{-- Modelos --}}
                <img src="{{ $oceanImg('modelo-polo-salvia.webp') }}" alt="" width="300" height="510" class="pointer-events-none absolute left-[41.7%] top-[22.3%] hidden h-[78%] w-[30.8%] max-w-none object-contain lg:block" aria-hidden="true">
                <img src="{{ $heroModels }}" alt="Tres modelos con polo azul, polo salvia y camisa vino de la colección hombre" width="1536" height="1024" class="pointer-events-none order-last mx-auto mt-6 h-auto w-full max-w-[560px] object-contain lg:absolute lg:left-[21.9%] lg:top-[29.4%] lg:mt-0 lg:h-[73.9%] lg:w-[71.2%] lg:max-w-none">

                {{-- Mensaje --}}
                <div class="relative z-10 flex flex-col items-start gap-5 lg:absolute lg:left-[5.55%] lg:top-[51.6%] lg:gap-[clamp(10px,1.4vw,20px)]">
                    <div class="flex items-center gap-3">
                        <span class="h-0.5 w-8 bg-[#54c8ff]" aria-hidden="true"></span>
                        <p class="text-[12px] font-bold uppercase tracking-[0.18em] text-[#54c8ff] sm:text-[13px]">{{ $heroEyebrow }}</p>
                    </div>
                    <h1 id="home-hero-title" class="flex flex-col font-black uppercase">
                        <span class="block text-[40px] leading-[0.92] tracking-[-0.02em] text-white sm:text-[48px] lg:text-[clamp(34px,3.89vw,56px)]">{{ $heroTitle }}</span>
                        <span class="block text-[64px] leading-[0.88] tracking-[-0.03em] text-[#54c8ff] sm:text-[80px] lg:text-[clamp(56px,6.67vw,96px)]">{{ $heroAccent }}</span>
                    </h1>
                    <p class="max-w-[400px] text-[18px] font-medium leading-[1.45] text-[#d9ebff]/85 sm:text-[22px] lg:text-[clamp(16px,1.67vw,24px)]">{{ $heroCopy }}</p>
                    <div class="flex flex-wrap items-start gap-4 pt-3">
                        <a href="{{ $heroCta1[1] }}" class="os-focus inline-flex items-center gap-3 border border-[#5bb1ff] bg-[#1b5fe0] px-7 py-[18px] text-[13px] font-bold uppercase leading-none tracking-[0.08em] text-white shadow-[0_10px_32px_rgb(26_115_255/45%)] transition hover:bg-[#2a6df0]">{{ $heroCta1[0] }} {!! $heroArrow !!}</a>
                        <a href="{{ $heroCta2[1] }}" class="os-focus inline-flex items-center gap-3 border border-white/35 bg-white/[0.04] px-7 py-[18px] text-[13px] font-bold uppercase leading-none tracking-[0.08em] text-white transition hover:bg-white/10">{{ $heroCta2[0] }} {!! $heroArrow !!}</a>
                    </div>
                </div>

                {{-- Tarjeta · Nuevo drop --}}
                <a href="{{ $heroTop ? route('web.products.show', $heroTop->id) : $catalogUrl }}" class="os-focus relative z-10 mt-8 flex w-full max-w-[360px] items-center gap-4 rounded-[14px] border border-white/[0.16] bg-white/[0.06] py-3 pl-3 pr-5 shadow-[0_20px_20px_rgb(0_0_0/40%)] backdrop-blur-[12px] transition hover:bg-white/10 lg:absolute lg:left-[69.5%] lg:top-[73.2%] lg:mt-0 lg:h-[15.9%] lg:w-[25%] lg:max-w-none">
                    <span class="grid size-[116px] shrink-0 place-items-center overflow-hidden rounded-[10px] bg-[#0b223b] lg:size-auto lg:h-full lg:aspect-square">
                        <img src="{{ $heroTop?->display_image ?: $oceanImg('modelo-polo-salvia.webp') }}" alt="{{ $heroTop ? \App\Support\ProductName::title((string) $heroTop->name) : 'Polo salvia' }}" loading="lazy" class="h-full w-full object-cover">
                    </span>
                    <span class="flex min-w-0 flex-col items-start gap-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-[0.18em] text-[#54c8ff]">Más vendido</span>
                        <span class="max-w-[190px] truncate text-[17px] font-semibold text-white">{{ $heroTop ? \App\Support\ProductName::title((string) $heroTop->name) : 'Polo salvia' }}</span>
                        <span class="flex items-center gap-2 text-[13px] font-medium text-[#d9ebff]/80">Ver producto {!! str_replace('h-4 w-4', 'h-3.5 w-3.5', $heroArrow) !!}</span>
                    </span>
                </a>

                {{-- Pie del hero --}}
                <div class="relative z-10 mt-8 flex items-center justify-between gap-4 text-[11px] font-bold uppercase tracking-[0.2em] text-[#d9ebff]/60 lg:absolute lg:inset-x-[5.55%] lg:bottom-[3.2%] lg:mt-0">
                    <a href="#hh-main" class="os-focus hidden transition hover:text-white sm:inline">↓&nbsp; Deslizar para explorar</a>
                    <nav class="flex flex-wrap gap-x-3 gap-y-1 sm:ml-auto" aria-label="Categorías">
                        @foreach ($heroCategories as [$categoryLabel, $categoryUrl])
                            <a href="{{ $categoryUrl }}" class="os-focus transition hover:text-white">{{ $categoryLabel }}</a>@if (! $loop->last)<span aria-hidden="true">·</span>@endif
                        @endforeach
                    </nav>
                </div>
            </div>
        </section>
    @else
    <div class="os-ocean">
        <section id="home-carousel" class="relative isolate mt-[calc(var(--header-h)*-1)] flex min-h-[100svh] flex-col overflow-hidden pb-6 lg:pb-8" data-carousel="slide" aria-labelledby="home-hero-title">
            <x-storefront.ocean-backdrop class="-z-10"/>
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(2_7_19/40%)_0%,rgb(2_7_19/12%)_45%,transparent_70%)]" aria-hidden="true"></div>
            <canvas class="pointer-events-none absolute inset-0 -z-10 h-full w-full" data-ocean-particles aria-hidden="true"></canvas>

            <div class="mx-auto grid w-full max-w-[1440px] flex-1 grid-cols-1 items-end px-4 sm:px-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,.85fr)] lg:px-10 xl:px-24">
                <div class="min-w-0 self-center pb-6 pt-[calc(var(--header-h)+2.5rem)] sm:pt-[calc(var(--header-h)+3.5rem)] lg:self-center lg:pb-10 lg:pt-[var(--header-h)]">
                    <p class="text-xs font-medium uppercase tracking-[0.34em] text-white/90 sm:text-sm">{{ $hero['eyebrow'] }}</p>
                    <h1 id="home-hero-title" class="mt-3 font-heading font-black uppercase leading-[1.02] tracking-tight">
                        <span class="block text-[34px] text-white sm:text-5xl lg:whitespace-nowrap lg:text-[40px] xl:text-[46px] min-[1400px]:text-[52px]">{{ $hero['title'] }}</span>
                        <span class="block text-[34px] text-electric sm:text-5xl lg:whitespace-nowrap lg:text-[40px] xl:text-[46px] min-[1400px]:text-[52px]">{{ $hero['accent'] }}</span>
                    </h1>
                    <p class="mt-3 max-w-xl text-base font-medium text-white/90 sm:text-lg xl:text-[22px]">{{ $hero['copy'] }}</p>

                    <a href="{{ $catalogUrl }}" class="os-btn-ghost os-focus mt-7 h-12 px-8 sm:h-14 sm:px-10">
                        Ver colección
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg>
                    </a>

                    @if ($heroSlides->count() > 1)
                        <div class="mt-8 flex gap-2">
                            @foreach ($heroSlides as $slide)
                                <button
                                    type="button"
                                    @class(['slider-dot os-focus transition-all', 'is-active' => $loop->first])
                                    aria-current="{{ $loop->first ? 'true' : 'false' }}"
                                    aria-label="Mostrar banner {{ $loop->iteration }}"
                                    data-carousel-slide-to="{{ $loop->index }}"
                                ></button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="relative flex min-w-0 items-end justify-center lg:justify-start">
                    <div class="pointer-events-none absolute bottom-0 left-1/2 h-[80%] w-[80%] -translate-x-1/2 rounded-full bg-electric/25 blur-[90px] lg:left-[35%]" aria-hidden="true"></div>
                    @foreach ($heroSlides as $banner)
                        <div class="{{ $loop->first ? 'block' : 'hidden' }} relative" data-carousel-item>
                            @if ($banner)
                                <img
                                    src="{{ $banner->imageUrl() }}"
                                    class="neon-hero-image mb-10 block h-auto w-full"
                                    alt="{{ $banner->name }}"
                                    @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                                >
                                @foreach ($banner->buttons ?? [] as $button)
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
                                    <a href="{{ in_array($button['url'] ?? null, [null, '', '#productos'], true) ? $catalogUrl : $button['url'] }}" class="absolute z-20 -translate-x-1/2 -translate-y-1/2 {{ $radiusClass }} {{ $sizeClass }} font-black uppercase shadow-lg transition hover:-translate-y-[calc(50%+2px)]" style="left: {{ $button['x'] ?? 20 }}%; top: {{ $button['y'] ?? 50 }}%; background-color: {{ $button['bg_color'] ?? '#111111' }}; color: {{ $button['text_color'] ?? '#ffffff' }};">
                                        {{ $button['text'] ?? 'Comprar ahora' }}
                                    </a>
                                @endforeach
                            @else
                                <img
                                    src="{{ \App\Support\SiteContent::asset($hero['image']) }}"
                                    width="{{ $hero['width'] }}"
                                    height="{{ $hero['height'] }}"
                                    class="relative block {{ $hero['imageClass'] }}"
                                    alt="{{ $hero['alt'] }}"
                                    fetchpriority="high"
                                >
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

    </div>
    @endif

    @include('web.partials.home-sections', ['line' => 'hombre'])
@endsection
