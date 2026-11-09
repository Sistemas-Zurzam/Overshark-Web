@extends('layouts.web')

@section('title', 'Tienda | Overshark')

{{--
    Tienda (diseños Figma "02 · Tienda Hombre" y "02 · Tienda Mujer"): cabecera con categorías rápidas,
    filtros laterales (categoría, talla, color y precio), resultados paginados y franja de confianza.
    Los filtros son enlaces GET: cada uno cambia un parámetro y conserva los demás. En la línea mujer
    (.os-hh--girls) cambian la paleta, las categorías y los títulos (Josefin Sans).
--}}
@php
    $girls = session('storefront.line') === 'mujer' && $marca === '';
    $title = fn ($text) => \App\Support\ProductName::title((string) $text);
    $url = fn (array $changes = []) => route('web.products.search', array_filter([...$filters, ...$changes], fn ($value) => $value !== null && $value !== ''));
    $categories = \App\Support\StorefrontCategories::all();
    $talla = $filters['talla'] ?? '';
    $color = $filters['color'] ?? '';
    $min = $filters['min'] ?? $priceFloor;
    $max = $filters['max'] ?? $priceCeil;

    $heading = match (true) {
        $categoria !== '' => \App\Support\StorefrontCategories::label($categoria),
        $search !== '' => 'Resultados',
        $orden === 'novedades' => 'Novedades',
        $marca !== '' => $marca,
        default => 'Tienda',
    };
    $intro = match (true) {
        $search !== '' => 'Resultados para "'.$search.'".',
        $marca !== '' => 'Productos de la marca '.$marca.'.',
        $orden === 'novedades' && $categoria === '' => 'Los últimos modelos incorporados al catálogo.',
        $girls => \App\Support\SiteContent::t('Baby tees, tops, poleras y joggers para todos los días. Mézclalos en combos {combo}.'),
        default => \App\Support\SiteContent::t('Polos, camisas, poleras y joggers para todos los días. Mézclalos en combos {combo}.'),
    };

    // Filtros activos, cada uno con el enlace que lo quita.
    $active = collect([
        $search !== '' ? ['label' => '"'.$search.'"', 'url' => $url(['q' => null])] : null,
        $marca !== '' ? ['label' => $marca, 'url' => $url(['marca' => null])] : null,
        $categoria !== '' ? ['label' => \App\Support\StorefrontCategories::label($categoria), 'url' => $url(['categoria' => null])] : null,
        $talla !== '' ? ['label' => 'Talla '.$talla, 'url' => $url(['talla' => null])] : null,
        $color !== '' ? ['label' => $title($color), 'url' => $url(['color' => null])] : null,
        isset($filters['min']) || isset($filters['max']) ? ['label' => 'S/ '.$min.' — S/ '.$max, 'url' => $url(['min' => null, 'max' => null])] : null,
    ])->filter();

    $chips = $girls ? ['baby-tees', 'tops', 'joggers'] : ['polos', 'camisas', 'poleras', 'pantalones'];
    // Títulos: Big Shoulders en mayúsculas (hombre) o Josefin Sans (Overshark Girls).
    $display = $girls ? 'os-josefin' : 'os-hh-display';
    $sorts = ['' => 'Más vendidos', 'novedades' => 'Novedades', 'precio-asc' => 'Precio: menor a mayor', 'precio-desc' => 'Precio: mayor a menor'];
    $perks = [
        ['title' => \App\Support\SiteContent::t('Envío desde {desde} a todo el Perú'), 'text' => 'Delivery en Lima o agencia Shalom'],
        ['title' => 'Pago contraentrega', 'text' => 'Yape, Plin o efectivo'],
        ['title' => 'Cambios y devoluciones', 'text' => 'Te ayudamos por WhatsApp'],
        ['title' => \App\Support\SiteContent::t('Combos {combo}'), 'text' => 'Mezcla tus favoritos'],
    ];
@endphp

