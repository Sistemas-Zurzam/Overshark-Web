@extends('layouts.web')

{{--
    Nosotros: Overshark como fabricante (tejido, teñido, corte, confección, acabados, calidad y empaque).
    Fotos en public/images/nosotros (ver components/nosotros/photo.blade.php); son ilustrativas,
    no se presentan como fotos de las instalaciones o del personal.
    Sin cifras, certificaciones ni condiciones comerciales no confirmadas.
--}}

@section('title', 'Nosotros | Overshark')
@section('meta_description', $c['seo_description'] ?: 'Overshark, tienda online')

@push('head')
    @vite('resources/js/nosotros.js')
@endpush

@php
    $img = fn (string $key) => \App\Support\AboutPage::imageUrl($key, $c);
    $file = fn (string $key) => \App\Support\AboutPage::IMAGES[$key][0];
    $hl = fn (array $block) => e($block['title']).(! empty($block['highlight']) ? ' <span class="text-electric">'.e($block['highlight']).'</span>' : '');
@endphp

@section('content')
    <div class="os-nos text-white">
        {{-- 1. Portada --}}
        <section class="relative isolate overflow-hidden bg-ink" aria-labelledby="nos-title">
            <x-nosotros.photo :src="$img('portada')" :file="$file('portada')" :alt="$c['hero']['image_alt']" ratio="auto" eager class="os-nos-hero__bg" />
            <div class="os-nos-hero__shade" aria-hidden="true"></div>
            <div class="relative mx-auto max-w-[1280px] px-4 pb-16 pt-16 sm:px-6 sm:pt-24 lg:px-10 lg:pb-24 lg:pt-32">
                <p class="os-nos-tag" data-nos-reveal>{{ $c['hero']['tag'] }}</p>
                <h1 id="nos-title" class="mt-4 max-w-3xl font-heading text-[2.4rem] font-black uppercase leading-[1] tracking-tight sm:text-6xl lg:text-7xl" data-nos-reveal>
                    {!! $hl($c['hero']) !!}
                </h1>
                <p class="mt-6 max-w-xl text-base leading-7 text-white/85 sm:text-lg sm:leading-8" data-nos-reveal>{{ $c['hero']['text'] }}</p>
                <a href="#proceso" class="btn-primary mt-8 inline-flex h-12 items-center gap-2 px-7 text-sm" data-nos-reveal>
                    {{ $c['hero']['button'] }}
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 5v14m-6-6 6 6 6-6"/></svg>
                </a>

                <ul class="mt-14 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-white/15 bg-white/15 lg:grid-cols-4" aria-label="Lo que hacemos">
                    @foreach ($c['hero']['attributes'] as $attribute)
                        <li class="flex items-center gap-3 bg-ink/80 px-4 py-4 text-sm font-black uppercase tracking-wide backdrop-blur sm:px-6 sm:py-5">
                            <span class="h-2 w-2 shrink-0 rounded-full bg-electric" aria-hidden="true"></span>{{ $attribute }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- 2. Nuestra esencia (claro) --}}
        <section class="os-nos-light px-4 py-20 sm:px-6 lg:px-10 lg:py-28" aria-labelledby="nos-esencia">
            <div class="mx-auto grid max-w-[1280px] items-center gap-10 lg:grid-cols-2 lg:gap-16">
                <x-nosotros.photo :src="$img('esencia')" :file="$file('esencia')" :alt="$c['esencia']['image_alt']" ratio="4 / 3" class="rounded-2xl" data-nos-reveal />
                <div data-nos-reveal>
                    <p class="os-nos-tag os-nos-tag--dark">{{ $c['esencia']['tag'] }}</p>
                    <h2 id="nos-esencia" class="mt-4 font-heading text-3xl font-black uppercase leading-[1.05] sm:text-5xl">{!! $hl($c['esencia']) !!}</h2>
                    <p class="mt-6 text-base leading-8 text-ink/75 sm:text-lg">{!! nl2br(e($c['esencia']['text'])) !!}</p>
                </div>
            </div>
        </section>

        {{-- 3. Proceso de fabricación (oscuro) --}}
        <section id="proceso" class="scroll-mt-[calc(var(--header-h)+1rem)] px-4 py-20 sm:px-6 lg:px-10 lg:py-28" aria-labelledby="nos-proceso">
            <div class="mx-auto max-w-[1280px]">
                <div class="max-w-2xl" data-nos-reveal>
                    <p class="os-nos-tag">{{ $c['proceso']['tag'] }}</p>
                    <h2 id="nos-proceso" class="mt-4 font-heading text-3xl font-black uppercase leading-[1.05] sm:text-5xl">{{ $c['proceso']['title'] }}</h2>
                    <p class="mt-4 text-base text-white/75 sm:text-lg">{{ $c['proceso']['text'] }}</p>
                </div>

                <ol class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($c['proceso']['steps'] as $step)
                        <li class="os-nos-step" data-nos-reveal>
                            <x-nosotros.photo :src="$img('paso_'.$loop->iteration)" :file="$file('paso_'.$loop->iteration)" :alt="$step['image_alt']" ratio="4 / 3" />
                            <div class="relative p-6">
                                <span class="os-nos-step__num" aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                                <h3 class="text-xl font-black uppercase"><span class="sr-only">Paso {{ $loop->iteration }}: </span>{{ $step['title'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-white/75">{{ $step['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- 4. Control de calidad (claro) --}}
        <section class="os-nos-light px-4 py-20 sm:px-6 lg:px-10 lg:py-28" aria-labelledby="nos-calidad">
            <div class="mx-auto grid max-w-[1280px] items-center gap-10 lg:grid-cols-2 lg:gap-16">
                <div class="lg:order-2" data-nos-reveal>
                    <x-nosotros.photo :src="$img('calidad')" :file="$file('calidad')" :alt="$c['calidad']['image_alt']" ratio="4 / 3" class="rounded-2xl" />
                </div>
                <div data-nos-reveal>
                    <p class="os-nos-tag os-nos-tag--dark">{{ $c['calidad']['tag'] }}</p>
                    <h2 id="nos-calidad" class="mt-4 font-heading text-3xl font-black uppercase leading-[1.05] sm:text-5xl">{!! $hl($c['calidad']) !!}</h2>
                    <p class="mt-6 text-base leading-8 text-ink/75 sm:text-lg">{!! nl2br(e($c['calidad']['text'])) !!}</p>
                    <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                        @foreach ($c['calidad']['points'] as $point)
                            <li class="flex items-start gap-3 rounded-xl border border-ink/10 bg-white px-4 py-3.5 text-sm font-bold">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-electric" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                                {{ $point }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        {{-- 5. De la producción a tu outfit (oscuro, acentos azules) --}}
        <section class="os-nos-connected px-4 py-20 sm:px-6 lg:px-10 lg:py-28" aria-labelledby="nos-conectada">
            <div class="mx-auto grid max-w-[1280px] items-center gap-12 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                <div data-nos-reveal>
                    <p class="os-nos-tag">{{ $c['conectada']['tag'] }}</p>
                    <h2 id="nos-conectada" class="mt-4 font-heading text-3xl font-black uppercase leading-[1.05] sm:text-5xl">{!! $hl($c['conectada']) !!}</h2>
                    <p class="mt-6 text-base leading-8 text-white/75 sm:text-lg">{{ $c['conectada']['text'] }}</p>
                    @if ($c['conectada']['note'])
                        <p class="mt-3 text-sm leading-6 text-white/60">{{ $c['conectada']['note'] }}</p>
                    @endif
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('web.products.search', ['orden' => 'populares']) }}" class="btn-primary inline-flex h-12 items-center px-7 text-sm">{{ $c['conectada']['button_collection'] }}</a>
                        <a href="{{ route('web.outfit') }}" class="os-nos-btn-ghost">{{ $c['conectada']['button_outfit'] }}</a>
                    </div>
                </div>

                @if ($c['conectada']['show_products'] && $products->isNotEmpty())
                    <ul class="grid grid-cols-3 gap-3 sm:gap-4" aria-label="Algunas prendas de la colección">
                        @foreach ($products as $product)
                            <li @class(['os-nos-product', 'sm:translate-y-6' => $loop->iteration === 2]) data-nos-reveal>
                                <a href="{{ route('web.products.show', $product->id) }}" class="block">
                                    <div class="aspect-[4/5] overflow-hidden rounded-xl bg-white">
                                        <img src="{{ $product->display_image }}" alt="{{ \Illuminate\Support\Str::title(mb_strtolower($product->name)) }}" class="h-full w-full object-cover transition duration-500 hover:scale-105" loading="lazy" decoding="async">
                                    </div>
                                    <p class="mt-3 text-xs font-black uppercase leading-tight sm:text-sm">{{ \Illuminate\Support\Str::title(mb_strtolower($product->name)) }}</p>
                                    <p class="mt-1 text-sm font-black text-electric-soft">S/ {{ number_format((float) $product->min_price, 2) }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        {{-- 6. Cierre --}}
        <section class="px-4 pb-24 pt-4 sm:px-6 lg:px-10" aria-labelledby="nos-cierre">
            <div class="os-nos-cta mx-auto max-w-[1280px]" data-nos-reveal>
                <h2 id="nos-cierre" class="font-heading text-3xl font-black uppercase leading-tight sm:text-5xl">{!! $hl($c['cierre']) !!}</h2>
                <p class="mx-auto mt-4 max-w-xl text-base leading-7 text-white/75 sm:text-lg">{{ $c['cierre']['text'] }}</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('web.products.search', ['orden' => 'populares']) }}" class="btn-primary inline-flex h-12 items-center px-8 text-sm">{{ $c['cierre']['button_primary'] }}</a>
                    <a href="{{ route('web.contact') }}" class="os-nos-btn-ghost">{{ $c['cierre']['button_secondary'] }}</a>
                </div>
            </div>
        </section>
    </div>
@endsection
