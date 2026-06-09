@extends('layouts.web')

@section('title', 'Buscar productos | Overshark')

@section('content')
    @php
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

    <section class="bg-white px-5 py-14 text-slate-950 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-9 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-cyan-600">Busqueda Overshark</p>
                    <h1 class="mt-2 text-3xl font-black uppercase sm:text-4xl">Productos encontrados</h1>
                    <p class="mt-2 text-slate-500">
                        @if ($search !== '')
                            Resultados para "{{ $search }}".
                        @else
                            Escribe un producto para buscar.
                        @endif
                    </p>
                </div>

                <form action="{{ route('web.products.search') }}" method="GET" class="flex w-full max-w-sm items-center gap-2">
                    <label for="simple-search" class="sr-only">Buscar productos</label>
                    <div class="relative w-full">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-slate-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 8v8m0-8a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm0 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8-8a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm0 0a4 4 0 0 1-4 4h-1a3 3 0 0 0-3 3"/></svg>
                        </div>
                        <input type="text" id="simple-search" name="q" value="{{ $search }}" class="block w-full rounded-lg border border-slate-300 bg-[#F7F7F7] py-2.5 pl-9 pr-3 text-sm font-semibold text-slate-950 outline-none placeholder:text-slate-500 focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100" placeholder="Buscar producto..." required>
                    </div>
                    <button type="submit" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-950 text-white shadow-sm transition hover:bg-cyan-600 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                        <span class="sr-only">Buscar</span>
                    </button>
                </form>
            </div>

            @if ($products->isEmpty())
                <div class="grid min-h-64 place-items-center rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-5 text-center">
                    <div>
                        <p class="text-lg font-black text-slate-700">No hay productos disponibles</p>
                        <p class="mt-1 text-sm text-slate-400">Solo se muestran productos con stock mayor a 0.</p>
                    </div>
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($products as $product)
                        @php
                            $displayColors = collect($product->display_colors ?? []);
                            $oldPrice = (float) $product->min_price > 0 ? ((float) $product->min_price / 0.8) : 0;
                        @endphp
                        <article class="group rounded-2xl bg-white p-4 shadow-xl shadow-slate-200/70 transition hover:-translate-y-1">
                            <a href="{{ route('web.products.show', $product->id) }}" class="relative block aspect-[4/5] overflow-hidden rounded-xl bg-[#F7F7F7]">
                                <img data-product-card-image src="{{ $product->display_image }}" alt="{{ $product->name }}" class="h-full w-full object-contain object-center transition duration-500 group-hover:scale-105">
                                <span class="absolute left-0 top-0 rounded-br-lg bg-red-50 px-3 py-1.5 text-base font-medium text-red-600">-20%</span>
                            </a>
                            <div class="p-5">
                                <a href="{{ route('web.products.show', $product->id) }}" class="line-clamp-2 min-h-10 text-base font-bold text-slate-950 transition hover:text-cyan-700">{{ $product->name }}</a>
                                <div class="mt-2 flex items-baseline gap-2">
                                    <p class="text-xl font-black">S/ {{ number_format((float) $product->min_price, 2) }}</p>
                                    @if ($oldPrice > 0)
                                        <p class="text-sm text-slate-400 line-through">S/ {{ number_format($oldPrice, 2) }}</p>
                                    @endif
                                </div>
                                <div class="mt-4 flex flex-wrap items-center gap-1.5">
                                    @foreach ($displayColors as $color)
                                        @php
                                            $colorName = mb_strtolower($color['name']);
                                            $swatchColor = $swatches[$colorName] ?? '#b8b8bd';
                                        @endphp
                                        @if ($loop->iteration === 9 && $displayColors->count() > 8)
                                            <button type="button" data-product-colors-expand data-collapsed-label="+{{ $displayColors->count() - 8 }}" data-expanded-label="-" class="grid h-5 min-w-5 place-items-center rounded-full border border-slate-300 bg-white px-1 text-[11px] font-black leading-none text-slate-700 transition hover:border-slate-950 hover:text-slate-950" aria-label="Mostrar colores extra" aria-expanded="false">
                                                +{{ $displayColors->count() - 8 }}
                                            </button>
                                        @endif
                                        <button
                                            type="button"
                                            data-product-color
                                            data-image="{{ $color['image'] }}"
                                            @class([
                                                'h-5 w-5 rounded-full border border-[#8E8E8E] ring-offset-2 transition hover:ring-2 hover:ring-slate-300',
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
            @endif
        </div>
    </section>
@endsection
