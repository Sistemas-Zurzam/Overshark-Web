@extends('layouts.web')

@section('title', 'Overshark | Tienda')

@php
    $hero = [
        'hombre' => [
            'eyebrow' => 'Colección hombre',
            'title' => 'Nacido del océano',
            'accent' => 'Hecho para dominar',
            'copy' => 'Prendas esenciales con carácter, precisión y fuerza.',
            'image' => 'images/home/hero-model-hombre.webp',
            'width' => 300,
            'height' => 510,
            'imageClass' => 'os-hero-model h-[300px] w-auto sm:h-[360px] lg:h-[440px] xl:h-[480px]',
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
    $channels = [
        [
            'badge' => 'En vivo ahora',
            'title' => 'Compras en vivo',
            'copy' => 'Compra mientras transmitimos y aprovecha precios exclusivos del live.',
            'cta' => 'Ver live',
            'url' => route('web.tv'),
            'external' => false,
            'icon' => '<rect x="3" y="6" width="13" height="12" rx="2.5"/><path d="m16 10 5-3v10l-5-3"/>',
            'card' => 'border-live/70 from-live/15 shadow-[0_0_40px_rgb(229_72_77/15%)]',
            'iconClass' => 'bg-live text-white shadow-[0_0_24px_rgb(229_72_77/55%)]',
            'badgeClass' => 'bg-live/15 text-[#ff8a8e]',
            'ctaClass' => 'bg-live text-white hover:shadow-[0_10px_26px_rgb(229_72_77/45%)]',
            'live' => true,
        ],
        [
            'badge' => 'Responde en 1 min',
            'title' => 'Asesoría personalizada',
            'copy' => 'Habla con un asesor en la web y arma tu outfit con su ayuda.',
            'cta' => 'Iniciar chat',
            'url' => config('services.storefront.chat_url'),
            'icon' => '<path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/><path d="M19 19c0 1.5-1.5 2.5-4 2.5h-2"/>',
            'card' => 'border-neon/70 from-neon/15 shadow-[0_0_40px_rgb(47_111_208/18%)]',
            'iconClass' => 'bg-neon text-white shadow-[0_0_24px_rgb(47_111_208/60%)]',
            'badgeClass' => 'bg-neon/20 text-neon-soft',
            'ctaClass' => 'bg-neon text-white hover:bg-neon-soft hover:shadow-[0_10px_26px_rgb(47_111_208/45%)]',
            'live' => false,
        ],
        [
            'badge' => 'A tu ritmo',
            'title' => 'Compra manual',
            'copy' => 'Explora el catálogo, elige talla y color, y finaliza tu pedido cuando quieras.',
            'cta' => 'Ver catálogo',
            'url' => route('web.products.search', ['orden' => 'populares']),
            'external' => false,
            'icon' => '<path d="M5 8h14l1 13H4Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
            'card' => 'border-aqua/70 from-aqua/15 shadow-[0_0_40px_rgb(45_212_191/14%)]',
            'iconClass' => 'bg-aqua text-ink shadow-[0_0_24px_rgb(45_212_191/50%)]',
            'badgeClass' => 'bg-aqua/15 text-aqua',
            'ctaClass' => 'bg-aqua text-ink hover:shadow-[0_10px_26px_rgb(45_212_191/40%)]',
            'live' => false,
        ],
    ];
    $heroSlides = $banners->isNotEmpty() ? $banners : collect([null]);
    $catalogUrl = route('web.products.search', ['orden' => 'populares']);
@endphp

@section('content')
    <div class="os-ocean">
        <section id="home-carousel" class="relative isolate -mt-14 overflow-hidden xl:-mt-[52px]" data-carousel="slide" aria-labelledby="home-hero-title">
            <x-storefront.ocean-backdrop class="-z-10"/>
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgb(2_7_19/40%)_0%,rgb(2_7_19/12%)_45%,transparent_70%),linear-gradient(0deg,#030c1e_0%,transparent_30%)]" aria-hidden="true"></div>
            <canvas class="pointer-events-none absolute inset-0 -z-10 h-full w-full" data-ocean-particles aria-hidden="true"></canvas>

            <div class="mx-auto grid max-w-[1440px] grid-cols-1 items-end px-4 sm:px-6 lg:min-h-[500px] lg:grid-cols-[minmax(0,1.15fr)_minmax(0,.85fr)] lg:px-10 xl:min-h-[540px] xl:px-24">
                <div class="min-w-0 self-center pb-6 pt-[6.5rem] sm:pt-[7.5rem] lg:self-start lg:pb-24 lg:pt-[min(14vw,230px)]">
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
                                    src="{{ asset($hero['image']) }}"
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

        <div class="relative z-10 mx-auto -mt-3 max-w-[1300px] px-4 pb-16 sm:px-6 sm:pb-20 lg:-mt-8 lg:px-10">
            <x-storefront.virtual-fitting :ai-available="app(\App\Services\OutfitAi\OutfitAiGenerator::class)->isAvailable()"/>
            <div class="mt-5 grid gap-5 lg:grid-cols-3" aria-label="Formas de comprar" role="group">
                @foreach ($channels as $channel)
                    <article class="flex flex-col gap-4 rounded-2xl border bg-gradient-to-r via-panel/90 to-panel/90 p-5 backdrop-blur sm:flex-row sm:items-center lg:flex-col lg:items-start {{ $channel['card'] }}">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full {{ $channel['iconClass'] }}" aria-hidden="true">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $channel['icon'] !!}</svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.14em] {{ $channel['badgeClass'] }}">
                                @if ($channel['live'])
                                    <span class="neon-live-dot h-1.5 w-1.5 rounded-full bg-live" aria-hidden="true"></span>
                                @endif
                                {{ $channel['badge'] }}
                            </span>
                            <h2 class="mt-2 text-base font-black uppercase tracking-wide">{{ $channel['title'] }}</h2>
                            <p class="mt-1 text-xs leading-5 text-mist">{{ $channel['copy'] }}</p>
                        </div>
                        <a href="{{ $channel['url'] ?: '#contacto' }}" @if ($channel['url'] && ($channel['external'] ?? true)) target="_blank" rel="noopener" @endif class="os-focus inline-flex w-fit shrink-0 items-center gap-2 rounded-full px-5 py-2.5 text-xs font-black uppercase tracking-[0.12em] transition hover:-translate-y-0.5 {{ $channel['ctaClass'] }}">
                            {{ $channel['cta'] }}
                            <span aria-hidden="true">→</span>
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
@endsection
