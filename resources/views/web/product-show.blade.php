@extends('layouts.web')

{{--
    Ficha de producto (diseño Figma "PDP · Polera Neru · Desktop"): galería sobre fondo claro, color con
    muestras redondas, tallas, cantidad, compra, envío y pago, "Sobre esta prenda" y "Completa tu look".
    $line ('hombre' | 'girls' | 'bravos') elige la paleta (.os-hh--girls / .os-hh--bravos), la marca y las rutas.
    Lógica de color, talla y stock: resources/js/product-page.js (ganchos data-pdp-*).
--}}

@section('title', \App\Support\ProductName::title($producto->name).' | '.match ($line) { 'bravos' => 'Bravos', 'girls' => 'Overshark Girls', default => 'Overshark' })
@section('body-class', match ($line) { 'bravos' => 'theme-neon theme-bravos', 'girls' => 'theme-neon theme-girls', default => 'theme-neon' })

@php
    $title = fn ($text) => \App\Support\ProductName::title((string) $text);
    $sizeOrder = ['XS' => 0, 'S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4, 'XXL' => 5];
    $allSizes = $sizes->sortBy(fn ($size) => $sizeOrder[mb_strtoupper($size)] ?? 99)->values();
    // Stock real por color y talla: habilita solo las tallas que existen en el color elegido.
    $stockByColor = $variants->whereNotNull('color')->groupBy('color')
        ->map(fn ($items) => $items->groupBy('talla')->map(fn ($rows) => (int) $rows->sum('stock')));
    $firstColor = $colorOptions->first()['name'] ?? null;
    $firstSize = $allSizes->first(fn ($size) => ($stockByColor[$firstColor][$size] ?? 0) > 0) ?? $allSizes->first();
    // Precio de la variante elegida al abrir la ficha (cambia con el color y la talla: resources/js/product-page.js).
    $shown = $priceMap[$firstColor][$firstSize] ?? ['regular' => $price, 'now' => $price];
    $onSale = $shown['now'] < $shown['regular'];
    $payments = $paymentMethods->pluck('name')->filter()->values();
    $details = array_filter(['Material' => $producto->material, 'Fit' => $producto->fit, 'Sensación' => $producto->sensacion]);
    $about = array_filter(['Descripción' => $producto->descripcion, 'Composición' => $producto->composicion, 'Cuidados' => $producto->cuidados]);
    $brandLabel = match ($line) { 'bravos' => 'Bravos', 'girls' => 'Overshark Girls', default => 'Overshark' };
    $homeUrl = $line === 'bravos' ? route('web.brands.show', $brandPage) : route('web.home', ['linea' => $line === 'girls' ? 'mujer' : 'hombre']);
    $shopUrl = $line === 'bravos' ? route('web.products.search', ['marca' => $brandPage->name]) : route('web.products.search', ['linea' => $line === 'girls' ? 'mujer' : 'hombre']);
    $categorySlug = \App\Support\StorefrontCategories::slugFor($producto->name);
    $combosUrl = route('web.combos.index', match ($line) { 'bravos' => ['marca' => 'Bravos'], 'girls' => ['marca' => 'Girls'], default => [] });
@endphp

@section('content')
    <div @class(['os-hh os-bpdp', 'os-hh--girls' => $line === 'girls', 'os-hh--bravos' => $line === 'bravos']) data-pdp data-pdp-stock='@json($stockByColor)' data-pdp-prices='@json($priceMap)'>
        <div class="os-bravos-wrap flex flex-col gap-5 py-9 max-md:!px-6 md:gap-8 md:py-10 lg:py-16">
            {{-- Celular (Figma "Producto / … / Mobile"): «← Volver a la tienda» en lugar de la ruta. --}}
            <a href="{{ $shopUrl }}" class="os-focus self-start text-[13px] font-extrabold leading-[1.35] text-[#0d0f14] md:hidden">← Volver a la tienda</a>
            <nav aria-label="Ruta" class="hidden text-[13px] leading-[1.45] text-[#65717c] md:block">
                <a href="{{ $homeUrl }}" class="hover:text-[#0b1015]">Inicio</a>
                <span class="mx-1.5" aria-hidden="true">/</span>
                <a href="{{ $shopUrl }}" class="hover:text-[#0b1015]">Tienda</a>
                <span class="mx-1.5" aria-hidden="true">/</span>
                <span>{{ $title($producto->name) }}</span>
            </nav>

            @if (session('status'))
                <p class="bg-[#f5f7f9] px-4 py-3 text-[14px] font-bold text-[#0b1015]" role="status">{{ session('status') }}</p>
            @endif
            {{-- Si el carrito lateral se abre, el aviso se muestra ahí y no se repite aquí. --}}
            @if ($errors->any() && ! session('cart_open'))
                <p class="bg-[#fdecec] px-4 py-3 text-[14px] font-bold text-[#a3261c]" role="alert">{{ $errors->first() }}</p>
            @endif

            <div class="grid gap-5 md:gap-10 lg:grid-cols-2 lg:gap-16">
                {{-- Galería --}}
                <div class="flex flex-col gap-3 self-start md:gap-4 md:bg-[#f5f7f9] md:p-6">
                    <div class="relative aspect-[342/420] bg-[#e8e8e8] md:aspect-[576/540] md:bg-transparent">
                        <img data-product-card-image src="{{ $mainImages[0] }}" alt="{{ $title($producto->name) }}" class="h-full w-full object-cover md:object-contain" fetchpriority="high">
                        <button type="button" data-pdp-step="-1" class="os-bpdp-arrow left-0" aria-label="Foto anterior">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>
                        </button>
                        <button type="button" data-pdp-step="1" class="os-bpdp-arrow right-0" aria-label="Foto siguiente">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                        </button>
                    </div>
                    <div class="flex items-center justify-center gap-4 md:justify-between">
                        <p class="hidden text-[12px] font-bold uppercase leading-[1.45] text-[#65717c] md:block">{{ \App\Support\ProductName::display($producto->name) }} / Color <span data-pdp-color-name>{{ $firstColor }}</span></p>
                        <div data-pdp-thumbs class="flex gap-2">
                            @foreach (array_slice($mainImages, 0, 5) as $image)
                                <button type="button" data-pdp-thumb data-image="{{ $image }}" @class(['os-pdp-dot', 'is-active' => $loop->first]) aria-label="Ver foto {{ $loop->iteration }}"></button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Información y compra --}}
                <div class="flex flex-col gap-5 text-[#0b1015] max-md:font-['Manrope'] max-md:text-[#0d0f14]">
                    @if ($categorySlug)
                        <p class="text-[11px] font-extrabold uppercase leading-[1.35] md:hidden">{{ \App\Support\StorefrontCategories::label($categorySlug) }}</p>
                    @endif
                    <p class="hidden text-[12px] font-bold uppercase leading-[1.45] text-[var(--hh-accent)] md:block">{{ $brandLabel }}</p>
                    <h1 class="text-[27px] font-extrabold leading-[1.35] md:text-[38px] md:uppercase md:leading-[1.45]">
                        <span class="md:hidden">{{ $title($producto->name) }}@if ($firstColor) · <span data-pdp-color-name class="os-m-cap">{{ $firstColor }}</span>@endif</span>
                        <span class="hidden md:inline">{{ \App\Support\ProductName::display($producto->name) }}</span>
                    </h1>
                    <div class="flex flex-col gap-1">
                        <p class="flex flex-wrap items-baseline gap-3 leading-[1.45]">
                            <span data-pdp-price-now @class(['text-[23px] font-extrabold md:text-[30px]', 'text-[#b42318]' => $onSale])>S/ {{ number_format($shown['now'], 2) }}</span>
                            <span data-pdp-price-regular @class(['text-[14px] text-[#65717c] line-through md:text-[18px]', 'hidden' => ! $onSale])>S/ {{ number_format($shown['regular'], 2) }}</span>
                            <span data-pdp-price-badge data-label="{{ $promo['label'] ?? '' }}" @class(['bg-[#b42318] px-2 py-1 text-[13px] font-extrabold leading-none text-white', 'hidden' => ! $onSale])>{{ ($promo['label'] ?? '') ?: '-'.round((1 - $shown['now'] / max($shown['regular'], 0.01)) * 100).'%' }}</span>
                        </p>
                        @if ($promo)
                            @include('web.partials.promo-countdown', ['promo' => $promo, 'prefix' => 'Oferta termina en ', 'class' => 'text-[13px] font-extrabold uppercase tracking-[0.5px] text-[#b42318]'])
                        @endif
                    </div>

                    <div class="flex flex-col gap-3">
                        <p class="text-[14px] leading-[1.35] md:text-[15px] md:font-bold md:leading-[1.45]"><span class="md:hidden">Color seleccionado:</span><span class="hidden md:inline">Color:</span> <span data-pdp-color-name class="os-m-cap md:uppercase">{{ $firstColor ?? 'Elige un color' }}</span></p>
                        <div class="flex flex-wrap gap-2.5" role="radiogroup" aria-label="Color">
                            @foreach ($colorOptions as $color)
                                <button type="button" role="radio" aria-checked="{{ $loop->first ? 'true' : 'false' }}"
                                    data-pdp-color="{{ $color['name'] }}" data-images='@json($color['images'])'
                                    class="os-bpdp-swatch os-focus" style="--sw: {{ \App\Support\GarmentSwatch::hex($color['name']) }}"
                                    aria-label="Color {{ $title($color['name']) }}" title="{{ $title($color['name']) }}"></button>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <p class="text-[16px] font-extrabold leading-[1.35] md:text-[15px] md:font-bold md:leading-[1.45]"><span class="md:hidden">Selecciona tu talla</span><span class="hidden md:inline">Talla: <span data-pdp-size-name>{{ $firstSize }}</span></span></p>
                        <div class="flex flex-wrap gap-3" role="radiogroup" aria-label="Talla">
                            @foreach ($allSizes as $size)
                                <button type="button" role="radio" aria-checked="{{ $size === $firstSize ? 'true' : 'false' }}" data-pdp-size="{{ $size }}" class="os-bpdp-size os-focus">{{ $size }}</button>
                            @endforeach
                        </div>
                        <p data-pdp-stock-note class="hidden text-[13px] font-bold text-[#b45309]"></p>
                    </div>

                    @if ($producto->sizeGuideImageUrl())
                        <button type="button" data-size-guide-open class="os-focus self-start text-[13px] font-bold leading-[1.45] text-[var(--hh-accent)] hover:underline">Guía de tallas</button>
                    @endif

                    <form action="{{ route('web.cart.store') }}" method="POST" data-cart-form class="flex flex-col gap-4">
                        @csrf
                        <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                        <input type="hidden" name="color" value="{{ $firstColor }}" data-pdp-input-color>
                        <input type="hidden" name="talla" value="{{ $firstSize }}" data-pdp-input-size>
                        <input type="hidden" name="qty" value="1" data-pdp-input-qty>

                        <div class="flex items-center gap-2 text-[15px] font-bold leading-[1.45]">
                            <span class="mr-2">Cantidad:</span>
                            <button type="button" data-pdp-qty="-1" class="os-focus grid h-9 w-9 place-items-center text-[18px] hover:bg-[#f5f7f9]" aria-label="Quitar una unidad">−</button>
                            <span data-pdp-qty-value class="w-6 text-center" aria-live="polite">1</span>
                            <button type="button" data-pdp-qty="1" class="os-focus grid h-9 w-9 place-items-center text-[18px] hover:bg-[#f5f7f9]" aria-label="Agregar una unidad">+</button>
                        </div>

                        <button type="submit" data-pdp-submit class="os-focus h-12 w-full bg-[#071226] text-[14px] font-extrabold uppercase text-white md:bg-[var(--shop-deep)] md:text-[13px] md:font-bold transition hover:bg-[var(--hh-accent)] disabled:cursor-not-allowed disabled:opacity-40">Agregar al carrito</button>
                        <button type="submit" name="checkout" value="1" data-pdp-submit class="os-focus h-12 w-full border border-[#dae0e5] bg-white text-[13px] font-bold uppercase text-[#0b1015] transition hover:border-[var(--shop-deep)] disabled:cursor-not-allowed disabled:opacity-40">Comprar ahora</button>
                    </form>

                    {{-- Celular: envío en una línea y «ARMA UN COMBO». --}}
                    <p class="text-[14px] leading-[1.35] md:hidden">{{ \App\Support\SiteContent::t('Envío desde {desde} a todo el Perú. Impuestos incluidos.') }}</p>
                    <a href="{{ $combosUrl }}" class="os-m-btn os-m-btn--dark os-focus md:hidden">Arma un combo</a>
                    <div class="hidden flex-col gap-2 leading-[1.45] md:flex">
                        <p class="text-[14px] font-bold">{{ \App\Support\SiteContent::t('Envío a todo el Perú desde {desde}') }}</p>
                        <p class="text-[13px] text-[#65717c]">{{ \App\Support\SiteContent::t('Lima: delivery {lima} · Agencia Shalom {agencia} · Almacén gratis') }}</p>
                        <p class="text-[14px] font-bold">Pago con {{ $payments->isNotEmpty() ? $payments->join(', ', ' y ') : 'Yape y Plin' }} · También contraentrega</p>
                    </div>
                </div>
            </div>

            {{-- Sobre esta prenda --}}
            <section class="flex flex-col gap-4 pt-4" aria-labelledby="bpdp-about">
                <h2 id="bpdp-about" class="text-[22px] font-extrabold leading-[1.45] text-[#0b1015]">SOBRE ESTA PRENDA</h2>
                @if ($about === [] && $details === [])
                    <p class="text-[15px] leading-[1.45] text-[#65717c]">Muy pronto agregaremos la descripción, la composición y los cuidados de esta prenda.</p>
                @else
                    <dl class="grid gap-4 text-[15px] leading-[1.45] sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($about as $label => $text)
                            <div>
                                <dt class="font-bold text-[#0b1015]">{{ $label }}</dt>
                                <dd class="mt-1 text-[#65717c]">{!! nl2br(e($text)) !!}</dd>
                            </div>
                        @endforeach
                        @foreach ($details as $label => $text)
                            <div>
                                <dt class="font-bold text-[#0b1015]">{{ $label }}</dt>
                                <dd class="mt-1 text-[#65717c]">{{ $text }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>

            {{-- Completa tu look --}}
            @if ($recommendedProducts->isNotEmpty())
                <section class="flex flex-col gap-8 pt-4" aria-labelledby="bpdp-look">
                    <h2 id="bpdp-look" class="text-[26px] font-extrabold leading-[1.45] text-[#0b1015]">COMPLETA TU LOOK</h2>
                    <ul class="os-m-grid md:hidden">
                        @foreach ($recommendedProducts as $product)
                            <li>@include('web.partials.m-product-card')</li>
                        @endforeach
                    </ul>
                    <ul class="hidden grid-cols-2 gap-x-4 gap-y-8 md:grid lg:grid-cols-3 lg:gap-8">
                        @foreach ($recommendedProducts as $product)
                            <li>@include('web.partials.hh-product-card', ['photoClass' => 'aspect-[416/314]'])</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>

    @if ($producto->sizeGuideImageUrl())
        <div data-size-guide-modal class="fixed inset-0 z-50 hidden bg-black/85 px-4 py-6 backdrop-blur-sm sm:px-8" role="dialog" aria-modal="true" aria-label="Guía de tallas">
            <button type="button" data-size-guide-close class="absolute right-5 top-5 grid h-11 w-11 place-items-center rounded-full bg-white text-[#0b1015] shadow-lg" aria-label="Cerrar guía de tallas">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
            <div class="flex h-full w-full items-center justify-center">
                <img src="{{ $producto->sizeGuideImageUrl() }}" alt="Guía de tallas {{ $title($producto->name) }}" class="max-h-full max-w-full bg-white object-contain shadow-2xl">
            </div>
        </div>
    @endif
@endsection
