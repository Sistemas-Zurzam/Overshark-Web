@extends('layouts.web')

{{--
    Arma tu outfit (como en la referencia): 1) se elige uno de los combos; 2) prenda por prenda
    ("Elige tu polo", "Elige tu pantalón") se elige producto + color en tarjetas y la talla, que
    avanza al siguiente paso; 3) "Tu outfit está listo" con precio normal vs. combo e "Ir a pagar".
    Combos: modalidad "Outfit" en Admin → Combos. Lógica: resources/js/outfit-builder.js.
--}}

@section('title', 'Arma tu outfit | Overshark')

@push('head')
    @vite('resources/js/outfit-builder.js')
@endpush

@section('content')
    <div class="os-ob-page relative isolate overflow-hidden px-4 pb-20 pt-8 text-white sm:px-6 lg:px-10" data-outfit-builder data-combos='@json($combos)'>
        <div class="os-ob-glow" aria-hidden="true"><i></i><i></i><i></i></div>
        <div class="mx-auto max-w-[1320px]">
            <header>
                <h1 class="sr-only">Arma tu outfit</h1>
                <img
                    src="{{ \App\Support\OutfitFlyer::url() }}"
                    @unless (\App\Support\OutfitFlyer::isCustom())
                        srcset="{{ asset('images/outfit/flyer-arma-tu-outfit-900.webp') }} 900w, {{ asset('images/outfit/flyer-arma-tu-outfit.webp') }} 1672w"
                        sizes="(min-width: 1320px) 1320px, 100vw"
                        width="1672" height="941"
                    @endunless
                    alt="Arma tu outfit: {{ $combos->map(fn ($c) => $c['name'].' S/'.number_format($c['price'], 0))->implode(', ') }}"
                    class="w-full rounded-2xl border border-edge object-cover shadow-2xl shadow-black/40"
                    fetchpriority="high">
                <p class="mt-4 text-center text-sm text-mist">Elige tu combo, escoge color y talla, y llévalo a un precio especial.</p>
            </header>

            @if ($combos->isEmpty())
                <div class="mt-10 rounded-2xl border border-dashed border-edge-strong bg-panel/60 px-6 py-16 text-center">
                    <p class="text-lg font-black">Muy pronto publicaremos los combos de outfit.</p>
                    <a href="{{ route('web.products.search', ['orden' => 'populares']) }}" class="btn-primary mt-5 inline-flex px-6 py-3">Ver la tienda</a>
                </div>
            @else
                {{-- 1) Elegir combo --}}
                <section data-ob-view="combos" class="mt-6" aria-label="Elige tu combo">
                    <div class="grid gap-4 md:grid-cols-{{ min(3, $combos->count()) }}">
                        @foreach ($combos as $combo)
                            <button type="button" data-ob-pick="{{ $combo['id'] }}" @class(['os-ob-combo', 'is-soon' => ! $combo['available']]) @disabled(! $combo['available'])>
                                <span class="text-[11px] font-black uppercase tracking-[0.25em] text-electric-soft">{{ $combo['name'] }}</span>
                                <span class="mt-2 block font-heading text-2xl font-black uppercase sm:text-3xl">{{ $combo['tagline'] }}</span>
                                @if ($combo['detail'] !== $combo['tagline'])
                                    <span class="mt-1 block text-sm text-haze">{{ $combo['detail'] }}</span>
                                @endif
                                <span class="mt-4 block font-heading text-5xl font-black">S/{{ number_format($combo['price'], 2) }}</span>
                                @if ($combo['available'])
                                    <span class="os-ob-combo__cta">Armar este combo</span>
                                @else
                                    <span class="os-ob-combo__soon">Muy pronto · {{ implode(', ', $combo['missing']) }} en camino</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </section>

                {{-- 2) Paso a paso por prenda --}}
                <section data-ob-view="steps" class="mt-5 hidden" aria-live="polite">
                    <nav class="flex flex-wrap gap-2" aria-label="Progreso" data-ob-chips></nav>

                    <div class="os-ob-panel mt-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="font-heading text-3xl font-black uppercase" data-ob-step-title></h2>
                                <p class="mt-1 text-sm text-haze" data-ob-step-sub></p>
                            </div>
                            <button type="button" data-ob-reset class="text-sm font-black uppercase tracking-wide text-electric-soft hover:text-white">Cambiar combo</button>
                        </div>

                        <div class="relative mt-5">
                            <button type="button" data-ob-scroll="-1" class="os-ob-arrow left-0" aria-label="Ver anteriores">‹</button>
                            <div class="os-ob-track" data-ob-track></div>
                            <button type="button" data-ob-scroll="1" class="os-ob-arrow right-0" aria-label="Ver más">›</button>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <p class="text-sm font-black uppercase" data-ob-building></p>
                            <div class="flex gap-2" data-ob-picked></div>
                        </div>
                        <p class="rounded-md bg-white px-3 py-1.5 text-sm font-black text-ink" data-ob-badge></p>
                    </div>
                </section>

                {{-- 3) Outfit listo --}}
                <section data-ob-view="ready" class="mt-5 hidden">
                    <nav class="flex flex-wrap gap-2" aria-label="Progreso" data-ob-chips></nav>
                    <form method="POST" data-ob-form class="os-ob-panel mt-4 text-center">
                        @csrf
                        <div data-ob-inputs></div>
                        <h2 class="font-heading text-4xl font-black uppercase">Tu outfit está listo</h2>
                        <p class="mt-1 text-sm text-haze">Revisa tu selección antes de pagar</p>
                        <div class="mt-6 flex flex-wrap justify-center gap-4" data-ob-summary></div>
                        <p class="mt-6 text-sm text-haze line-through" data-ob-normal></p>
                        <p class="mt-1 text-lg">Total combo: <b class="font-heading text-4xl font-black text-electric-soft" data-ob-total></b></p>
                        <div class="mt-6 flex flex-wrap justify-center gap-3">
                            <button type="button" data-ob-reset class="os-ob-btn-ghost">Cambiar combo</button>
                            <button type="submit" class="btn-primary h-12 px-10 text-sm">Ir a pagar</button>
                        </div>
                    </form>
                </section>
            @endif

            {{-- Cómo funciona --}}
            <section class="mt-16 grid gap-8 lg:grid-cols-[minmax(240px,0.6fr)_minmax(0,2fr)] lg:items-center" aria-labelledby="ob-how">
                <div>
                    <p class="flex items-center gap-2 text-xs font-black uppercase tracking-[0.25em] text-mist"><span class="h-1.5 w-1.5 rounded-full bg-electric" aria-hidden="true"></span>Overshark / Cómo funciona</p>
                    <h2 id="ob-how" class="mt-4 font-heading text-4xl font-black uppercase leading-[0.95]">Armar tu outfit es <span class="text-electric">así de fácil.</span></h2>
                    <p class="mt-4 max-w-sm text-base leading-7 text-mist">Tu estilo, tus prendas y un precio especial. Sigue estos pasos y crea una combinación hecha para ti.</p>
                </div>
                <ol class="grid gap-4 md:grid-cols-3">
                    @foreach ([
                        ['Elige tu combo', 'Encuentra el combo que va contigo: '.$combos->pluck('name')->map(fn ($n) => str_replace('Combo ', '', $n))->implode(', ', ' o ').'.', 'Empieza por tu estilo', 'os-step-card--light'],
                        ['Ármalo', 'Elige tus prendas, su color y su talla para terminar tu combo.', 'El match perfecto', 'os-step-card--dark'],
                        ['Págalo', 'Revisa tu outfit, verifica las opciones y realiza tu compra.', 'Es tu momento', 'os-step-card--accent'],
                    ] as [$title, $copy, $foot, $variant])
                        <li class="os-step-card {{ $variant }}">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-lg font-black uppercase">Paso {{ sprintf('%02d', $loop->iteration) }}</p>
                                    <p class="os-step-card__title">{{ sprintf('%02d', $loop->iteration) }} / {{ $title }}</p>
                                </div>
                                <span class="os-step-card__num" aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                            </div>
                            <p class="mt-3 text-sm leading-6 opacity-85">{{ $copy }}</p>
                            <p class="os-step-card__foot mt-auto">{{ $foot }} <span aria-hidden="true">↗</span></p>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
@endsection
