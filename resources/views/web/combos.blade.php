@extends('layouts.web')

{{--
    Combos de la colección (App\Support\ComboCatalog): sin duplicados por canal de venta, solo con
    prendas disponibles y con la foto de las prendas incluidas. Primero los combos mixtos, luego los
    packs de un mismo modelo; cada grupo ordenado por precio por prenda.
--}}

@section('title', 'Combos | '.$brandLabel)
@section('meta_description', 'Combos y packs '.$brandLabel.': lleva más prendas a un precio especial.')

@php
    $sections = [
        'mix' => ['title' => 'Combina modelos', 'text' => 'Varias prendas distintas en un solo precio.'],
        'pack' => ['title' => 'Packs por modelo', 'text' => 'Tu modelo favorito en varios colores.'],
    ];
    $grouped = $combos->groupBy('kind');
@endphp

@section('content')
    <div class="px-4 pb-20 pt-10 text-white sm:px-6 lg:px-10">
        <div class="mx-auto max-w-[1320px]">
            <header class="max-w-2xl">
                <p class="text-xs font-black uppercase tracking-[0.25em] text-electric-soft">{{ $brandLabel }}</p>
                <h1 class="mt-3 font-heading text-4xl font-black uppercase leading-none sm:text-5xl">Combos</h1>
                <p class="mt-4 text-base text-mist">Lleva más prendas pagando menos. Eliges color y talla de cada una en el siguiente paso.</p>
            </header>

            @if ($combos->isEmpty())
                <div class="mt-10 rounded-2xl border border-dashed border-edge-strong bg-panel/60 px-6 py-16 text-center">
                    <p class="text-lg font-black">Muy pronto publicaremos nuevos combos.</p>
                    <a href="{{ route('web.products.search', ['orden' => 'populares']) }}" class="btn-primary mt-5 inline-flex px-6 py-3">Ver la tienda</a>
                </div>
            @else
                @if ($grouped->count() > 1)
                    <nav class="mt-8 flex flex-wrap gap-2" aria-label="Tipos de combo">
                        @foreach ($sections as $kind => $section)
                            @if ($grouped->has($kind))
                                <a href="#combos-{{ $kind }}" class="os-focus rounded-full border border-edge-strong px-4 py-2 text-sm font-bold text-mist transition hover:border-neon-soft hover:text-white">
                                    {{ $section['title'] }} <span class="text-haze">({{ $grouped[$kind]->count() }})</span>
                                </a>
                            @endif
                        @endforeach
                    </nav>
                @endif

                @foreach ($sections as $kind => $section)
                    @continue (! $grouped->has($kind))
                    <section id="combos-{{ $kind }}" class="mt-12 scroll-mt-[calc(var(--header-h)+1rem)]" aria-labelledby="combos-{{ $kind }}-title">
                        <h2 id="combos-{{ $kind }}-title" class="text-2xl font-black uppercase">{{ $section['title'] }}</h2>
                        <p class="mt-1 text-sm text-haze">{{ $section['text'] }}</p>

                        <ul class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            @foreach ($grouped[$kind] as $combo)
                                <li>
                                    <a href="{{ $combo['url'] }}" class="os-focus group flex h-full flex-col overflow-hidden rounded-2xl border border-edge bg-panel transition hover:-translate-y-1 hover:border-neon-soft">
                                        <div class="relative grid aspect-[4/3] overflow-hidden bg-white" style="grid-template-columns: repeat({{ max(1, count($combo['images'])) }}, minmax(0, 1fr))">
                                            @forelse ($combo['images'] as $image)
                                                <img src="{{ $image }}" alt="" class="h-full w-full object-cover object-top transition duration-500 group-hover:scale-105" loading="lazy" decoding="async">
                                            @empty
                                                <span class="grid place-items-center font-heading text-5xl font-black text-ink/20">{{ $combo['quantity'] }}x</span>
                                            @endforelse
                                            <span class="absolute left-3 top-3 rounded-full bg-ink px-3 py-1 text-xs font-black uppercase tracking-wide text-white">{{ $combo['quantity'] }} prendas</span>
                                        </div>
                                        <div class="flex flex-1 flex-col p-5">
                                            <h3 class="text-lg font-black leading-tight">{{ $combo['title'] }}</h3>
                                            <p class="mt-1 text-sm text-haze">{{ $combo['detail'] }}</p>
                                            <div class="mt-auto flex items-end justify-between gap-3 pt-5">
                                                <div>
                                                    <p class="font-heading text-2xl font-black">S/ {{ number_format($combo['price'], 2) }}</p>
                                                    <p class="text-xs font-bold text-electric-soft">S/ {{ number_format($combo['unit_price'], 2) }} c/u</p>
                                                </div>
                                                <span class="rounded-full bg-electric px-4 py-2 text-xs font-black uppercase tracking-wide text-white transition group-hover:bg-white group-hover:text-ink">Elegir</span>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            @endif
        </div>
    </div>
@endsection
