@extends('layouts.web')

@section('title', $producto->name.' | Overshark')

@section('content')
    @php
        $oldPrice = $price > 0 ? $price / 0.8 : 0;
        $selectedSize = $sizes->first();
        $swatches = [
            'azul' => '#1d4f91',
            'beige' => '#ddcdbd',
            'perla' => '#e8e0d6',
            'cemento' => '#9b9b95',
            'negro' => '#111111',
            'vino' => '#7b1028',
            'botella' => '#0f4f3b',
            'plomo' => '#9a9aa0',
            'pacay' => '#8c9b73',
            'denim' => '#526f91',
            'blanco' => '#f7f7f2',
            'p. rosa' => '#e8b8bd',
        ];
    @endphp

    <section class="bg-white px-5 py-8 text-slate-950 lg:px-8">
        <article class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[minmax(0,1.05fr)_minmax(390px,0.8fr)]">
            <div class="grid gap-4 sm:grid-cols-[92px_1fr]">
                <div class="order-2 flex gap-3 overflow-x-auto sm:order-1 sm:flex-col sm:overflow-visible">
                    @foreach (array_slice($mainImages, 0, 4) as $image)
                        <button type="button" data-product-color data-image="{{ $image }}" class="h-24 w-24 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-[#F1F2F4] p-1 transition hover:border-cyan-600 sm:h-28 sm:w-full">
                            <img src="{{ $image }}" alt="{{ $producto->name }}" class="h-full w-full rounded-lg object-cover">
                        </button>
                    @endforeach
                </div>

                <div class="order-1 relative self-start overflow-hidden rounded-3xl bg-white sm:order-2">
                    <img data-product-card-image src="{{ $mainImages[0] }}" alt="{{ $producto->name }}" class="mx-auto h-auto w-full object-contain object-top">
                    <button type="button" data-product-zoom-open class="absolute bottom-5 right-5 grid h-12 w-12 place-items-center rounded-full bg-white text-slate-950 shadow-lg transition hover:scale-105" aria-label="Ampliar imagen">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 4.5 4.5"/><path d="M10.5 7.5v6M7.5 10.5h6"/></svg>
                    </button>
                </div>
            </div>

            <div class="lg:pt-1">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-slate-950">{{ $producto->name }}</h1>
                        <div class="mt-1 flex items-center gap-3">
                            <p class="text-5xl font-black leading-none">S/ {{ number_format($price, 2) }}</p>
                            <span class="rounded-md bg-red-50 px-2 py-1 text-sm font-bold text-red-600">-20%</span>
                            @if ($oldPrice > 0)
                                <span class="text-sm text-slate-400 line-through">S/ {{ number_format($oldPrice, 2) }}</span>
                            @endif
                        </div>
                        <span class="mt-3 inline-flex rounded-md bg-red-50 px-2 py-1 text-sm font-bold text-red-600">Ahorras S/ {{ number_format(max($oldPrice - $price, 0), 2) }}</span>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-red-200 p-4">
                    <div class="flex items-center gap-3 text-sm font-bold">
                        <svg class="h-6 w-6 text-red-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 1.8c.7 4.4-2.2 5.8-2.2 8.7 0 1.2.7 2.1 1.7 2.1 1.6 0 2.5-1.5 2.1-3.5 2.6 2 4 4.4 4 7.1a7.1 7.1 0 0 1-14.2 0c0-3.8 2-7.3 5.8-10.5-.2 2.6.5 4.1 1.5 4.1 1.4 0 2.3-2.7 1.3-8Z"/></svg>
                        Ahorra mas comprando en pack
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach ([['3 por S/ 49.00', 'Equivale a S/ 16.33 c/u', 'Ahorra S/ 10.70'], ['3 por S/ 49.00', 'Equivale a S/ 16.33 c/u', 'Ahorra S/ 10.70']] as [$pack, $equiv, $save])
                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-red-200 px-4 py-3 text-left transition hover:border-red-400">
                                <span>
                                    <span class="block text-lg font-black">{{ $pack }}</span>
                                    <span class="mt-1 block text-sm text-slate-500">{{ $equiv }}</span>
                                </span>
                                <span class="rounded-md bg-red-50 px-2 py-1 text-sm font-bold text-red-600">{{ $save }}</span>
                            </button>
                        @endforeach
                    </div>
                    <a href="#packs" class="mt-3 flex justify-end text-sm font-bold underline">Ver mas packs -></a>
                </div>

                <div class="mt-6">
                    <p class="text-sm text-slate-600">Color: <span data-product-selected-color class="font-medium text-slate-950">{{ $colorOptions->first()['name'] ?? 'Selecciona color' }}</span></p>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach ($colorOptions as $color)
                            @php
                                $colorName = mb_strtolower($color['name']);
                                $swatchColor = $swatches[$colorName] ?? '#b8b8bd';
                            @endphp
                            <button type="button" data-product-color data-image="{{ $color['images'][0] }}" data-color-name="{{ $color['name'] }}" data-sizes='@json($color['sizes'])' class="h-5 w-5 rounded-full border border-slate-200 ring-offset-2 transition hover:ring-2 hover:ring-slate-300" style="background-color: {{ $swatchColor }}" aria-label="Ver color {{ $color['name'] }}"></button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 grid gap-6 sm:grid-cols-2">
                    <div>
                        <div class="flex items-center gap-2 text-sm">
                            <span>Talla:</span>
                            <a href="#" class="font-bold text-slate-500 underline">Guia de tallas</a>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-3">
                            @foreach ($sizes as $size)
                                <button type="button" data-product-size="{{ $size }}" @class([
                                    'grid h-10 w-10 place-items-center border border-slate-200 text-sm font-bold transition hover:border-slate-950 hover:bg-slate-950 hover:text-white',
                                    'bg-slate-950 text-white' => $size === $selectedSize,
                                    'bg-white text-slate-950' => $size !== $selectedSize,
                                ])>{{ $size }}</button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="text-sm">Cantidad:</p>
                        <div class="mt-4 flex w-28 items-center justify-between rounded-md border border-slate-200 px-3 py-2">
                            <button type="button" data-qty-dec class="text-2xl text-slate-500">-</button>
                            <input data-qty-input value="1" readonly class="w-8 bg-transparent text-center text-sm outline-none">
                            <button type="button" data-qty-inc class="text-2xl text-slate-500">+</button>
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <button type="button" class="btn-primary w-full gap-2 px-5 py-4">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M5 8h14l1 13H4Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                        Agregar al carrito
                    </button>
                    <button type="button" class="btn-secondary w-full px-5 py-4">Comprar ahora</button>
                </div>

                <div class="mt-8 divide-y divide-slate-200 border-y border-slate-200">
                    @foreach ([
                        ['Envios a todo el Peru', 'Lima: delivery o agencia · Provincia: agencia', 'S/ 14 costo unico'],
                        ['Pago contraentrega', 'Paga cuando recibas tu pedido', null],
                    ] as [$title, $copy, $badge])
                        <div class="flex gap-4 py-4">
                            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-slate-200">
                                <svg class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 7h11v9H4z"/><path d="M15 10h4l2 3v3h-6z"/><circle cx="8" cy="18" r="1.6"/><circle cx="18" cy="18" r="1.6"/></svg>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-black">{{ $title }}</p>
                                    @if ($badge)
                                        <span class="rounded-md bg-blue-100 px-2 py-1 text-xs font-black text-blue-700">{{ $badge }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $copy }}</p>
                            </div>
                        </div>
                    @endforeach

                    @if ($paymentMethods->isNotEmpty())
                        <div class="flex gap-4 py-4">
                            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-slate-200">
                                <svg class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 7h16v10H4z"/><path d="M7 11h4"/><path d="M15 14h2"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-black">Metodos de pago</p>
                                <p class="mt-1 text-xs text-slate-500">Pagos 100% seguros</p>
                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    @foreach ($paymentMethods as $method)
                                        <div class="flex items-center gap-2 rounded-full bg-[#F1F2F4] px-3 py-2">
                                            @if ($method->imageUrl())
                                                <img src="{{ $method->imageUrl() }}" alt="{{ $method->name }}" class="h-7 w-7 rounded-full object-contain">
                                            @endif
                                            <span class="text-xs font-black text-slate-950">{{ $method->name }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @foreach ([
                        ['Material de alta calidad', 'No destine, no encoge y no hace bolitas'],
                        ['Comoda y fresca', 'Ligera y transpirable'],
                    ] as [$title, $copy])
                        <div class="flex gap-4 py-4">
                            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-slate-200">
                                <svg class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 3 4 6v5c0 5 3.4 8.4 8 10 4.6-1.6 8-5 8-10V6Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black">{{ $title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $copy }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>
    </section>

    <div data-product-zoom-modal class="fixed inset-0 z-50 hidden bg-black/85 px-4 py-6 backdrop-blur-sm sm:px-8" role="dialog" aria-modal="true" aria-label="Imagen ampliada del producto">
        <button type="button" data-product-zoom-close class="absolute right-5 top-5 grid h-11 w-11 place-items-center rounded-full bg-white text-slate-950 shadow-lg" aria-label="Cerrar imagen ampliada">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
        <div class="flex h-full w-full items-center justify-center">
            <img data-product-zoom-image src="{{ $mainImages[0] }}" alt="{{ $producto->name }}" class="max-h-full max-w-full rounded-2xl object-contain shadow-2xl">
        </div>
    </div>
@endsection
