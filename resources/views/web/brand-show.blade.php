@extends('layouts.web')

@section('title', $brandPage->name.' | Overshark')

@section('content')
    <div style="--brand-primary: {{ $brandPage->primary_color }}; --brand-secondary: {{ $brandPage->secondary_color }}; --brand-accent: {{ $brandPage->accent_color }}; --brand-background: {{ $brandPage->background_color }}; --brand-text: {{ $brandPage->text_color }};">
        <section class="px-5 py-16 sm:py-20 lg:px-8" style="background-color: var(--brand-background); color: var(--brand-text);">
            <div class="mx-auto max-w-7xl">
                <div class="grid items-end gap-8 lg:grid-cols-[1fr_auto]">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.24em]" style="color: var(--brand-primary);">Coleccion oficial</p>
                        <h1 class="mt-3 text-4xl font-black uppercase tracking-tight sm:text-6xl">{{ $brandPage->name }}</h1>
                        <p class="mt-4 max-w-2xl text-base leading-7 opacity-75">Descubre los productos disponibles de {{ $brandPage->name }}. El catalogo se actualiza con el stock disponible.</p>
                    </div>
                    <div class="flex items-center gap-3 rounded-2xl px-5 py-4 shadow-sm" style="background-color: var(--brand-accent); color: var(--brand-secondary);">
                        <span class="h-4 w-4 rounded-full" style="background-color: var(--brand-primary);"></span>
                        <span class="text-sm font-black">{{ $products->count() }} productos disponibles</span>
                    </div>
                </div>
            </div>
        </section>

        <section id="combos" class="px-5 py-14 text-slate-950 sm:py-20 lg:px-8" style="background-color: var(--brand-accent);">
            <div class="mx-auto max-w-7xl">
                <div class="mb-9 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.22em]" style="color: var(--brand-primary);">Promociones de {{ $brandPage->name }}</p>
                        <h2 class="mt-2 text-3xl font-black uppercase sm:text-4xl">Combos disponibles</h2>
                        <p class="mt-2 text-slate-600">Elige una promocion de {{ $brandPage->name }} y selecciona sus productos.</p>
                    </div>
                </div>

                @if ($brandCombos->isEmpty())
                    <div class="grid min-h-40 place-items-center rounded-3xl border border-dashed border-slate-300 bg-white/70 px-5 text-center">
                        <div>
                            <p class="text-lg font-black text-slate-700">Aun no hay combos para esta marca</p>
                            <p class="mt-1 text-sm text-slate-500">Puedes crear uno desde Administracion &gt; Combos.</p>
                        </div>
                    </div>
                @else
                    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($brandCombos as $combo)
                            <article class="group overflow-hidden rounded-3xl border border-white/70 bg-white shadow-xl shadow-slate-900/10 transition hover:-translate-y-1">
                                <a href="{{ $combo->url ?: route('web.combos.show', $combo) }}" class="relative block aspect-[4/3] overflow-hidden bg-slate-100">
                                    <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                                    <span class="absolute left-4 top-4 rounded-full px-3 py-1.5 text-xs font-black uppercase tracking-wide text-white" style="background-color: var(--brand-primary);">{{ $brandPage->name }}</span>
                                </a>
                                <div class="p-5">
                                    <p class="text-xs font-black uppercase tracking-[0.16em]" style="color: var(--brand-primary);">{{ $combo->modality ?: 'Promocion' }}</p>
                                    <h3 class="mt-2 text-xl font-black text-slate-950">{{ $combo->name }}</h3>
                                    @if ($combo->price !== null)
                                        <p class="mt-3 text-2xl font-black text-slate-950">S/ {{ number_format((float) $combo->price, 2) }}</p>
                                    @endif
                                    <p class="mt-1 text-sm text-slate-500">{{ $combo->quantityLabel() }}</p>
                                    <a href="{{ $combo->url ?: route('web.combos.show', $combo) }}" class="mt-5 inline-flex rounded-xl px-5 py-3 text-sm font-black text-white transition hover:opacity-85" style="background-color: var(--brand-secondary);">Ver combo</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section id="productos" class="bg-white px-5 py-14 text-slate-950 sm:py-20 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="mb-9 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.22em]" style="color: var(--brand-primary);">Catalogo de {{ $brandPage->name }}</p>
                        <h2 class="mt-2 text-3xl font-black uppercase sm:text-4xl">Todos sus productos</h2>
                    </div>
                    <a href="{{ route('web.products.search', ['marca' => $brandPage->name]) }}" class="rounded-xl border px-5 py-3 text-sm font-black transition hover:-translate-y-0.5" style="border-color: var(--brand-primary); color: var(--brand-secondary);">Buscar en {{ $brandPage->name }}</a>
                </div>

                @if ($products->isEmpty())
                    <div class="grid min-h-64 place-items-center rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-5 text-center">
                        <div>
                            <p class="text-lg font-black text-slate-700">Aun no hay productos publicados</p>
                            <p class="mt-1 text-sm text-slate-400">Asigna esta marca a los productos desde Administracion &gt; Productos.</p>
                        </div>
                    </div>
                @else
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($products as $product)
                            @php
                                $swatches = [
                                    'azul' => '#1d4f91', 'beige' => '#ddcdbd', 'perla' => '#e8e0d6', 'cemento' => '#9b9b95',
                                    'negro' => '#111111', 'vino' => '#7b1028', 'botella' => '#0f4f3b', 'plomo' => '#9a9aa0',
                                    'pacay' => '#8c9b73', 'denim' => '#526f91', 'blanco' => '#f7f7f2', 'p. rosa' => '#e8b8bd',
                                ];
                                $displayColors = collect($product->display_colors ?? []);
                                $oldPrice = (float) $product->min_price > 0 ? ((float) $product->min_price / 0.8) : 0;
                            @endphp
                            <article class="group rounded-2xl bg-white p-4 shadow-xl shadow-slate-200/70 transition hover:-translate-y-1">
                                <a href="{{ route('web.products.show', $product->id) }}" class="relative block aspect-[4/5] overflow-hidden rounded-xl bg-[#F7F7F7]">
                                    <img data-product-card-image src="{{ $product->display_image }}" alt="{{ $product->name }}" class="h-full w-full object-contain object-center transition duration-500 group-hover:scale-105">
                                    <span class="absolute left-0 top-0 rounded-br-lg px-3 py-1.5 text-base font-medium text-white" style="background-color: var(--brand-primary);">{{ $brandPage->name }}</span>
                                </a>
                                <div class="p-5">
                                    <p class="text-[11px] font-black uppercase tracking-[0.16em]" style="color: var(--brand-primary);">{{ $product->marca ?: $brandPage->name }}</p>
                                    <a href="{{ route('web.products.show', $product->id) }}" class="mt-1 line-clamp-2 min-h-10 text-base font-bold text-slate-950 transition hover:opacity-70">{{ $product->name }}</a>
                                    <div class="mt-2 flex items-baseline gap-2">
                                        <p class="text-xl font-black">S/ {{ number_format((float) $product->min_price, 2) }}</p>
                                        @if ($oldPrice > 0)
                                            <p class="text-sm text-slate-400 line-through">S/ {{ number_format($oldPrice, 2) }}</p>
                                        @endif
                                    </div>
                                    <div class="mt-4 flex flex-wrap items-center gap-1.5">
                                        @foreach ($displayColors->take(8) as $color)
                                            @php($colorName = mb_strtolower($color['name']))
                                            <button type="button" data-product-color data-image="{{ $color['image'] }}" class="h-5 w-5 rounded-full border border-[#8E8E8E] ring-offset-2 transition hover:ring-2 hover:ring-slate-300" style="background-color: {{ $swatches[$colorName] ?? '#b8b8bd' }}" aria-label="Ver color {{ $color['name'] }}"></button>
                                        @endforeach
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
