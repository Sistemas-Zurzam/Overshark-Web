@extends('layouts.web')

{{--
    Portada de Bravos (diseño Figma "01 · Home Bravos · Black & White"): hero con tipografía monumental,
    banda de marca, favoritos (productos de la marca con stock), editorial de poleras, pantalones,
    "Bravos para todos", confianza y otras tiendas. Paleta blanco y negro con acento amarillo #ffdb00.
    Los productos son los de la marca Bravos en el admin o, sin marca asignada, los de la empresa BRAVOS de Zazu.
--}}

@section('title', 'Bravos | Overshark')
@section('body-class', 'theme-neon theme-bravos')

@php
    $shop = fn (array $params = []) => route('web.products.search', ['marca' => $brandPage->name] + $params);
    $img = fn (string $file) => \App\Support\SiteContent::asset('images/bravos/'.$file);
    $btn = 'os-focus inline-flex items-center justify-center p-[18px] text-[13px] font-bold uppercase leading-[1.3] transition';
@endphp

@section('content')
    <div class="os-hh os-bravos">
        {{-- Hero · tipografía monumental --}}
        <section class="relative isolate overflow-hidden bg-[#050505] text-white" aria-labelledby="bravos-title">
            <img src="{{ $img('hero.webp') }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover" fetchpriority="high">
            <div class="relative mx-auto flex max-w-[1440px] flex-col px-4 pb-10 pt-8 sm:px-8 lg:block lg:aspect-[1440/740] lg:p-0">
                <p class="text-[14px] font-bold leading-[1.1] lg:absolute lg:left-[6.67%] lg:top-[5.4%]">BRAVOS / URBAN CLOTHING</p>
                <h1 id="bravos-title" class="os-bravos-mega mt-4 text-center lg:absolute lg:left-[9.4%] lg:top-[13.5%] lg:mt-0 lg:text-left">BRAVOS</h1>
                <img src="{{ $img('modelo.webp') }}" alt="Modelo con polera Neru negra y Comfort Pant" class="relative z-10 mx-auto -mt-[16vw] w-[78%] max-w-[420px] lg:absolute lg:left-[31.74%] lg:top-[0.54%] lg:mt-0 lg:h-[106.6%] lg:w-[36.5%] lg:max-w-none lg:object-contain">

                <div class="relative z-20 -mt-24 flex flex-col gap-4 lg:absolute lg:left-[6.67%] lg:top-[57.4%] lg:mt-0 lg:w-[320px]">
                    <p class="text-[30px] font-extrabold leading-[1.2] text-[#ffdb00] sm:text-[36px]">TU ESTILO.<br>TUS REGLAS.</p>
                    <p class="text-[17px] leading-[1.2]">Siluetas amplias.<br>Actitud sin límites.</p>
                </div>
                <div class="relative z-20 mt-8 flex flex-col items-start gap-5 lg:static lg:mt-0">
                    <p class="text-[26px] font-extrabold leading-[1.1] sm:text-[30px] lg:absolute lg:left-[71.8%] lg:top-[59%]">NERU +<br>COMFORT PANT</p>
                    <a href="#favoritos" class="{{ $btn }} h-[54px] w-full max-w-[290px] bg-[#ffdb00] font-extrabold text-[#050505] hover:bg-white lg:absolute lg:left-[71.8%] lg:top-[74.3%] lg:w-[20.1%]">EXPLORAR BRAVOS →</a>
                </div>
                <p class="relative z-20 mt-8 flex h-10 w-full max-w-[334px] items-center justify-center bg-[#ffdb00] text-[13px] font-extrabold text-[#050505] lg:absolute lg:left-[6.67%] lg:top-[86.2%] lg:mt-0">{{ mb_strtoupper(\App\Support\SiteContent::t('Envío desde {desde} a todo el Perú')) }}</p>
            </div>
        </section>

        {{-- Banda de marca --}}
        <div class="overflow-hidden bg-[#050505] p-6 text-white" aria-hidden="true">
            <p class="flex flex-wrap justify-center gap-x-8 gap-y-1 whitespace-nowrap text-[15px] font-extrabold leading-[1.3] sm:gap-x-[54px] sm:text-[22px]">
                <span>BRAVOS</span><span>URBAN CLOTHING</span><span>TU ESTILO. TUS REGLAS.</span><span>BRAVOS</span>
            </p>
        </div>

        {{-- Los favoritos de la calle --}}
        <section id="favoritos" class="os-bravos-wrap scroll-mt-[calc(var(--header-h)+16px)] py-16" aria-labelledby="bravos-favs">
            <div class="flex flex-col items-start justify-between gap-6 sm:flex-row">
                <h2 id="bravos-favs" class="text-[32px] font-extrabold leading-[1.3] text-[#0a0a0a] sm:text-[40px]">LOS FAVORITOS<br>DE LA CALLE</h2>
                <a href="{{ $shop() }}" class="{{ $btn }} w-full bg-[#0a0a0a] text-white hover:bg-[#333] sm:w-[280px]">VER TODA LA COLECCIÓN →</a>
            </div>
            @if ($favorites->isEmpty())
                <p class="mt-8 bg-[#f5f5f5] p-6 text-[16px] text-[#595959]">Estamos reponiendo la colección. Muy pronto vuelven los favoritos de Bravos.</p>
            @else
                <ul class="mt-8 grid grid-cols-2 gap-x-4 gap-y-8 lg:grid-cols-4 lg:gap-8">
                    @foreach ($favorites as $product)
                        <li>@include('web.partials.hh-product-card', ['photoClass' => 'aspect-[304/314]'])</li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Editorial · siluetas oversize --}}
        <section class="grid bg-[#0a0a0a] lg:grid-cols-2" aria-labelledby="bravos-fit">
            <div class="flex flex-col items-start justify-center gap-7 px-4 py-14 sm:px-8 lg:p-[72px]">
                <p class="text-[13px] font-bold leading-[1.3] text-[#f0f0f0]">EL FIT HABLA POR TI</p>
                <h2 id="bravos-fit" class="text-[40px] font-extrabold leading-[1.3] text-white sm:text-[54px]">MÁS<br>VOLUMEN.<br>MÁS ACTITUD.</h2>
                <p class="max-w-[550px] text-[18px] leading-[1.3] text-[#f0f0f0] sm:text-[20px]">Neru y Boxy Fit: siluetas amplias que hacen suyo cualquier look.</p>
                <a href="{{ $shop(['categoria' => 'poleras']) }}" class="{{ $btn }} w-full bg-white text-[#0a0a0a] hover:bg-[#ffdb00] sm:w-[280px]">DESCUBRIR POLERAS →</a>
            </div>
            <div class="h-[420px] bg-[#f0f0f0] sm:h-[560px] lg:h-[670px]">
                <img src="{{ $img('boxy-fit.webp') }}" alt="Polera Boxy Fit" class="h-full w-full object-contain" loading="lazy">
            </div>
        </section>

        {{-- Pantalones · elige tu silueta --}}
        <section class="os-bravos-wrap py-16" aria-labelledby="bravos-pants">
            <h2 id="bravos-pants" class="text-[32px] font-extrabold leading-[1.3] text-[#0a0a0a] sm:text-[42px]">BAJA EL RITMO.<br>SUBE EL ESTILO.</h2>
            <div class="mt-9 grid gap-8 lg:grid-cols-2">
                @foreach ([
                    ['COMFORT PANT', 'Comodidad que sigue tu ritmo.', 'comfort-pant.webp', 'bg-[#f0f0f0]', 'comfort'],
                    ['BRATZ', 'Tu silueta urbana, a tu manera.', 'bratz.webp', 'bg-[#ddd]', 'bratz'],
                ] as [$pantName, $pantText, $pantImage, $pantBg, $pantSearch])
                    <article class="{{ $pantBg }} flex flex-col">
                        <img src="{{ $img($pantImage) }}" alt="{{ \Illuminate\Support\Str::title(mb_strtolower($pantName)) }}" class="h-[340px] w-full object-contain sm:h-[440px]" loading="lazy">
                        <div class="flex flex-col items-start gap-4 p-6 sm:p-8">
                            <h3 class="text-[28px] font-extrabold leading-[1.3] text-[#0a0a0a] sm:text-[32px]">{{ $pantName }}</h3>
                            <p class="text-[17px] leading-[1.3] text-[#0a0a0a]">{{ $pantText }}</p>
                            <a href="{{ route('web.products.search', ['q' => $pantSearch]) }}" class="{{ $btn }} w-full bg-[#0a0a0a] text-white hover:bg-[#333] sm:w-[250px]">VER PANTALONES →</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Bravos para todos --}}
        <section class="bg-[#0a0a0a]" aria-labelledby="bravos-all">
            <div class="os-bravos-wrap flex flex-col gap-10 py-16 lg:flex-row lg:items-start lg:justify-between lg:gap-16">
                <div class="flex flex-col items-start gap-6">
                    <h2 id="bravos-all" class="text-[34px] font-extrabold leading-[1.3] text-white sm:text-[48px]">MISMA CALLE.<br>TU PROPIO ESTILO.</h2>
                    <p class="max-w-[670px] text-[18px] leading-[1.3] text-[#ccc] sm:text-[20px]">Comfort Pant y Bratz también están disponibles para mujer.</p>
                    <a href="{{ route('web.products.search', ['linea' => 'mujer']) }}" class="{{ $btn }} w-full bg-white text-[#050505] hover:bg-[#ffdb00] sm:w-[290px]">EXPLORAR PARA MUJER →</a>
                </div>
                <p class="shrink-0 bg-white p-8 text-[40px] font-extrabold leading-[1.3] text-[#050505] sm:text-[52px] lg:w-[528px]" aria-hidden="true">COMFORT<br>BRATZ<br>BRAVOS</p>
            </div>
        </section>

        {{-- Compra con confianza --}}
        <section class="os-bravos-wrap py-16" aria-labelledby="bravos-trust">
            <h2 id="bravos-trust" class="text-[28px] font-extrabold leading-[1.3] text-[#0a0a0a] sm:text-[36px]">TU LOOK. SIN COMPLICACIONES.</h2>
            <ul class="mt-8 grid gap-8 lg:grid-cols-3">
                @foreach (array_filter([
                    ['ENVÍOS A TODO EL PERÚ', \App\Support\SiteContent::t('Lima: delivery {lima}. Agencia Shalom {agencia}. Recojo en almacén gratis.')],
                    ['PAGA A TU MANERA', 'Yape, Plin, transferencia bancaria o pago contraentrega.'],
                    $modelCount > 0 ? ['ELIGE TU COMBINACIÓN', ($modelCount === 1 ? 'Un modelo' : $modelCount.' modelos').($colorCount > 1 ? ' y una paleta de '.$colorCount.' colores' : '').' para tu estilo.'] : null,
                ]) as [$perkTitle, $perkText])
                    <li class="flex flex-col gap-4 bg-[#f5f5f5] p-6">
                        <h3 class="text-[19px] font-extrabold leading-[1.3] text-[#0a0a0a]">{{ $perkTitle }}</h3>
                        <p class="text-[16px] leading-[1.3] text-[#595959]">{{ $perkText }}</p>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Familia Overshark --}}
        <section class="bg-[#f4f4f4]" aria-labelledby="bravos-more">
            <div class="os-bravos-wrap flex flex-col gap-6 py-16">
                <h2 id="bravos-more" class="text-[26px] font-extrabold leading-[1.3] text-[#0a0a0a] sm:text-[30px]">SIGUE EXPLORANDO</h2>
                <div class="flex flex-col gap-4 sm:flex-row sm:gap-8">
                    <a href="{{ route('web.home', ['linea' => 'hombre']) }}" class="{{ $btn }} bg-[#0a0a0a] text-white hover:bg-[#333] sm:w-[400px]">OVERSHARK HOMBRE →</a>
                    <a href="{{ route('web.home', ['linea' => 'mujer']) }}" class="{{ $btn }} bg-[#0a0a0a] text-white hover:bg-[#333] sm:w-[400px]">OVERSHARK GIRLS →</a>
                </div>
            </div>
        </section>
    </div>
@endsection
