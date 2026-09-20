@extends('layouts.web')

@section('title', $combo->name.' · Overshark')

@section('content')
    <section class="bg-[#f1f2f4] px-5 py-12 text-slate-950 sm:py-16 lg:px-8">
        <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(360px,.95fr)] lg:items-center">
            <div class="relative aspect-[4/3] overflow-hidden rounded-3xl bg-slate-950 shadow-2xl shadow-slate-300/50">
                <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
                <div class="absolute bottom-6 left-6 right-6 flex items-end justify-between gap-4 text-white">
                    <span class="rounded-full bg-white/95 px-3 py-1.5 text-xs font-black uppercase tracking-[0.16em] text-slate-950">{{ $combo->brand ?: 'Overshark' }}</span>
                    @if ($combo->modality)
                        <span class="text-sm font-bold text-white/85">{{ $combo->modality }}</span>
                    @endif
                </div>
            </div>

            <div>
                <p class="text-sm font-black uppercase tracking-[0.2em] text-cyan-600">Promoción Overshark</p>
                <h1 class="mt-3 text-4xl font-black uppercase leading-[.95] sm:text-6xl">{{ $combo->name }}</h1>
                @if ($combo->price !== null)
                    <p class="mt-6 text-4xl font-black text-slate-950">S/ {{ number_format((float) $combo->price, 2) }}</p>
                @endif
                <p class="mt-2 text-sm font-semibold text-slate-500">{{ $combo->quantityLabel() }}</p>

                <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Incluye</h2>
                    <ul class="mt-4 divide-y divide-slate-100">
                        @forelse ($combo->displayItems() as $item)
                            <li class="flex items-center justify-between gap-4 py-3 text-sm">
                                @php
                                    $searchUrl = route('web.products.search', ['q' => $item['zazu']]);
                                @endphp
                                <a href="{{ $searchUrl }}" class="font-bold text-slate-800 transition hover:text-cyan-700">{{ $item['zazu'] }}</a>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-600">×{{ $item['cantidad'] }}</span>
                            </li>
                        @empty
                            <li class="py-3 text-sm font-semibold text-slate-500">La composición se confirmará antes de publicar la promoción.</li>
                        @endforelse
                    </ul>
                    @if ($combo->notes)
                        <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold leading-6 text-amber-900">{{ $combo->notes }}</p>
                    @endif
                </div>

                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert">
                        {{ $errors->first('selections') }}
                    </div>
                @endif

                @php
                    $hasUnavailableSlot = $comboSlots->contains(fn (array $slot): bool => $slot['variants']->isEmpty());
                @endphp

                <form action="{{ route('web.combos.cart.store', $combo) }}" method="POST" data-combo-cart-form novalidate class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    @csrf
                    <div>
                        <h2 class="text-lg font-black">Elige talla y color</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Selecciona la combinación de cada unidad del combo antes de agregarlo al carrito.</p>
                    </div>

                    @if ($comboSlots->isEmpty() || $hasUnavailableSlot)
                        <div class="mt-5 rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold leading-6 text-amber-900">
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
                                    $oldVariantId = old("selections.{$index}");
                                    $initialVariant = $slot['variants']->firstWhere('id', (int) $oldVariantId) ?? $slot['variants']->first();
                                    $sizes = $slot['variants']->pluck('talla')->filter()->unique()->values();
                                    $colors = $slot['variants']->where('talla', $initialVariant?->talla)->pluck('color')->filter()->unique()->values();
                                @endphp
                                <div data-combo-slot data-variants='@json($variantData)' class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-black uppercase tracking-[0.16em] text-cyan-700">Unidad {{ $index + 1 }}</p>
                                            <p class="mt-1 text-sm font-black text-slate-950">{{ $slot['label'] }}</p>
                                        </div>
                                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-500">Producto</span>
                                    </div>

                                    @if ($slot['allow_product_choice'])
                                        <label class="mt-4 block text-xs font-black uppercase tracking-wide text-slate-600">
                                            Producto
                                            <select data-combo-product class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-950 outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100">
                                                @foreach ($slot['variants']->pluck('name')->unique()->values() as $productName)
                                                    <option value="{{ $productName }}" @selected($initialVariant?->name === $productName)>{{ $productName }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @endif

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <label class="block text-xs font-black uppercase tracking-wide text-slate-600">
                                            Talla
                                            <select data-combo-size class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-950 outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100">
                                                @foreach ($sizes as $size)
                                                    <option value="{{ $size }}" @selected($initialVariant?->talla === $size)>{{ $size }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="block text-xs font-black uppercase tracking-wide text-slate-600">
                                            Color
                                            <select data-combo-color class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-950 outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100">
                                                @foreach ($colors as $color)
                                                    <option value="{{ $color }}" @selected($initialVariant?->color === $color)>{{ $color }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>
                                    <input type="hidden" name="selections[]" value="{{ $initialVariant?->id }}" data-combo-variant>
                                </div>
                            @endforeach
                        </div>

                        <p data-combo-selection-error class="mt-4 hidden rounded-lg bg-red-50 px-3 py-2 text-sm font-semibold text-red-700" role="alert"></p>
                        <button type="submit" data-combo-cart-submit class="btn-primary mt-5 w-full gap-2 px-5 py-4 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M5 8h14l1 13H4Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                            Agregar combo al carrito · S/ {{ number_format((float) $combo->price, 2) }}
                        </button>
                    @endif
                </form>

                <a href="{{ route('web.home') }}#productos" class="btn-secondary mt-4 w-full px-7 py-3.5">Ver productos disponibles</a>
            </div>
        </div>
    </section>
@endsection
