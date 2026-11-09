@extends('layouts.web')

{{--
    Arma tu outfit de Bravos (diseño Figma "03 · Arma tu Outfit Bravos"): eliges una polera y un pantalón
    de la marca (con su color y talla) y "Tu outfit" suma sus precios individuales; el look se agrega al
    carrito con web.cart.look. Las opciones las pinta resources/js/bravos-outfit.js desde $steps.
--}}

@section('title', 'Arma tu outfit | Bravos')
@section('body-class', 'theme-neon theme-bravos')

@push('head')
    @vite('resources/js/bravos-outfit.js')
@endpush

@php
    $ready = $steps['tops'] !== [] && $steps['bottoms'] !== [];
@endphp

@section('content')
    <div class="os-hh os-bravos">
        <header class="bg-[#0a0a0a] text-white">
            <div class="os-bravos-wrap flex flex-col items-start gap-4 py-12 leading-[1.35] lg:py-14">
                <p class="text-[13px] font-bold text-[#b3b3b3]">BRAVOS / ESTILISTA URBANO</p>
                <h1 class="text-[36px] font-extrabold sm:text-[46px]">ARMA TU OUTFIT.</h1>
                <p class="text-[16px] text-[#ccc] sm:text-[18px]">Elige una polera y un pantalón. Tu estilo, tus reglas.</p>
            </div>
        </header>

        @if (! $ready)
            <section class="os-bravos-wrap py-16">
                <div class="flex flex-col items-start gap-4 bg-[#f5f5f5] p-8">
                    <p class="text-[20px] font-extrabold text-[#0a0a0a]">Estamos reponiendo la colección.</p>
                    <p class="text-[15px] text-[#595959]">Muy pronto podrás armar tu look con poleras y pantalones Bravos.</p>
                    <a href="{{ route('web.products.search', ['marca' => $brandPage->name]) }}" class="os-focus bg-[#ffdb00] p-[18px] text-[13px] font-extrabold text-[#0a0a0a] hover:bg-[#ffe640]">VER TIENDA BRAVOS</a>
                </div>
            </section>
        @else
            <div class="os-bravos-wrap grid gap-10 py-10 lg:grid-cols-[minmax(0,1fr)_424px] lg:gap-12 lg:py-16"
                data-bravos-outfit data-steps='@json($steps)'>
                <div class="flex min-w-0 flex-col gap-7">
                    @foreach (['tops' => '01 / ELIGE TU TOP', 'bottoms' => '02 / ELIGE TU PANTALÓN'] as $key => $label)
                        <section class="flex flex-col gap-7" aria-labelledby="bo-{{ $key }}">
                            <h2 id="bo-{{ $key }}" class="text-[22px] font-extrabold leading-[1.35] text-[#0a0a0a] sm:text-[24px]">{{ $label }}</h2>
                            <ul class="grid gap-4 sm:grid-cols-2 sm:gap-8" data-bo-options="{{ $key }}"></ul>
                        </section>
                    @endforeach
                    <noscript><p class="text-[14px] text-[#595959]">Activa JavaScript para armar tu look, o elige tus prendas en la <a class="underline" href="{{ route('web.products.search', ['marca' => $brandPage->name]) }}">tienda Bravos</a>.</p></noscript>
                </div>

                <aside class="flex flex-col gap-6 self-start bg-[#f2f2f2] p-6 sm:p-7 lg:sticky lg:top-[calc(var(--header-h)+24px)]" aria-labelledby="bo-look">
                    <h2 id="bo-look" class="text-[25px] font-extrabold leading-[1.35] text-[#0a0a0a]">TU OUTFIT</h2>
                    @if ($errors->any())
                        <p class="bg-white px-3 py-2 text-[13px] font-bold text-[#c0392b]" role="alert">{{ $errors->first() }}</p>
                    @endif
                    <ul class="flex flex-col gap-6" data-bo-summary aria-live="polite"></ul>
                    <div class="flex flex-col gap-1 leading-[1.35]">
                        <p class="text-[14px] text-[#595959]" data-bo-count>Subtotal · 2 prendas</p>
                        <p class="text-[30px] font-extrabold text-[#0a0a0a]" data-bo-total>S/ 0.00</p>
                        <p class="text-[13px] text-[#595959]">{{ \App\Support\SiteContent::t('Envío desde {desde} a todo el Perú.') }}</p>
                    </div>
                    <form action="{{ route('web.cart.look') }}" method="POST" class="flex flex-col gap-3" data-bo-form>
                        @csrf
                        <div data-bo-inputs></div>
                        <button type="submit" class="os-focus bg-[#ffdb00] p-[18px] text-[13px] font-extrabold leading-[1.35] text-[#0a0a0a] transition hover:bg-[#ffe640] disabled:cursor-not-allowed disabled:opacity-40" data-bo-submit>AGREGAR LOOK AL CARRITO</button>
                        <p class="text-[12px] leading-[1.35] text-[#666]">Precio individual de las prendas. No incluye promociones de combos.</p>
                    </form>
                    <a href="{{ route('web.products.search', ['marca' => $brandPage->name]) }}" class="os-focus border border-[#0a0a0a] p-[18px] text-center text-[13px] font-extrabold leading-[1.35] text-[#0a0a0a] transition hover:bg-[#0a0a0a] hover:text-white">VER TIENDA BRAVOS</a>
                </aside>
            </div>
        @endif

        <section class="bg-[#0a0a0a] text-white" aria-label="Confianza">
            <ul class="os-bravos-wrap flex flex-col gap-3 py-8 text-[15px] font-bold leading-[1.35] sm:flex-row sm:flex-wrap sm:gap-x-12 sm:text-[16px] lg:py-10">
                <li>ENVÍOS A TODO EL PERÚ</li>
                <li>YAPE · PLIN · CONTRAENTREGA</li>
                <li>BRAVOS / URBAN CLOTHING</li>
            </ul>
        </section>
    </div>
@endsection
