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
    $payments = $paymentMethods->pluck('name')->filter()->values();
    $details = array_filter(['Material' => $producto->material, 'Fit' => $producto->fit, 'Sensación' => $producto->sensacion]);
    $about = array_filter(['Descripción' => $producto->descripcion, 'Composición' => $producto->composicion, 'Cuidados' => $producto->cuidados]);
    $brandLabel = match ($line) { 'bravos' => 'Bravos', 'girls' => 'Overshark Girls', default => 'Overshark' };
    $homeUrl = $line === 'bravos' ? route('web.brands.show', $brandPage) : route('web.home', ['linea' => $line === 'girls' ? 'mujer' : 'hombre']);
    $shopUrl = $line === 'bravos' ? route('web.products.search', ['marca' => $brandPage->name]) : route('web.products.search', ['linea' => $line === 'girls' ? 'mujer' : 'hombre']);
@endphp

@section('content')
    <div @class(['os-hh os-bpdp', 'os-hh--girls' => $line === 'girls', 'os-hh--bravos' => $line === 'bravos']) data-pdp data-pdp-stock='@json($stockByColor)'>
        <div class="os-bravos-wrap flex flex-col gap-8 py-10 lg:py-16">
            <nav aria-label="Ruta" class="text-[13px] leading-[1.45] text-[#65717c]">
                <a href="{{ $homeUrl }}" class="hover:text-[#0b1015]">Inicio</a>
                <span class="mx-1.5" aria-hidden="true">/</span>
                <a href="{{ $shopUrl }}" class="hover:text-[#0b1015]">Tienda</a>
                <span class="mx-1.5" aria-hidden="true">/</span>
                <span>{{ $title($producto->name) }}</span>
            </nav>

            @if (session('status'))
                <p class="bg-[#f5f7f9] px-4 py-3 text-[14px] font-bold text-[#0b1015]" role="status">{{ session('status') }}</p>
            @endif

            <div class="grid gap-10 lg:grid-cols-2 lg:gap-16">
                {{-- Galería --}}
                <div class="flex flex-col gap-4 self-start bg-[#f5f7f9] p-4 sm:p-6">
                    <div class="relative aspect-[576/540]">
                        <img data-product-card-image src="{{ $mainImages[0] }}" alt="{{ $title($producto->name) }}" class="h-full w-full object-contain" fetchpriority="high">
                        <button type="button" data-pdp-step="-1" class="os-bpdp-arrow left-0" aria-label="Foto anterior">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>
                        </button>
                        <button type="button" data-pdp-step="1" class="os-bpdp-arrow right-0" aria-label="Foto siguiente">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                        </button>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-[12px] font-bold uppercase leading-[1.45] text-[#65717c]">{{ \App\Support\ProductName::display($producto->name) }} / Color <span data-pdp-color-name>{{ $firstColor }}</span></p>
                        <div data-pdp-thumbs class="flex gap-2">
                            @foreach (array_slice($mainImages, 0, 5) as $image)
                                <button type="button" data-pdp-thumb data-image="{{ $image }}" @class(['os-pdp-dot', 'is-active' => $loop->first]) aria-label="Ver foto {{ $loop->iteration }}"></button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Información y compra --}}
                <div class="flex flex-col gap-5 text-[#0b1015]">
                    <p class="text-[12px] font-bold uppercase leading-[1.45] text-[var(--hh-accent)]">{{ $brandLabel }}</p>
                    <h1 class="text-[30px] font-extrabold uppercase leading-[1.45] sm:text-[38px]">{{ \App\Support\ProductName::display($producto->name) }}</h1>
                    <p class="text-[30px] font-extrabold leading-[1.45]">S/ {{ number_format($price, 2) }}</p>

                    <div class="flex flex-col gap-3">
                        <p class="text-[15px] font-bold leading-[1.45]">Color: <span data-pdp-color-name class="uppercase">{{ $firstColor ?? 'Elige un color' }}</span></p>
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
                        <p class="text-[15px] font-bold leading-[1.45]">Talla: <span data-pdp-size-name>{{ $firstSize }}</span></p>
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

                        <button type="submit" data-pdp-submit class="os-focus h-12 w-full bg-[var(--shop-deep)] text-[13px] font-bold uppercase text-white transition hover:bg-[var(--hh-accent)] disabled:cursor-not-allowed disabled:opacity-40">Agregar al carrito</button>
                        <button type="submit" name="checkout" value="1" data-pdp-submit class="os-focus h-12 w-full border border-[#dae0e5] bg-white text-[13px] font-bold uppercase text-[#0b1015] transition hover:border-[var(--shop-deep)] disabled:cursor-not-allowed disabled:opacity-40">Comprar ahora</button>
                    </form>

                    <div class="flex flex-col gap-2 leading-[1.45]">
                        <p class="text-[14px] font-bold">Envío a todo el Perú · S/ 14 costo único</p>
                        <p class="text-[13px] text-[#65717c]">Lima: delivery o agencia · Provincia: agencia</p>
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
                    <ul class="grid grid-cols-2 gap-x-4 gap-y-8 lg:grid-cols-3 lg:gap-8">
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
