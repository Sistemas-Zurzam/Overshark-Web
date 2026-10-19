@extends('layouts.web')

{{--
    Home de Overshark Girls (línea mujer), según el diseño de Figma "Propuestas" (overshark-web/index.html#mujer).
    Estilos en resources/css/girls.css (todo cuelga de .gl) y animaciones en resources/js/girls.js.
    Combos, productos y el contador vienen de GirlsHome: si no hay datos, esas secciones no se muestran.
--}}

@section('title', 'Overshark Girls | Colección mujer')
@section('body-class', 'gl')
@section('header-class', 'gl-hdr fixed inset-x-0 top-0 z-50')
@section('main-class', '')

@php
    $catalogUrl = route('web.girls.products');
    $searchUrl = fn (string $q) => route('web.girls.products', ['q' => $q]);

    $access = [
        ['primary' => true, 'icon' => 'bag', 'badge' => 'Toda la tienda', 'title' => 'Comprar ahora', 'copy' => 'Explora todos los productos: baby tees, tops, buzos y más.', 'cta' => 'Ver productos', 'url' => $catalogUrl, 'style' => ''],
        ['live' => true, 'icon' => 'video', 'badge' => 'En vivo ahora', 'title' => 'Compras en vivo', 'copy' => 'Compra mientras transmitimos y aprovecha precios exclusivos del live.', 'cta' => 'Ver live', 'url' => route('web.tv'), 'style' => '--ac: linear-gradient(135deg,#FF6F9F,#D63A78); --ac-line: #E0569A; --ac-text: #B52A62;'],
        ['icon' => 'headset', 'badge' => 'Responde en 1 min', 'title' => 'Asesoría personalizada', 'copy' => 'Habla con una asesora en la web y arma tu outfit con su ayuda.', 'cta' => 'Iniciar chat', 'url' => config('services.storefront.chat_url') ?: '#contacto', 'external' => (bool) config('services.storefront.chat_url'), 'style' => '--ac: linear-gradient(135deg,#E07AA0,#A9456B); --ac-line: #E07AA0; --ac-text: #8F3A5B;'],
    ];
    $categories = [
        ['name' => 'Baby tees', 'image' => 'm-cat-baby-tees.jpg', 'q' => 'baby ty'],
        ['name' => 'Tops', 'image' => 'm-cat-tops.jpg', 'q' => 'top'],
        ['name' => 'Buzos', 'image' => 'm-cat-buzos.jpg', 'q' => 'buzo'],
    ];
    $looks = [
        ['title' => "Baby tees,\n3 formas de llevarlo", 'copy' => 'Combina colores y crea tu look de todos los días.', 'image' => 'm-look-1.jpg', 'q' => 'baby ty'],
        ['title' => "Manga larga\n+ jean", 'copy' => 'Overshark Girls + tu clóset', 'image' => 'm-look-2.jpg', 'q' => 'baby ty manga'],
        ['title' => 'Escote + jean', 'copy' => 'Overshark Girls + tu clóset', 'image' => 'm-look-3.jpg', 'q' => 'baby ty escote'],
    ];
    $perks = [
        ['icon' => 'drop', 'title' => 'No destiñe', 'copy' => 'Mantiene su color lavado tras lavado.'],
        ['icon' => 'shirt', 'title' => 'No encoge', 'copy' => 'Conserva su forma original.'],
        ['icon' => 'spark', 'title' => 'No hace bolitas', 'copy' => 'Mayor durabilidad en el uso diario.'],
        ['icon' => 'truck', 'title' => 'Envíos a todo el Perú', 'copy' => 'Lima: delivery o agencia. Provincia: agencia.'],
    ];
@endphp

@push('head')
    @include('web.partials.girls-head')
@endpush

@section('header-bar')
    @include('web.partials.girls-header')
@endsection

@section('content')
    <div class="gl-page">
        <section class="gl-hero" aria-labelledby="gl-hero-title">
            <div class="gl-hero__bg" aria-hidden="true">
                <div class="gl-rays"><i></i><i></i><i></i><i></i><i></i></div>
                <canvas class="gl-bubbles" data-gl-bubbles></canvas>
            </div>
            <div class="gl-wrap gl-hero__inner">
                <div class="gl-hero__copy">
                    <p class="gl-crumbs"><a href="{{ route('web.explore') }}">Inicio</a> / <b>Mujer</b></p>
                    <p class="gl-eyebrow"><span></span>Overshark Girls · Colección mujer</p>
                    <h1 id="gl-hero-title" class="gl-hero__title">
                        <span class="gl-t-light" data-gl-split>Mujer</span>
                        <span class="gl-t-serif gl-line-mask"><span class="gl-line-inner">libre como la marea</span></span>
                    </h1>
                    <p class="gl-hero__sub">Baby tees, sudaderas y pantalones de buzo suaves, para tu día a día.</p>
                    <div class="gl-hero__ctas">
                        <a class="gl-btn-primary" href="{{ $catalogUrl }}" data-gl-magnetic>Ver colección <svg class="gl-i" aria-hidden="true"><use href="#gl-i-arrow"/></svg></a>
                        <nav class="gl-quick" aria-label="Accesos por prenda">
                            <a href="{{ $searchUrl('baby ty') }}">Baby tees</a><span aria-hidden="true"></span>
                            <a href="{{ $searchUrl('top') }}">Tops</a><span aria-hidden="true"></span>
                            <a href="{{ $searchUrl('buzo') }}">Buzos</a>
                        </nav>
                    </div>
                    @if ($productCount > 0)
                        <p class="gl-count">{{ $productCount }} {{ $productCount === 1 ? 'producto' : 'productos' }}</p>
                    @endif
                </div>
                <div class="gl-hero__models">
                    <img src="{{ asset('images/explorar/mujer-modelos.jpg') }}" alt="Tres modelos con básicos Overshark Girls" width="900" height="900" fetchpriority="high">
                </div>
            </div>
        </section>

        <section class="gl-access" aria-label="Formas de comprar">
            <div class="gl-wrap gl-access__grid">
                @foreach ($access as $card)
                    <a @class(['gl-acc', 'gl-acc--primary' => $card['primary'] ?? false]) href="{{ $card['url'] }}" style="{{ $card['style'] }}" @if ($card['external'] ?? false) target="_blank" rel="noopener" @endif>
                        <div class="gl-acc__top">
                            <span class="gl-acc__icon" aria-hidden="true"><svg class="gl-i"><use href="#gl-i-{{ $card['icon'] }}"/></svg></span>
                            <span class="gl-badge">@if ($card['live'] ?? false)<i class="gl-dot" aria-hidden="true"></i>@endif{{ $card['badge'] }}</span>
                            <h2>{{ mb_strtoupper($card['title']) }}</h2>
                            <p>{{ $card['copy'] }}</p>
                        </div>
                        <span class="gl-acc__btn">{{ $card['cta'] }} <svg class="gl-i" aria-hidden="true"><use href="#gl-i-arrow"/></svg></span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="gl-sec" aria-labelledby="gl-cats">
            <div class="gl-wrap">
                <div class="gl-sec__head">
                    <div>
                        <p class="gl-sec__eyebrow">Categorías Overshark Girls</p>
                        <h2 id="gl-cats" class="gl-sec__title" data-gl-split-sec>Explora por <em>categoría</em></h2>
                    </div>
                </div>
                <div class="gl-grid gl-grid--3">
                    @foreach ($categories as $category)
                        <a class="gl-cat" href="{{ $searchUrl($category['q']) }}" data-gl-reveal-clip>
                            <img src="{{ asset('images/girls/'.$category['image']) }}" alt="" width="768" height="560" loading="lazy" decoding="async">
                            <span class="gl-cat__label">{{ mb_strtoupper($category['name']) }} <svg class="gl-i" aria-hidden="true"><use href="#gl-i-arrow"/></svg></span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($combos->isNotEmpty())
            <section class="gl-sec" aria-labelledby="gl-combos">
                <div class="gl-wrap">
                    <div class="gl-sec__head">
                        <div>
                            <p class="gl-sec__eyebrow">Combos Overshark Girls</p>
                            <h2 id="gl-combos" class="gl-sec__title" data-gl-split-sec>Combos más <em>pedidos</em></h2>
                        </div>
                        <button type="button" class="gl-more" data-combos-toggle aria-expanded="false" aria-controls="combos-menu">Ver todos los combos <svg class="gl-i" aria-hidden="true"><use href="#gl-i-arrow"/></svg></button>
                    </div>
                    <div class="gl-grid gl-grid--3">
                        @foreach ($combos as $combo)
                            <article class="gl-combo" data-gl-reveal>
                                <div class="gl-combo__img"><img src="{{ $combo->imageUrl() }}" alt="" loading="lazy" decoding="async"></div>
                                <div class="gl-combo__body">
                                    <div>
                                        <h3 class="gl-combo__num">{{ $combo->name }}</h3>
                                        <p class="gl-combo__what">{{ $combo->modality ?: 'Promoción' }} · {{ $combo->quantityLabel() }}</p>
                                        @if ($combo->price !== null)
                                            <p class="gl-combo__price">Por S/ {{ number_format((float) $combo->price, 2) }}</p>
                                        @endif
                                    </div>
                                    <a class="gl-btn-sm" href="{{ $combo->url ?: route('web.combos.show', $combo) }}">Armar combo <svg class="gl-i" aria-hidden="true"><use href="#gl-i-arrow"/></svg></a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($products->isNotEmpty())
            <section class="gl-sec" aria-labelledby="gl-best">
                <div class="gl-wrap">
                    <div class="gl-sec__head">
                        <div>
                            <p class="gl-sec__eyebrow">La colección</p>
                            <h2 id="gl-best" class="gl-sec__title" data-gl-split-sec>Lo más <em>vendido</em></h2>
                        </div>
                        <a class="gl-more" href="{{ $catalogUrl }}">Ver todo <svg class="gl-i" aria-hidden="true"><use href="#gl-i-arrow"/></svg></a>
                    </div>
                    <div class="gl-grid gl-grid--4">
                        @foreach ($products as $product)
                            @php $colors = collect($product->display_colors ?? [])->take(6); @endphp
                            <article class="gl-prod" data-gl-reveal>
                                <a href="{{ route('web.products.show', $product->id) }}" class="gl-prod__media">
                                    <img src="{{ $product->display_image }}" alt="{{ \Illuminate\Support\Str::title(mb_strtolower($product->name)) }}" loading="lazy" decoding="async" data-gl-prod-image>
                                </a>
                                <div class="gl-prod__info">
                                    <h3 class="gl-prod__name">{{ \Illuminate\Support\Str::title(mb_strtolower($product->name)) }}</h3>
                                    <p class="gl-prod__price"><b>S/ {{ number_format((float) $product->min_price, 2) }}</b></p>
                                    @if ($colors->isNotEmpty())
                                        <div class="gl-swatches" role="group" aria-label="Colores de {{ $product->name }}">
                                            @foreach ($colors as $color)
                                                <button type="button" style="--sw: {{ \App\Support\GarmentSwatch::hex($color['name']) }}" aria-label="Color {{ $color['name'] }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" data-gl-swatch data-image="{{ $color['image'] }}"></button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <a class="gl-add" href="{{ route('web.products.show', $product->id) }}"><svg class="gl-i" aria-hidden="true"><use href="#gl-i-bag"/></svg>Elegir talla</a>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="gl-sec" aria-labelledby="gl-looks">
            <div class="gl-wrap">
                <div class="gl-sec__head">
                    <div>
                        <p class="gl-sec__eyebrow">Inspírate</p>
                        <h2 id="gl-looks" class="gl-sec__title" data-gl-split-sec>Arma tu <em>look</em></h2>
                    </div>
                </div>
                <div class="gl-looks">
                    @foreach ($looks as $look)
                        <a @class(['gl-look', 'gl-look--lg' => $loop->first]) href="{{ $searchUrl($look['q']) }}" data-gl-reveal>
                            <div class="gl-look__img" data-gl-parallax><img src="{{ asset('images/girls/'.$look['image']) }}" alt="" loading="lazy" decoding="async"></div>
                            <div class="gl-look__txt">
                                <span class="gl-look__n">LOOK 0{{ $loop->iteration }}</span>
                                <h3>{{ mb_strtoupper($look['title']) }}</h3>
                                <p>{{ $look['copy'] }}</p>
                                <span class="gl-look__link">VER LOOK <svg class="gl-i" aria-hidden="true"><use href="#gl-i-arrow"/></svg></span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="nosotros" class="gl-sec" aria-labelledby="gl-about">
            <div class="gl-wrap">
                <div class="gl-sec__head">
                    <div>
                        <p class="gl-sec__eyebrow">Nosotros</p>
                        <h2 id="gl-about" class="gl-sec__title" data-gl-split-sec>Por qué <em>Overshark Girls</em></h2>
                    </div>
                    <a class="gl-more" href="#contacto">Contáctanos <svg class="gl-i" aria-hidden="true"><use href="#gl-i-arrow"/></svg></a>
                </div>
                <div class="gl-perks" data-gl-reveal>
                    @foreach ($perks as $perk)
                        <div class="gl-perk">
                            <svg class="gl-i" aria-hidden="true"><use href="#gl-i-{{ $perk['icon'] }}"/></svg>
                            <div><b>{{ $perk['title'] }}</b><span>{{ $perk['copy'] }}</span></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection

@section('footer')
    @include('web.partials.girls-footer')
@endsection
