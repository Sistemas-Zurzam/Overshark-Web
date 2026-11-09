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

    @include('web.partials.home-sections', ['line' => 'hombre'])
@endsection
