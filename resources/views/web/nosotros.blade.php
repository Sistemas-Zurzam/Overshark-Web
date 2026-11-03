@extends('layouts.web')

{{--
    Nosotros (diseño Figma "04 · Nosotros · Overshark"): portada, nuestra historia, nuestro ADN,
    proceso de fabricación (del hilo a la prenda), datos clave, nuestras marcas y comunidad.
    Textos y fotos en Admin → Página Nosotros (App\Support\AboutPage). El año de fundación, el
    párrafo de origen, la cuenta y los enlaces a redes solo se muestran cuando están completos.
--}}

@section('title', 'Nosotros | Overshark')
@section('meta_description', $c['seo_description'] ?: 'Overshark, tienda online')

@push('head')
    @vite('resources/js/nosotros.js')
@endpush

@php
    $img = fn (string $key) => \App\Support\AboutPage::imageUrl($key, $c);
    // Desde Bravos (?marca=bravos): paleta negra y amarilla (.os-hh--bravos) y títulos en Manrope.
    // Línea mujer: paleta Overshark Girls (.os-hh--girls). Sin portada propia, cada una usa su foto de portada.
    $bravos = ($brandPage ?? null)?->slug === 'bravos';
    $girls = ! $bravos && session('storefront.line') === 'mujer';
    $portada = empty($c['images']['portada'])
        ? ($bravos ? asset('images/bravos/hero.webp') : ($girls ? asset('images/home-mujer/hero.webp') : $img('portada')))
        : $img('portada');
    $display = fn (string $hh, string $bv) => $bravos ? 'font-extrabold uppercase tracking-[-0.01em] '.$bv : 'os-hh-display '.$hh;
    $file = fn (string $key) => \App\Support\AboutPage::IMAGES[$key][0];
    $paragraphs = fn (?string $text) => collect(preg_split('/\R\s*\R/', trim((string) $text)))->filter();
    $brands = [
        ['key' => 'marca_overshark', 'logo' => 'home-hombre/logo-overshark.png', 'name' => 'Overshark', 'label' => 'Para él', 'arrow' => 'bg-[#061a2e]', 'url' => route('web.home', ['linea' => 'hombre'])],
        ['key' => 'marca_girls', 'logo' => 'home-hombre/logo-girls.png', 'name' => 'Overshark Girls', 'label' => 'Para ella', 'arrow' => 'bg-[#8c2155]', 'url' => route('web.home', ['linea' => 'mujer'])],
        ['key' => 'marca_bravos', 'logo' => 'home-hombre/logo-bravos.svg', 'name' => 'Bravos', 'label' => 'Urban clothing', 'arrow' => 'bg-[#141414]', 'url' => $bravosBrand ? route('web.brands.show', $bravosBrand) : route('web.products.search', ['q' => 'bravos'])],
    ];
    $socials = collect(['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'facebook' => 'Facebook'])
        ->map(fn (string $label, string $key) => ['label' => $label, 'url' => $c['comunidad'][$key] ?? null])
        ->filter(fn (array $social) => filled($social['url']));
@endphp

@if ($bravos)
    @section('body-class', 'theme-neon theme-bravos')
@endif

@section('content')
    <div @class(['os-hh os-nos-page', 'os-hh--girls' => $girls, 'os-hh--bravos' => $bravos])>
        {{-- Portada --}}
        <section class="relative isolate flex min-h-[460px] flex-col items-center justify-center gap-5 overflow-hidden bg-[var(--shop-deep)] px-4 py-20 text-center text-white lg:min-h-[560px]" aria-labelledby="nos-title">
            @if ($portada)
                <img src="{{ $portada }}" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover" fetchpriority="high">
            @endif
            <div class="absolute inset-0 -z-10 [background:var(--nos-veil)]" aria-hidden="true"></div>
            @if ($bravos)
                <img src="{{ asset('images/nav/logo-bravos.svg') }}" alt="" width="80" height="26" class="h-8 w-auto">
            @else
                <img src="{{ asset('images/nosotros/'.($girls ? 'aleta-girls.svg' : 'aleta.svg')) }}" alt="" width="40" height="40">
            @endif
            <p class="text-[13px] font-extrabold uppercase tracking-[4px] text-[var(--hh-bright)]">{{ $c['hero']['tag'] }}</p>
            <h1 id="nos-title" class="{{ $display('text-[64px] leading-[0.95] sm:text-[104px] lg:text-[150px] lg:leading-[140px]', 'text-[40px] leading-[1.05] sm:text-[64px] lg:text-[88px]') }}">{{ $c['hero']['title'] }}</h1>
            <p class="max-w-[640px] text-[17px] font-medium leading-[27px] text-[var(--shop-intro)] sm:text-[20px] sm:leading-[30px]">{{ $c['hero']['text'] }}</p>
        </section>

        {{-- Nuestra historia --}}
        <section class="os-hh-wrap grid items-center gap-12 py-16 lg:grid-cols-[560px_minmax(0,1fr)] lg:gap-[72px] lg:py-[100px]" aria-labelledby="nos-historia">
            <div class="relative mx-auto aspect-square w-full max-w-[560px]" data-nos-reveal>
                <div class="absolute left-0 top-0 w-[68%]">
                    <x-nosotros.photo :src="$img('historia_1')" :file="$file('historia_1')" :alt="$c['historia']['image_alt_1']" ratio="380 / 500" :gray="false" />
                </div>
                <div class="absolute bottom-0 right-0 w-[43%] border-8 border-white bg-white">
                    <x-nosotros.photo :src="$img('historia_2')" :file="$file('historia_2')" :alt="$c['historia']['image_alt_2']" ratio="224 / 284" :gray="false" />
                </div>
                @if (filled($c['historia']['year']))
                    <p class="absolute left-[4%] top-[4%] flex flex-col bg-[var(--hh-accent)] px-[18px] py-[14px] text-white">
                        <span class="text-[11px] font-extrabold uppercase tracking-[2px]">Desde</span>
                        <span class="{{ $display('text-[40px] leading-[40px]', 'text-[26px] leading-[1.15]') }}">{{ $c['historia']['year'] }}</span>
                    </p>
                @endif
            </div>
            <div class="flex flex-col items-start gap-[22px]" data-nos-reveal>
                <p class="text-[13px] font-extrabold uppercase tracking-[3px] text-[var(--hh-accent)]">{{ $c['historia']['tag'] }}</p>
                <h2 id="nos-historia" class="{{ $display('text-[44px] leading-[44px] sm:text-[64px] sm:leading-[62px]', 'text-[32px] leading-[1.15] sm:text-[44px]') }}">{{ $c['historia']['title'] }}</h2>
                @foreach ($paragraphs($c['historia']['text'])->concat($paragraphs($c['historia']['origen'])) as $paragraph)
                    <p class="text-[17px] font-medium leading-[28px] text-[#545966]">{{ $paragraph }}</p>
                @endforeach
                <a href="{{ $bravos ? route('web.products.search', ['marca' => 'BRAVOS']) : route('web.products.search') }}" class="os-hh-btn os-focus bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)]">{{ $c['historia']['button'] }} →</a>
            </div>
        </section>

        {{-- Nuestro ADN --}}
        <section class="bg-[var(--shop-deep)] py-16 text-white lg:py-[88px]" aria-labelledby="nos-adn">
            <div class="os-hh-wrap">
                <header class="flex flex-col justify-between gap-2 border-b-2 border-white/20 pb-5 sm:flex-row sm:items-end">
                    <h2 id="nos-adn" class="{{ $display('text-[48px] leading-none sm:text-[64px] sm:leading-[62px]', 'text-[34px] leading-[1.1] sm:text-[44px]') }}">{{ $c['adn']['title'] }}</h2>
                    <p class="text-[16px] font-medium text-[var(--hh-muted)]">{{ $c['adn']['text'] }}</p>
                </header>
                <ul class="mt-10 grid gap-4 md:grid-cols-3">
                    @foreach ($c['adn']['items'] as $item)
                        <li class="flex flex-col gap-4 border border-white/10 bg-[var(--hh-card)] px-8 pb-9 pt-8" data-nos-reveal>
                            <span class="{{ $display('text-[72px] leading-[68px] text-[var(--hh-bright)]', 'text-[48px] leading-none text-[var(--hh-bright)]') }}" aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <h3 class="{{ $display('text-[40px] leading-[40px]', 'text-[26px] leading-[1.15]') }}">{{ $item['title'] }}</h3>
                            <p class="text-[15px] font-medium leading-6 text-[var(--hh-muted)]">{{ $item['text'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Proceso de fabricación --}}
        <section id="proceso" class="os-hh-wrap scroll-mt-[calc(var(--header-h)+1rem)] py-16 lg:py-[88px]" aria-labelledby="nos-proceso">
            <header class="os-hh-head">
                <div>
                    <p class="os-hh-eyebrow">{{ $c['proceso']['tag'] }}</p>
                    <h2 id="nos-proceso" class="os-hh-title">{{ $c['proceso']['title'] }}</h2>
                    <p class="os-hh-sub">{{ $c['proceso']['text'] }}</p>
                </div>
            </header>
            <ol class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($c['proceso']['steps'] as $step)
                    <li class="flex flex-col border border-[var(--shop-line)] bg-white" data-nos-reveal>
                        {{-- Sin foto subida, la tarjeta muestra solo el texto (el aviso de foto pendiente queda en el admin). --}}
                        @if ($stepPhoto = $img('paso_'.$loop->iteration))
                            <x-nosotros.photo :src="$stepPhoto" :alt="$step['image_alt']" ratio="4 / 3" :gray="false" />
                        @endif
                        <div class="flex flex-col gap-2 p-6">
                            <span class="{{ $display('text-[40px] leading-none text-[var(--hh-accent)]', 'text-[28px] leading-none text-[var(--hh-accent)]') }}" aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <h3 class="text-[18px] font-extrabold"><span class="sr-only">Paso {{ $loop->iteration }}: </span>{{ $step['title'] }}</h3>
                            <p class="text-[14px] font-medium leading-[22px] text-[#545966]">{{ $step['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Datos clave --}}
        <section class="bg-[var(--hh-soft)] py-12 lg:py-[72px]" aria-label="Datos clave">
            <ul class="os-hh-wrap grid grid-cols-2 gap-y-8 lg:grid-cols-4">
                @foreach ($c['datos'] as $dato)
                    <li @class(['flex flex-col gap-1.5', 'border-l border-[var(--shop-line)] pl-5 sm:pl-8' => $loop->index % 2 === 1, 'lg:border-l lg:border-[var(--shop-line)] lg:pl-8' => $loop->index === 2])>
                        <span class="{{ $display('text-[40px] leading-none text-[var(--hh-accent)] sm:text-[64px] sm:leading-[62px]', 'text-[30px] leading-none text-[var(--hh-accent)] sm:text-[44px]') }}">{{ $dato['value'] }}</span>
                        <span class="text-[14px] font-bold sm:text-[15px]">{{ $dato['label'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Nuestras marcas --}}
        <section class="os-hh-wrap py-16 lg:py-[100px]" aria-labelledby="nos-marcas">
            <header class="os-hh-head">
                <div>
                    <p class="os-hh-eyebrow">{{ $c['marcas']['tag'] }}</p>
                    <h2 id="nos-marcas" class="os-hh-title">{{ $c['marcas']['title'] }}</h2>
                    <p class="os-hh-sub">{{ $c['marcas']['text'] }}</p>
                </div>
            </header>
            <ul class="mt-8 grid gap-4 md:grid-cols-3">
                @foreach ($brands as $brand)
                    <li data-nos-reveal>
                        <a href="{{ $brand['url'] }}" class="os-focus group block">
                            <span class="block aspect-[403/460] overflow-hidden bg-[var(--hh-soft)]">
                                @if ($photo = $img($brand['key']))
                                    <img src="{{ $photo }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                @endif
                            </span>
                            <span class="flex items-center justify-between border border-t-0 border-[var(--shop-line)] bg-white px-6 py-[22px]">
                                <span class="flex flex-col items-start gap-2">
                                    <img src="{{ asset('images/'.$brand['logo']) }}" alt="{{ $brand['name'] }}" class="h-[26px] w-auto max-w-[130px] object-contain">
                                    <span class="text-[13px] font-semibold text-[#545966]">{{ $brand['label'] }}</span>
                                </span>
                                <span class="grid h-11 w-11 place-items-center text-[18px] font-extrabold text-white transition group-hover:translate-x-1 {{ $brand['arrow'] }}" aria-hidden="true">→</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Comunidad --}}
        <section class="os-hh-wrap pb-16 lg:pb-[100px]" aria-labelledby="nos-comunidad">
            <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
                <div class="flex flex-col gap-1.5">
                    <h2 id="nos-comunidad" class="{{ $display('text-[36px] leading-none sm:text-[48px] sm:leading-[48px]', 'text-[26px] leading-[1.15] sm:text-[34px]') }}">{{ $c['comunidad']['title'] }}</h2>
                    <p class="text-[15px] font-medium text-[#545966]">
                        @if (filled($c['comunidad']['handle']))
                            Etiquétanos con {{ $c['comunidad']['handle'] }} cuando estrenes tu look.
                        @else
                            Comparte tu look Overshark y muéstralo con orgullo.
                        @endif
                    </p>
                </div>
                @if ($socials->isNotEmpty())
                    <ul class="flex flex-wrap gap-2.5">
                        @foreach ($socials as $social)
                            <li><a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="os-focus inline-flex border-[1.5px] border-[#0b0f14] px-[18px] py-3 text-[12px] font-extrabold uppercase tracking-[1px] transition hover:bg-[#0b0f14] hover:text-white">{{ $social['label'] }}&nbsp; ↗</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <ul class="mt-7 grid grid-cols-3 gap-2 md:grid-cols-6" aria-hidden="true">
                @foreach (range(1, 6) as $i)
                    <li class="aspect-square overflow-hidden bg-[var(--hh-soft)]"><img src="{{ asset("images/nosotros/comunidad-{$i}.webp") }}" alt="" class="h-full w-full object-cover" loading="lazy"></li>
                @endforeach
            </ul>
        </section>

    </div>
@endsection
