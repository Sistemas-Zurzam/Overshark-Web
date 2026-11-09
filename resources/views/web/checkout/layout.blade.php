@extends('layouts.web')

{{--
    Layout del checkout (Figma "04 · Compra compartida"): barra oscura "OVERSHARK · COMPRA SEGURA" sin menú,
    pasos 01 Carrito / 02 Datos y entrega / 03 Pago y contenido sobre fondo blanco. Cada pantalla define
    @section('checkout') y, si corresponde, $step (1, 2 o 3).
--}}

@section('body-class', 'theme-neon os-checkout-page')
@section('main-class', 'pt-[88px]')

@section('header-bar')
    <div class="bg-[#061c2d]">
        <div class="os-bravos-wrap flex h-[88px] items-center justify-between">
            <a href="{{ route('web.home') }}" class="os-focus text-[20px] text-white [font-family:'Archivo_Black',sans-serif] sm:text-[23px]" aria-label="Overshark, volver a la tienda">OVERSHARK</a>
            <p class="flex items-center gap-2 text-[12px] font-bold text-[#afc5d7]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="1.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                COMPRA SEGURA
            </p>
        </div>
    </div>
@endsection

@section('footer')
    <footer class="border-t border-[#dae0e5] bg-white">
        <div class="os-bravos-wrap flex flex-col gap-2 py-6 text-[12px] text-[#65717c] sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} Overshark. Precios en soles, impuestos incluidos.</p>
            <p class="flex gap-4">
                <a href="{{ route('web.contact') }}#faq" class="hover:text-[#0b1015]">Ayuda</a>
                <a href="{{ route('web.terms') }}" data-legal-open="terms" class="hover:text-[#0b1015]">Términos y condiciones</a>
                <a href="{{ route('web.privacy') }}" data-legal-open="privacy" class="hover:text-[#0b1015]">Política de privacidad</a>
                <a href="{{ route('web.claims.create') }}" class="hover:text-[#0b1015]">Libro de Reclamaciones</a>
            </p>
        </div>
    </footer>
@endsection

@section('content')
    <div class="os-hh os-checkout">
        <div class="os-bravos-wrap flex flex-col gap-6 py-10 lg:py-16">
            @isset($step)
                <nav aria-label="Pasos de la compra" class="text-[12px] font-bold leading-[1.45] text-[#2467cf]">
                    <ol class="flex flex-wrap items-center gap-x-4 gap-y-1">
                        @foreach ([1 => ['CARRITO', route('web.cart.index')], 2 => ['DATOS Y ENTREGA', route('web.checkout.personal')], 3 => ['PAGO', null]] as $number => [$label, $url])
                            <li class="flex items-center gap-4">
                                @if ($url && $number < $step)
                                    <a href="{{ $url }}" class="os-focus hover:underline">0{{ $number }}&nbsp; {{ $label }}</a>
                                @else
                                    <span @class(['underline underline-offset-4' => $number === $step, 'opacity-50' => $number > $step]) @if ($number === $step) aria-current="step" @endif>0{{ $number }}&nbsp; {{ $label }}</span>
                                @endif
                                @unless ($loop->last)<span aria-hidden="true">/</span>@endunless
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endisset

            @yield('checkout')
        </div>
    </div>
@endsection
