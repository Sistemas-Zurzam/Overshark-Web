@extends('layouts.web')

{{-- Catálogo de la colección mujer con el diseño de Overshark Girls (mismo header, footer y estilos que la home mujer). --}}

@section('title', 'Productos | Overshark Girls')
@section('body-class', 'gl')
@section('header-class', 'gl-hdr fixed inset-x-0 top-0 z-50')
@section('main-class', '')

@php
    $filters = [
        ['label' => 'Todo', 'q' => ''],
        ['label' => 'Baby tees', 'q' => 'baby ty'],
        ['label' => 'Tops', 'q' => 'top'],
        ['label' => 'Buzos', 'q' => 'buzo'],
    ];
    $activeFilter = collect($filters)->firstWhere('q', mb_strtolower($search));
    $heading = $search === '' ? 'Toda la colección' : ($activeFilter['label'] ?? 'Resultados para "'.$search.'"');
@endphp

@push('head')
    @include('web.partials.girls-head')
@endpush

@section('header-bar')
    @include('web.partials.girls-header')
@endsection

@section('content')
    <div class="gl-page">
        <section class="gl-listing-head" aria-labelledby="gl-list-title">
            <div class="gl-hero__bg" aria-hidden="true">
                <canvas class="gl-bubbles" data-gl-bubbles></canvas>
            </div>
            <div class="gl-wrap">
                <p class="gl-crumbs"><a href="{{ route('web.home', ['linea' => 'mujer']) }}">Mujer</a> / <b>Productos</b></p>
                <p class="gl-eyebrow"><span></span>Overshark Girls · Productos</p>
                <h1 id="gl-list-title" class="gl-sec__title">{{ $heading }}</h1>

                <div class="gl-listing-tools">
                    <nav class="gl-chips" aria-label="Filtrar por prenda">
                        @foreach ($filters as $filter)
                            <a href="{{ route('web.girls.products', array_filter(['q' => $filter['q']])) }}" @class(['gl-chip', 'is-active' => mb_strtolower($search) === $filter['q']]) @if (mb_strtolower($search) === $filter['q']) aria-current="page" @endif>{{ $filter['label'] }}</a>
                        @endforeach
                    </nav>
                    <form class="gl-search" action="{{ route('web.girls.products') }}" method="GET" role="search">
                        <label for="gl-search-q" class="sr-only">Buscar en Overshark Girls</label>
                        <input id="gl-search-q" type="search" name="q" value="{{ $search }}" placeholder="Buscar baby tees, tops, buzos…">
                        <button type="submit" aria-label="Buscar"><svg class="gl-i" aria-hidden="true"><use href="#gl-i-search"/></svg></button>
                    </form>
                </div>
            </div>
        </section>

        <section class="gl-sec gl-sec--tight" aria-label="Productos">
            <div class="gl-wrap">
                @if ($products->isNotEmpty())
                    <p class="gl-count gl-listing-count">{{ $products->count() }} {{ $products->count() === 1 ? 'modelo' : 'modelos' }}</p>
                    <div class="gl-grid gl-grid--4">
                        @foreach ($products as $product)
                            @php $colors = collect($product->display_colors ?? [])->take(6); @endphp
                            <article class="gl-prod" data-gl-reveal>
                                <a href="{{ route('web.products.show', $product->id) }}" class="gl-prod__media">
                                    <img src="{{ $product->display_image }}" alt="{{ \Illuminate\Support\Str::title(mb_strtolower($product->name)) }}" loading="lazy" decoding="async" data-gl-prod-image>
                                </a>
                                <div class="gl-prod__info">
                                    <h2 class="gl-prod__name">{{ \Illuminate\Support\Str::title(mb_strtolower($product->name)) }}</h2>
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
                @else
                    <div class="gl-empty">
                        <svg class="gl-i" aria-hidden="true"><use href="#gl-i-bag"/></svg>
                        <h2>{{ $search === '' ? 'Pronto publicaremos la colección mujer' : 'No encontramos productos para "'.$search.'"' }}</h2>
                        <p>{{ $search === '' ? 'Estamos preparando los productos de Overshark Girls. Mientras tanto, mira las categorías o arma tu look.' : 'Prueba con otra prenda o mira toda la colección.' }}</p>
                        <div class="gl-empty__ctas">
                            <a class="gl-btn-sm" href="{{ route('web.home', ['linea' => 'mujer']) }}">Volver a mujer <svg class="gl-i" aria-hidden="true"><use href="#gl-i-arrow"/></svg></a>
                            @if ($search !== '')
                                <a class="gl-more" href="{{ route('web.girls.products') }}">Ver toda la colección <svg class="gl-i" aria-hidden="true"><use href="#gl-i-arrow"/></svg></a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection

@section('footer')
    @include('web.partials.girls-footer')
@endsection
