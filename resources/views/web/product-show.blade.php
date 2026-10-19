@extends('layouts.web')

@section('title', $producto->name.' | Overshark')

@section('content')
    @php
        $oldPrice = $price > 0 ? $price / 0.8 : 0;
        $swatches = [
            'azul' => '#1d4f91',
            'beige' => '#ddcdbd',
            'perla' => '#e8e0d6',
            'cemento' => '#5f5f5c',
            'negro' => '#111111',
            'vino' => '#7b1028',
            'botella' => '#0f4f3b',
            'plomo' => '#9a9aa0',
            'pacay' => '#8c9b73',
            'denim' => '#7d94c9',
            'blanco' => '#f7f7f2',
            'p. rosa' => '#e8b8bd',
            'melange o.' => '#b9bbc0',
            'topo' => '#8b7d72',
        ];
        $brandName = $variants->first()?->empresa_nombre;
        $brandName = $brandName ? \Illuminate\Support\Str::of($brandName)->replace(['S.A.C', 'PERU'], '')->trim()->title() : 'Overshark';
        $sizeOrder = ['XS' => 0, 'S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4, 'XXL' => 5];
        $allSizes = $sizes->sortBy(fn ($size) => $sizeOrder[mb_strtoupper($size)] ?? 99)->values();
        // Stock real por color y talla: habilita solo las tallas que existen en el color elegido.
        $stockByColor = $variants->whereNotNull('color')->groupBy('color')
            ->map(fn ($items) => $items->groupBy('talla')->map(fn ($rows) => (int) $rows->sum('stock')));
        $firstColor = $colorOptions->first()['name'] ?? null;
        $firstSize = $allSizes->first(fn ($size) => ($stockByColor[$firstColor][$size] ?? 0) > 0) ?? $allSizes->first();
        $freeShippingFrom = \App\Support\Shipping::freeFrom();
    @endphp

    <div class="os-pdp px-4 pb-16 pt-4 text-white sm:px-6 lg:px-10 lg:pt-8" data-pdp
        data-pdp-stock='@json($stockByColor)'>
        <div class="mx-auto max-w-[1240px]">
            <nav class="mb-4 flex lg:hidden flex-wrap items-center gap-2 text-xs text-haze" aria-label="Ruta">
                <a href="{{ route('web.home') }}" class="hover:text-white">Inicio</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('web.products.search', ['orden' => 'populares']) }}" class="hover:text-white">Tienda</a>
                <span aria-hidden="true">/</span>
                <span class="text-mist">{{ \Illuminate\Support\Str::title(mb_strtolower($producto->name)) }}</span>
            </nav>

            @if (session('status'))
                <div class="mb-6 rounded-xl border border-neon/40 bg-neon/10 px-4 py-3 text-sm font-bold text-neon-soft" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(380px,0.85fr)] lg:gap-12">
                {{-- Galería editorial: foto grande del alto de la pantalla, flechas y puntos --}}
                <div data-product-zoom-frame class="os-pdp-stage">
                    <img data-product-card-image src="{{ $mainImages[0] }}" alt="{{ $producto->name }}" class="h-full w-full origin-center object-contain transition-transform duration-200">
                    <span class="absolute left-5 top-5 rounded-full bg-red-500 px-3 py-1 text-xs font-black text-white">-20%</span>
                    <button type="button" data-pdp-step="-1" class="os-pdp-arrow left-4" aria-label="Foto anterior">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg>
                    </button>
                    <button type="button" data-pdp-step="1" class="os-pdp-arrow right-4" aria-label="Foto siguiente">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                    </button>
                    <div data-pdp-thumbs class="absolute inset-x-0 bottom-5 flex justify-center gap-2">
                        @foreach (array_slice($mainImages, 0, 5) as $image)
                            <button type="button" data-pdp-thumb data-image="{{ $image }}" @class(['os-pdp-dot', 'is-active' => $loop->first]) aria-label="Ver foto {{ $loop->iteration }}"></button>
                        @endforeach
                    </div>
                    <button type="button" data-product-lens-toggle class="absolute bottom-4 right-4 grid h-11 w-11 place-items-center rounded-full bg-ink/85 text-white shadow-lg backdrop-blur transition hover:scale-105" aria-label="Activar zoom de imagen" aria-pressed="false">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 4.5 4.5"/><path d="M10.5 7.5v6M7.5 10.5h6"/></svg>
                    </button>
                </div>

                {{-- Compra --}}
                <div class="lg:sticky lg:top-[calc(var(--header-h)+2rem)] lg:self-start">
                    <nav class="mb-6 hidden flex-wrap items-center gap-2 text-xs text-haze lg:flex" aria-label="Ruta">
                        <a href="{{ route('web.home') }}" class="hover:text-white">Inicio</a>
                        <span aria-hidden="true">/</span>
                        <a href="{{ route('web.products.search', ['orden' => 'populares']) }}" class="hover:text-white">Tienda</a>
                        <span aria-hidden="true">/</span>
                        <span class="text-mist">{{ \Illuminate\Support\Str::title(mb_strtolower($producto->name)) }}</span>
                    </nav>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-electric-soft">{{ $brandName }}</p>
                    <h1 class="mt-2 font-heading text-3xl font-black uppercase leading-tight tracking-tight sm:text-4xl">{{ $producto->name }}</h1>

                    <div class="mt-4 flex flex-wrap items-end gap-3">
                        <p class="text-4xl font-black leading-none">S/ {{ number_format($price, 2) }}</p>
                        @if ($oldPrice > 0)
                            <p class="pb-0.5 text-base text-haze line-through">S/ {{ number_format($oldPrice, 2) }}</p>
                            <span class="mb-0.5 rounded-md bg-red-500/15 px-2 py-1 text-xs font-black text-red-400">Ahorras S/ {{ number_format($oldPrice - $price, 2) }}</span>
                        @endif
                    </div>
                    @if ($freeShippingFrom > 0)
                        <p class="mt-2 text-sm text-mist">Envío gratis en compras desde <b class="text-white">S/ {{ number_format($freeShippingFrom, 0) }}</b></p>
                    @endif

                    <hr class="my-7 border-edge">

                    {{-- Color --}}
                    <div>
                        <p class="text-sm text-mist">Color: <b data-pdp-color-name class="font-bold uppercase text-white">{{ $firstColor ?? 'Elige un color' }}</b></p>
                        <div class="mt-3 grid grid-cols-5 gap-2" role="radiogroup" aria-label="Color">
                            @foreach ($colorOptions as $color)
                                <button type="button" role="radio" aria-checked="{{ $loop->first ? 'true' : 'false' }}"
                                    data-pdp-color="{{ $color['name'] }}" data-images='@json($color['images'])'
                                    class="os-pdp-chip" aria-label="Color {{ $color['name'] }}" title="{{ $color['name'] }}">
                                    <img src="{{ $color['images'][0] }}" alt="" loading="lazy" decoding="async">
                                    <span class="os-pdp-chip__dot" style="--sw: {{ $swatches[mb_strtolower($color['name'])] ?? '#b8b8bd' }}" aria-hidden="true"></span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Talla --}}
                    <div class="mt-6">
                        <div class="flex items-center justify-between text-sm">
                            <p class="text-mist">Talla: <b data-pdp-size-name class="font-bold text-white">{{ $firstSize }}</b></p>
                            @if ($producto->sizeGuideImageUrl())
                                <button type="button" data-size-guide-open class="inline-flex items-center gap-1.5 font-semibold text-electric-soft underline-offset-4 hover:underline">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 8h18v8H3z"/><path d="M7 8v3M11 8v4M15 8v3M19 8v4"/></svg>
                                    Guía de tallas
                                </button>
                            @endif
                        </div>
                        <div class="os-pdp-sizes mt-3" role="radiogroup" aria-label="Talla">
                            @foreach ($allSizes as $size)
                                <button type="button" role="radio" aria-checked="{{ $size === $firstSize ? 'true' : 'false' }}" data-pdp-size="{{ $size }}" class="os-pdp-size">{{ $size }}</button>
                            @endforeach
                        </div>
                        <p data-pdp-stock-note class="mt-3 hidden text-sm font-semibold text-amber-300"></p>
                    </div>

                    {{-- Cantidad + carrito --}}
                    <form action="{{ route('web.cart.store') }}" method="POST" data-cart-form class="mt-7">
                        @csrf
                        <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                        <input type="hidden" name="color" value="{{ $firstColor }}" data-pdp-input-color>
                        <input type="hidden" name="talla" value="{{ $firstSize }}" data-pdp-input-size>
                        <input type="hidden" name="qty" value="1" data-pdp-input-qty>

                        <div class="flex gap-3">
                            <div class="flex h-14 shrink-0 items-center rounded-xl border border-edge-strong">
                                <button type="button" data-pdp-qty="-1" class="grid h-full w-11 place-items-center text-xl text-mist hover:text-white" aria-label="Quitar una unidad">−</button>
                                <span data-pdp-qty-value class="w-8 text-center text-base font-bold" aria-live="polite">1</span>
                                <button type="button" data-pdp-qty="1" class="grid h-full w-11 place-items-center text-xl text-mist hover:text-white" aria-label="Agregar una unidad">+</button>
                            </div>
                            <button type="submit" data-pdp-submit class="btn-primary h-14 flex-1 gap-2 whitespace-nowrap px-4 text-[13px] tracking-[0.04em]">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 8h14l-1 12H6Z"/><path d="M9 9V7a3 3 0 0 1 6 0v2"/></svg>
                                Agregar al carrito
                            </button>
                        </div>
                        <button type="submit" name="checkout" value="1" data-pdp-submit class="os-pdp-buy mt-3 h-14 w-full text-sm">Comprar ahora</button>
                    </form>

                    {{-- Beneficios --}}
                    <ul class="mt-7 grid gap-3 sm:grid-cols-3">
                        @foreach ([
                            ['M4 7h11v9H4zM15 10h4l2 3v3h-6z', 'Envíos a todo el Perú', 'Lima y provincias'],
                            ['M4 12a8 8 0 1 0 2.3-5.7M4 4v4h4', 'Cambios fáciles', 'Si la talla no te queda'],
                            ['M12 3 4 6v5c0 5 3.4 8.4 8 10 4.6-1.6 8-5 8-10V6Z', 'Pago seguro', 'O contraentrega'],
                        ] as [$icon, $title, $copy])
                            <li class="flex items-center gap-3 rounded-xl border border-edge bg-panel/60 px-3 py-3">
                                <svg class="h-6 w-6 shrink-0 text-electric-soft" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                                <span><b class="block text-xs font-black">{{ $title }}</b><span class="text-[11px] text-haze">{{ $copy }}</span></span>
                            </li>
                        @endforeach
                    </ul>

                    @if ($paymentMethods->isNotEmpty())
                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            <span class="mr-1 text-xs text-haze">Aceptamos:</span>
                            @foreach ($paymentMethods as $method)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-panel-2 px-2.5 py-1.5 text-[11px] font-bold">
                                    @if ($method->imageUrl())
                                        <img src="{{ $method->imageUrl() }}" alt="" class="h-5 w-5 rounded-full object-contain">
                                    @endif
                                    {{ $method->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Detalles --}}
                    <div class="mt-7 divide-y divide-edge border-y border-edge">
                        @foreach ([
                            'Descripción' => $producto->descripcion,
                            'Composición' => $producto->composicion,
                            'Cuidados' => $producto->cuidados,
                        ] as $label => $text)
                            <details class="os-pdp-details group" @if ($loop->first) open @endif>
                                <summary class="flex cursor-pointer list-none items-center justify-between py-4 text-sm font-black uppercase tracking-wide">
                                    {{ $label }}
                                    <svg class="h-4 w-4 text-haze transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                </summary>
                                <div class="pb-5 text-sm leading-7 text-mist">
                                    @if ($text)
                                        {!! nl2br(e($text)) !!}
                                    @else
                                        <p>Muy pronto agregaremos esta información.</p>
                                    @endif
                                    @if ($loop->first)
                                        <dl class="mt-3 grid gap-1">
                                            @foreach (['Material' => $producto->material, 'Fit' => $producto->fit, 'Sensación' => $producto->sensacion] as $key => $value)
                                                @if ($value)
                                                    <div class="flex gap-2"><dt class="font-semibold text-white">{{ $key }}:</dt><dd>{{ $value }}</dd></div>
                                                @endif
                                            @endforeach
                                        </dl>
                                    @endif
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($recommendedProducts->isNotEmpty())
        <section class="px-5 pb-20 text-white lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="mb-8">
                    <h2 class="text-2xl font-black uppercase tracking-tight">Productos similares</h2>
                    <p class="mt-2 text-sm text-haze">Tambien podrian interesarte.</p>
                </div>

                <div class="-mx-5 flex snap-x snap-mandatory gap-6 overflow-x-auto px-5 pb-4 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-3">
                    @foreach ($recommendedProducts as $recommended)
                        @php
                            $displayColors = collect($recommended->display_colors ?? []);
                            $recommendedOldPrice = (float) $recommended->min_price > 0 ? ((float) $recommended->min_price / 0.8) : 0;
                        @endphp
                        <article class="group basis-[78vw] shrink-0 snap-start rounded-2xl bg-panel p-4 shadow-xl shadow-black/30 transition hover:-translate-y-1 sm:basis-auto">
                            <a href="{{ route('web.products.show', $recommended->id) }}" class="relative block aspect-[4/5] overflow-hidden rounded-xl bg-frame">
                                <img data-product-card-image src="{{ $recommended->display_image }}" alt="{{ $recommended->name }}" class="h-full w-full object-contain object-center transition duration-500 group-hover:scale-105">
                                <span class="absolute left-0 top-0 rounded-br-lg bg-red-500/10 px-3 py-1.5 text-base font-medium text-red-400">-20%</span>
                                <span class="absolute bottom-4 right-4 grid h-10 w-10 place-items-center rounded-full bg-neon text-white shadow-lg" aria-hidden="true">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
                                </span>
                            </a>
                            <div class="p-5">
                                <a href="{{ route('web.products.show', $recommended->id) }}" class="line-clamp-2 min-h-10 text-base font-bold text-white transition hover:text-neon-soft">{{ $recommended->name }}</a>
                                <div class="mt-2 flex items-baseline gap-2">
                                    <p class="text-xl font-black">S/ {{ number_format((float) $recommended->min_price, 2) }}</p>
                                    @if ($recommendedOldPrice > 0)
                                        <p class="text-sm text-haze line-through">S/ {{ number_format($recommendedOldPrice, 2) }}</p>
                                    @endif
                                </div>
                                <div class="mt-4 flex flex-wrap items-center gap-1.5">
                                    @foreach ($displayColors as $color)
                                        @php
                                            $colorName = mb_strtolower($color['name']);
                                            $swatchColor = $swatches[$colorName] ?? '#b8b8bd';
                                        @endphp
                                        @if ($loop->iteration === 9 && $displayColors->count() > 8)
                                            <button type="button" data-product-colors-expand data-collapsed-label="+{{ $displayColors->count() - 8 }}" data-expanded-label="-" class="grid h-5 min-w-5 place-items-center rounded-full border border-edge-strong bg-panel px-1 text-[11px] font-black leading-none text-mist transition hover:border-neon-soft hover:text-white" aria-label="Mostrar colores extra" aria-expanded="false">
                                                +{{ $displayColors->count() - 8 }}
                                            </button>
                                        @endif
                                    <button
                                        type="button"
                                        data-product-color
                                        data-image="{{ $color['image'] }}"
                                        @class([
                                            'h-5 w-5 rounded-full border border-edge-strong ring-offset-2 ring-offset-ink transition hover:ring-2 hover:ring-edge-strong',
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
                </div>
            </div>
        </section>
    @endif

    <div data-product-zoom-modal class="fixed inset-0 z-50 hidden bg-black/85 px-4 py-6 backdrop-blur-sm sm:px-8" role="dialog" aria-modal="true" aria-label="Imagen ampliada del producto">
        <button type="button" data-product-zoom-close class="absolute right-5 top-5 grid h-11 w-11 place-items-center rounded-full bg-panel text-white shadow-lg" aria-label="Cerrar imagen ampliada">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
        <div class="flex h-full w-full items-center justify-center">
            <img data-product-zoom-image src="{{ $mainImages[0] }}" alt="{{ $producto->name }}" class="max-h-full max-w-full rounded-2xl object-contain shadow-2xl">
        </div>
    </div>

    @if ($producto->sizeGuideImageUrl())
        <div data-size-guide-modal class="fixed inset-0 z-50 hidden bg-black/85 px-4 py-6 backdrop-blur-sm sm:px-8" role="dialog" aria-modal="true" aria-label="Guia de tallas">
            <button type="button" data-size-guide-close class="absolute right-5 top-5 grid h-11 w-11 place-items-center rounded-full bg-panel text-white shadow-lg" aria-label="Cerrar guia de tallas">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
            <div class="flex h-full w-full items-center justify-center">
                <img src="{{ $producto->sizeGuideImageUrl() }}" alt="Guia de tallas {{ $producto->name }}" class="max-h-full max-w-full rounded-2xl bg-panel object-contain shadow-2xl">
            </div>
        </div>
    @endif
@endsection