@section('content')
    <div @class(['os-hh', 'os-hh--girls' => $girls])>
        {{-- Cabecera de página --}}
        <header class="pb-10 pt-10 text-white [background:var(--shop-head)] lg:pt-12">
            <div class="os-hh-wrap flex flex-col items-start gap-3.5">
                <nav aria-label="Ruta" class="text-[13px] font-semibold text-[var(--shop-crumb)]">
                    <a href="{{ route('web.home') }}" class="hover:text-white">Inicio</a>
                    <span class="mx-1.5" aria-hidden="true">›</span>
                    <a href="{{ route('web.products.search') }}" class="hover:text-white">Tienda</a>
                    @if ($girls)
                        <span class="mx-1.5" aria-hidden="true">›</span>
                        <a href="{{ route('web.products.search') }}" class="hover:text-white">Mujer</a>
                    @endif
                    @if ($heading !== 'Tienda')
                        <span class="mx-1.5" aria-hidden="true">›</span>
                        <span class="text-white">{{ $heading }}</span>
                    @endif
                </nav>
                <div class="flex flex-wrap items-baseline gap-x-5 gap-y-1">
                    <h1 @class([$display, 'text-[64px] leading-[0.95]', 'sm:text-[96px] sm:leading-[90px]' => ! $girls, 'sm:text-[84px] sm:leading-[91px]' => $girls])>{{ $heading }}</h1>
                    <p class="text-[16px] font-bold text-[var(--hh-bright)]">{{ $products->total() }} {{ $products->total() === 1 ? 'producto' : 'productos' }}</p>
                </div>
                <p class="text-[16px] font-medium text-[var(--shop-intro)]">{{ $intro }}</p>
                <ul class="-mx-4 flex max-w-[calc(100%+2rem)] gap-2.5 overflow-x-auto px-4 pt-3.5 [scrollbar-width:none] sm:mx-0 sm:max-w-full sm:flex-wrap sm:px-0">
                    <li><a href="{{ $url(['categoria' => null]) }}" @class(['os-shop-chip os-focus', 'is-active' => $categoria === '']) @if ($categoria === '') aria-current="page" @endif>Todos</a></li>
                    @foreach ($chips as $slug)
                        <li><a href="{{ $url(['categoria' => $slug]) }}" @class(['os-shop-chip os-focus', 'is-active' => $categoria === $slug]) @if ($categoria === $slug) aria-current="page" @endif>{{ $categories[$slug]['label'] }}</a></li>
                    @endforeach
                    <li><a href="{{ route('web.combos.index', $girls ? ['marca' => 'Girls'] : []) }}" class="os-shop-chip os-focus">Combos</a></li>
                </ul>
            </div>
        </header>

        {{-- Contenido: filtros + resultados --}}
        <div class="os-hh-wrap grid gap-8 pb-16 pt-8 lg:grid-cols-[264px_minmax(0,1fr)] lg:gap-10 lg:pb-[88px] lg:pt-12">
            <button type="button" class="os-focus flex h-11 items-center justify-center gap-2 border border-[#0b0f14] text-[14px] font-extrabold uppercase tracking-[1px] lg:hidden" data-shop-filters-toggle aria-controls="shop-filters" aria-expanded="false">
                Filtros @if ($active->isNotEmpty()) ({{ $active->count() }}) @endif
            </button>

            <aside id="shop-filters" class="hidden flex-col gap-7 lg:flex" aria-label="Filtros">
                <div class="flex items-center justify-between border-b-2 border-[#0b0f14] pb-3.5">
                    <h2 @class([$display, 'uppercase leading-none', 'text-[28px]' => ! $girls, 'text-[19px] !font-bold' => $girls])>Filtros</h2>
                    <a href="{{ route('web.products.search') }}" class="os-focus text-[13px] font-bold text-[var(--hh-accent)] hover:underline">Limpiar</a>
                </div>

                <section class="flex flex-col gap-3" aria-labelledby="f-cat">
                    <h3 id="f-cat" class="os-shop-label">Categoría</h3>
                    <ul class="flex flex-col gap-3">
                        @foreach ($categories as $slug => $category)
                            <li>
                                <a href="{{ $url(['categoria' => $categoria === $slug ? null : $slug]) }}" class="os-focus group flex items-center gap-2.5" role="checkbox" aria-checked="{{ $categoria === $slug ? 'true' : 'false' }}">
                                    <span @class(['os-shop-check', 'is-checked' => $categoria === $slug]) aria-hidden="true"></span>
                                    <span @class(['flex-1 text-[14px] group-hover:text-[var(--hh-accent)]', 'font-bold' => $categoria === $slug, 'font-medium' => $categoria !== $slug])>{{ $category['label'] }}</span>
                                    <span class="text-[13px] font-medium text-[#888]">{{ $categoryCounts[$slug] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>

                @if ($sizes->isNotEmpty())
                    <section class="flex flex-col gap-3" aria-labelledby="f-size">
                        <h3 id="f-size" class="os-shop-label">Talla</h3>
                        <ul class="flex flex-wrap gap-2">
                            @foreach ($sizes as $size)
                                <li><a href="{{ $url(['talla' => $talla === $size ? null : $size]) }}" @class(['os-shop-size os-focus', 'is-active' => $talla === $size]) aria-pressed="{{ $talla === $size ? 'true' : 'false' }}">{{ $size }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($colors->isNotEmpty())
                    <section class="flex flex-col gap-3" aria-labelledby="f-color">
                        <h3 id="f-color" class="os-shop-label">Color</h3>
                        <ul class="flex flex-wrap gap-2.5">
                            @foreach ($colors as $option)
                                <li>
                                    <a href="{{ $url(['color' => $color === $option ? null : $option]) }}" @class(['os-shop-swatch os-focus', 'is-active' => $color === $option]) style="--swatch: {{ \App\Support\GarmentSwatch::hex($option) }}" title="{{ $title($option) }}" aria-label="Color {{ $title($option) }}" aria-pressed="{{ $color === $option ? 'true' : 'false' }}"></a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($priceCeil > $priceFloor)
                    <form action="{{ route('web.products.search') }}" method="GET" class="flex flex-col gap-3" aria-labelledby="f-price" data-shop-price>
                        <h3 id="f-price" class="os-shop-label">Precio</h3>
                        @foreach (\Illuminate\Support\Arr::except($filters, ['min', 'max']) as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach
                        <div class="os-shop-range" style="--low: {{ round(($min - $priceFloor) / ($priceCeil - $priceFloor), 4) }}; --high: {{ round(($max - $priceFloor) / ($priceCeil - $priceFloor), 4) }}">
                            <input type="range" name="min" min="{{ $priceFloor }}" max="{{ $priceCeil }}" step="1" value="{{ $min }}" aria-label="Precio mínimo" data-shop-price-min>
                            <input type="range" name="max" min="{{ $priceFloor }}" max="{{ $priceCeil }}" step="1" value="{{ $max }}" aria-label="Precio máximo" data-shop-price-max>
                        </div>
                        <p class="text-[14px] font-bold" data-shop-price-label>S/ {{ $min }} — S/ {{ $max }}</p>
                        <button type="submit" class="sr-only">Aplicar precio</button>
                    </form>
                @endif

                <a href="{{ route('web.combos.index', $girls ? ['marca' => 'Girls'] : []) }}" class="os-focus group flex flex-col items-start gap-2.5 bg-[var(--shop-deep)] px-5 py-[22px]">
                    <span class="text-[12px] font-extrabold uppercase tracking-[2px] text-[var(--hh-bright)]">Combo</span>
                    <span @class([$display, 'text-white', 'text-[52px] leading-[50px]' => ! $girls, 'text-[36px] leading-[39px]' => $girls])>{{ \App\Support\SiteContent::t('{combo}') }}</span>
                    <span class="max-w-[220px] text-[13px] font-medium leading-[19px] text-[var(--shop-deep-muted)]">Mezcla cualquier básico de la tienda.</span>
                    <span class="text-[13px] font-extrabold uppercase text-[var(--hh-bright)] group-hover:underline">Armar combo →</span>
                </a>
            </aside>

            <section aria-label="Resultados" class="min-w-0">
                <div class="flex flex-col gap-3 border-b-2 border-[#0b0f14] pb-3.5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-[14px] font-bold">
                            @if ($products->total() > 0)
                                Mostrando {{ $products->firstItem() }}–{{ $products->lastItem() }} de {{ $products->total() }}
                            @else
                                Sin resultados
                            @endif
                        </p>
                        @foreach ($active as $filter)
                            <a href="{{ $filter['url'] }}" class="os-focus bg-[var(--hh-soft)] px-3 py-1.5 text-[12px] font-bold text-[var(--hh-accent)] hover:brightness-95" aria-label="Quitar filtro {{ $filter['label'] }}">{{ $filter['label'] }}&nbsp; ✕</a>
                        @endforeach
                    </div>
                    <form action="{{ route('web.products.search') }}" method="GET">
                        @foreach (\Illuminate\Support\Arr::except($filters, ['orden']) as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach
                        <label class="flex h-10 items-center gap-1 border border-[var(--shop-line)] pl-3.5 text-[13px] font-bold">
                            <span class="shrink-0">Ordenar por:</span>
                            <select name="orden" class="os-shop-sort os-focus" data-auto-submit style="background-image: url('{{ asset('images/home-hombre/chevron-abajo.svg') }}')">
                                @foreach ($sorts as $value => $label)
                                    <option value="{{ $value }}" @selected($orden === $value || ($value === '' && $orden === 'populares'))>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <noscript><button type="submit" class="mt-2 text-[13px] font-bold text-[var(--hh-accent)]">Ordenar</button></noscript>
                    </form>
                </div>

                @if ($products->isEmpty())
                    <div class="mt-6 grid min-h-64 place-items-center border border-dashed border-[#d9d9d9] px-5 text-center">
                        <div>
                            <p class="text-[18px] font-extrabold">No encontramos productos con esos filtros</p>
                            <p class="mt-1 text-[14px] font-medium text-[#545966]">Quita algún filtro o mira toda la tienda.</p>
                            <a href="{{ route('web.products.search') }}" class="os-hh-btn os-focus mt-5 bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)]">Ver toda la tienda</a>
                        </div>
                    </div>
                @else
                    <ul class="mt-6 grid grid-cols-2 gap-x-4 gap-y-6 sm:grid-cols-3 xl:grid-cols-4">
                        @foreach ($products as $product)
                            <li>@include('web.partials.hh-product-card', ['photoClass' => 'aspect-[222/314]'])</li>
                        @endforeach
                    </ul>

                    @if ($products->hasPages())
                        <nav class="flex justify-center gap-2 pt-12" aria-label="Paginación">
                            @if ($products->onFirstPage())
                                <span class="os-shop-page is-disabled" aria-hidden="true">‹</span>
                            @else
                                <a href="{{ $products->previousPageUrl() }}" class="os-shop-page os-focus" rel="prev" aria-label="Página anterior">‹</a>
                            @endif
                            @foreach (range(1, $products->lastPage()) as $page)
                                <a href="{{ $products->url($page) }}" @class(['os-shop-page os-focus', 'is-active' => $page === $products->currentPage()]) @if ($page === $products->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                            @endforeach
                            @if ($products->hasMorePages())
                                <a href="{{ $products->nextPageUrl() }}" class="os-shop-page os-focus" rel="next" aria-label="Página siguiente">›</a>
                            @else
                                <span class="os-shop-page is-disabled" aria-hidden="true">›</span>
                            @endif
                        </nav>
                    @endif
                @endif
            </section>
        </div>

        @if ($marca === '')
            {{-- Banner · Promo Wafflera (diseños Tienda Hombre y Tienda Mujer; no en la vista de una marca) --}}
            <section class="os-hh-wrap pb-16 lg:pb-[88px]" aria-labelledby="hh-promo">
                @include('web.partials.promo-wafflera')
            </section>
        @endif

        {{-- Franja de confianza --}}
        <section class="bg-[var(--shop-deep)] py-7 text-white" aria-label="Beneficios de comprar en Overshark">
            <ul class="os-hh-wrap grid grid-cols-2 gap-6 lg:flex lg:justify-between">
                @foreach ($perks as $perk)
                    <li class="flex flex-col gap-1">
                        <span class="text-[15px] font-extrabold">{{ $perk['title'] }}</span>
                        <span class="text-[13px] font-medium text-[var(--shop-deep-muted)]">{{ $perk['text'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
@endsection
