@extends('layouts.web')

@section('title', $combo->name.' · Overshark')

@section('content')
    <section class="px-5 py-12 text-white sm:py-16 lg:px-8">
        <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(360px,.95fr)] lg:items-center">
            <div class="relative aspect-[4/3] overflow-hidden rounded-3xl bg-neon shadow-2xl shadow-black/30">
                <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-ink/70 via-transparent to-transparent"></div>
                <div class="absolute bottom-6 left-6 right-6 flex items-end justify-between gap-4 text-white">
                    <span class="rounded-full bg-ink/90 px-3 py-1.5 text-xs font-black uppercase tracking-[0.16em] text-white">{{ $combo->brand ?: 'Overshark' }}</span>
                    @if ($combo->modality)
                        <span class="text-sm font-bold text-white/85">{{ $combo->modality }}</span>
                    @endif
                </div>
            </div>

            <div>
                <p class="text-sm font-black uppercase tracking-[0.2em] text-neon-soft">Promoción Overshark</p>
                <h1 class="mt-3 text-4xl font-black uppercase leading-[.95] sm:text-6xl">{{ $combo->name }}</h1>
                @if ($combo->price !== null)
                    <p class="mt-6 text-4xl font-black text-white">S/ {{ number_format((float) $combo->price, 2) }}</p>
                @endif
                <p class="mt-2 text-sm font-semibold text-haze">{{ $combo->quantityLabel() }}</p>

                <div class="mt-8 rounded-2xl border border-edge bg-panel p-5 shadow-sm">
                    <h2 class="text-lg font-black">Incluye</h2>
                    <ul class="mt-4 divide-y divide-edge">
                        @forelse ($combo->displayItems() as $item)
                            <li class="flex items-center justify-between gap-4 py-3 text-sm">
                                @php
                                    $searchUrl = route('web.products.search', ['q' => $item['zazu']]);
                                @endphp
                                <a href="{{ $searchUrl }}" class="font-bold text-white transition hover:text-neon-soft">{{ $item['zazu'] }}</a>
                                <span class="rounded-full bg-panel-2 px-2.5 py-1 text-xs font-black text-mist">×{{ $item['cantidad'] }}</span>
                            </li>
                        @empty
                            <li class="py-3 text-sm font-semibold text-haze">La composición se confirmará antes de publicar la promoción.</li>
                        @endforelse
                    </ul>
                    @if ($combo->notes)
                        <p class="mt-4 rounded-xl bg-amber-400/10 px-4 py-3 text-sm font-semibold leading-6 text-amber-200">{{ $combo->notes }}</p>
                    @endif
                </div>

                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm font-semibold text-red-300" role="alert">
                        {{ $errors->first('selections') }}
                    </div>
                @endif

                @php
                    $hasUnavailableSlot = $comboSlots->contains(fn (array $slot): bool => $slot['variants']->isEmpty());
                @endphp

                <form action="{{ route('web.combos.cart.store', $combo) }}" method="POST" data-combo-cart-form novalidate class="mt-6 rounded-2xl border border-edge bg-panel p-5 shadow-sm sm:p-6">
                    @csrf
                    <div>
                        <h2 class="text-lg font-black">Elige talla y color</h2>
                        <p class="mt-1 text-sm leading-6 text-haze">Los productos repetidos se agrupan en una sola tarjeta. Elige la talla y el color de cada unidad por separado.</p>
                    </div>

                    @if ($comboSlots->isEmpty() || $hasUnavailableSlot)
                        <div class="mt-5 rounded-xl bg-amber-400/10 px-4 py-3 text-sm font-semibold leading-6 text-amber-200">
                            Este combo no tiene suficientes productos disponibles para elegir en este momento.
                        </div>
                    @else
                        <div class="mt-5 space-y-4">
                            @foreach ($comboSlots as $index => $slot)
                                @php
                                    $variantData = $slot['variants']->map(fn ($variant): array => [
                                        'id' => $variant->id,
                                        'producto' => $variant->name,
                                        'talla' => $variant->talla,
                                        'color' => $variant->color,
                                    ])->values();
                                    $allColors = $slot['variants']->pluck('color')->filter()->unique()->values();
                                    $swatches = [
                                        'azul' => '#1d4f91', 'beige' => '#ddcdbd', 'perla' => '#e8e0d6', 'cemento' => '#9b9b95',
                                        'negro' => '#111111', 'vino' => '#7b1028', 'botella' => '#0f4f3b', 'plomo' => '#9a9aa0',
                                        'pacay' => '#8c9b73', 'denim' => '#526f91', 'blanco' => '#f7f7f2', 'p. rosa' => '#e8b8bd',
                                    ];
                                @endphp
                                <div class="rounded-xl border border-edge bg-panel-2 p-4" aria-label="{{ $slot['product'] }}: {{ $slot['quantity'] }} unidades">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-black uppercase tracking-[0.16em] text-neon-soft">{{ $slot['allow_product_choice'] ? 'Unidad '.($index + 1) : 'Producto '.($index + 1) }}</p>
                                            <p class="mt-1 text-sm font-black text-white">{{ $slot['label'] }}</p>
                                        </div>
                                        <span class="rounded-full bg-panel px-2.5 py-1 text-xs font-bold text-haze">{{ ($slot['quantity'] ?? 1) > 1 ? '×'.($slot['quantity']).' unidades' : 'Producto' }}</span>
                                    </div>
                                    @if (($slot['quantity'] ?? 1) > 1)
                                        <p class="mt-2 text-xs font-semibold text-haze">Configura cada unidad con una combinación distinta si lo deseas.</p>
                                    @endif

                                    @php
                                        $slotQuantity = max(1, (int) ($slot['quantity'] ?? 1));
                                        $selectionOffset = $comboSlots->take($index)->sum(fn (array $previousSlot): int => max(1, (int) ($previousSlot['quantity'] ?? 1)));
                                    @endphp
                                    @for ($unit = 0; $unit < $slotQuantity; $unit++)
                                        @php
                                            $selectionIndex = $selectionOffset + $unit;
                                            $oldVariantId = old("selections.{$selectionIndex}");
                                            $initialVariant = $slot['variants']->firstWhere('id', (int) $oldVariantId) ?? $slot['variants']->first();
                                            $sizes = $slot['variants']->pluck('talla')->filter()->unique()->values();
                                            $colors = $slot['variants']->where('talla', $initialVariant?->talla)->pluck('color')->filter()->unique()->values();
                                        @endphp
                                        <div data-combo-slot data-variants='@json($variantData)' class="mt-4 rounded-xl border border-edge bg-panel p-4 first:mt-5">
                                            <div class="mb-4 flex items-center justify-between gap-3">
                                                <p class="text-xs font-black uppercase tracking-[0.16em] text-neon-soft">Unidad {{ $unit + 1 }}</p>
                                                <span class="rounded-full bg-panel-2 px-2.5 py-1 text-xs font-bold text-haze">Escoge una combinación</span>
                                            </div>

                                    @if ($slot['allow_product_choice'])
                                        <label class="mt-4 block text-xs font-black uppercase tracking-wide text-mist">
                                            Producto
                                            <select data-combo-product class="mt-2 h-11 w-full rounded-lg border border-edge-strong bg-panel px-3 text-sm font-semibold text-white outline-none transition focus:border-neon-soft focus:ring-4 focus:ring-neon/25">
                                                @foreach ($slot['variants']->pluck('name')->unique()->values() as $productName)
                                                    <option value="{{ $productName }}" @selected($initialVariant?->name === $productName)>{{ $productName }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @endif

                                    <div class="mt-5 space-y-5">
                                        <div>
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="text-xs font-black uppercase tracking-wide text-mist">Talla</p>
                                                <span class="text-xs font-bold text-haze">Elige una</span>
                                            </div>
                                            <div data-combo-size-options class="mt-3 flex flex-wrap gap-2">
                                                @foreach ($sizes as $size)
                                                    <button type="button" data-combo-size-option="{{ $size }}" @class([
                                                        'grid h-10 min-w-10 place-items-center border px-3 text-sm font-bold transition hover:border-neon-soft hover:bg-neon-soft hover:text-white',
                                                        'bg-neon text-white' => $initialVariant?->talla === $size,
                                                        'bg-panel text-white' => $initialVariant?->talla !== $size,
                                                    ])>{{ $size }}</button>
                                                @endforeach
                                            </div>
                                            <select data-combo-size class="sr-only" tabindex="-1" aria-hidden="true">
                                                @foreach ($sizes as $size)
                                                    <option value="{{ $size }}" @selected($initialVariant?->talla === $size)>{{ $size }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="text-xs font-black uppercase tracking-wide text-mist">Color</p>
                                                <span data-combo-selected-color class="text-xs font-bold text-white">{{ $initialVariant?->color ?: 'Elige un color' }}</span>
                                            </div>
                                            <div data-combo-color-options class="mt-3 flex flex-wrap items-center gap-3">
                                                @foreach ($allColors as $color)
                                                    @php($colorName = mb_strtolower($color))
                                                    <button type="button" data-combo-color-option="{{ $color }}" @class([
                                                        'h-7 w-7 rounded-full border border-edge-strong ring-offset-2 ring-offset-ink transition hover:ring-2 hover:ring-edge-strong',
                                                        'ring-2 ring-neon-soft' => $initialVariant?->color === $color,
                                                    ]) style="background-color: {{ $swatches[$colorName] ?? '#b8b8bd' }}" aria-label="Elegir color {{ $color }}" title="{{ $color }}"></button>
                                                @endforeach
                                            </div>
                                            <select data-combo-color class="sr-only" tabindex="-1" aria-hidden="true">
                                                @foreach ($colors as $color)
                                                    <option value="{{ $color }}" @selected($initialVariant?->color === $color)>{{ $color }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <input type="hidden" name="selections[]" value="{{ $initialVariant?->id }}" data-combo-variant>
                                </div>
                                    @endfor
                                </div>
                            @endforeach
                        </div>

                        <p data-combo-selection-error class="mt-4 hidden rounded-lg bg-red-500/10 px-3 py-2 text-sm font-semibold text-red-300" role="alert"></p>
                        <button type="submit" data-combo-cart-submit class="btn-primary mt-5 w-full gap-2 px-5 py-4 focus:outline-none focus:ring-4 focus:ring-neon/25">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M5 8h14l1 13H4Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                            Agregar combo al carrito · S/ {{ number_format((float) $combo->price, 2) }}
                        </button>
                    @endif
                </form>

                <a href="{{ route('web.products.search', ['orden' => 'populares']) }}" class="btn-secondary mt-4 w-full px-7 py-3.5">Ver productos disponibles</a>
            </div>
        </div>
    </section>
@endsection
