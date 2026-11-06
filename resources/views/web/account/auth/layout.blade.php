@extends('layouts.web')

{{-- Pantallas de acceso (registro, inicio de sesión, recuperación): una tarjeta centrada sobre fondo claro. --}}

@section('content')
    <div class="os-hh">
        <div class="os-bravos-wrap flex justify-center py-12 sm:py-16">
            <div class="w-full max-w-[480px] text-[#0b1015]">
                <h1 class="text-[30px] font-extrabold leading-[1.2] sm:text-[36px]">@yield('heading')</h1>
                <p class="mt-2 text-[15px] leading-[1.5] text-[#65717c]">@yield('lead')</p>

                @if (session('status'))
                    <p class="mt-5 border border-[#b8e0d0] bg-[#e3f4ec] px-4 py-3 text-[14px] font-bold text-[#147d5b]" role="status">{{ session('status') }}</p>
                @endif

                <div class="mt-6 border border-[#dae0e5] bg-white p-5 sm:p-7">
                    @yield('form')
                </div>

                <div class="mt-6 text-center text-[14px] text-[#65717c]">@yield('footer-links')</div>
                <p class="mt-6 text-center text-[13px] text-[#65717c]">¿Prefieres no registrarte? <a href="{{ route('web.cart.index') }}" class="font-bold text-[#2467cf] hover:underline">Compra como invitado</a>.</p>
            </div>
        </div>
    </div>
@endsection
