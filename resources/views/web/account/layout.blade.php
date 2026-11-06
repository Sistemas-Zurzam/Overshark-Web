@extends('layouts.web')

{{-- Mi cuenta: menú lateral en escritorio y píldoras con scroll en móvil. Cada pantalla define 'heading' y 'account'. --}}

@php
    $account = auth('customer')->user();
    $menu = [
        ['customer.account', 'Resumen', 'customer.account'],
        ['customer.orders', 'Mis pedidos', 'customer.orders*'],
        ['customer.addresses', 'Mis direcciones', 'customer.addresses*'],
        ['customer.profile', 'Mi perfil', 'customer.profile*'],
    ];
@endphp

@section('content')
    <div class="os-hh">
        <div class="os-bravos-wrap py-8 text-[#0b1015] lg:py-12">
            <p class="text-[13px] font-bold text-[#65717c]">Hola, {{ $account->name }}</p>
            <h1 class="text-[28px] font-extrabold leading-[1.2] sm:text-[36px]">@yield('heading')</h1>

            <div class="mt-6 grid grid-cols-[minmax(0,1fr)] gap-6 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-10">
                <nav aria-label="Mi cuenta" class="lg:sticky lg:top-[132px] lg:self-start">
                    <ul class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 lg:flex-col lg:gap-1 lg:overflow-visible lg:pb-0">
                        @foreach ($menu as [$route, $label, $pattern])
                            <li class="shrink-0">
                                <a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif @class([
                                    'os-focus block whitespace-nowrap border px-4 py-2.5 text-[14px] font-bold transition lg:border-0 lg:border-l-2',
                                    'border-[#0b1015] bg-[#0b1015] text-white lg:bg-[#f5f7f9] lg:text-[#0b1015] lg:border-[#0b1015]' => request()->routeIs($pattern),
                                    'border-[#dae0e5] text-[#0b1015] hover:bg-[#f5f7f9] lg:border-transparent' => ! request()->routeIs($pattern),
                                ])>{{ $label }}</a>
                            </li>
                        @endforeach
                        <li class="shrink-0">
                            <form method="POST" action="{{ route('customer.logout') }}">
                                @csrf
                                <button type="submit" class="os-focus block w-full whitespace-nowrap border border-[#dae0e5] px-4 py-2.5 text-left text-[14px] font-bold text-[#a3261c] transition hover:bg-[#fdecec] lg:border-0 lg:border-l-2 lg:border-transparent">Cerrar sesión</button>
                            </form>
                        </li>
                    </ul>
                </nav>

                <div class="min-w-0">
                    @if (session('status'))
                        <p class="mb-5 border border-[#b8e0d0] bg-[#e3f4ec] px-4 py-3 text-[14px] font-bold text-[#147d5b]" role="status">{{ session('status') }}</p>
                    @endif
                    @unless ($account->hasVerifiedEmail())
                        <div class="mb-5 flex flex-col gap-3 border border-[#f0d9a8] bg-[#fff6e0] px-4 py-3 text-[14px] text-[#8a5a00] sm:flex-row sm:items-center sm:justify-between">
                            <p><strong>Verifica tu correo.</strong> Te enviamos un enlace a {{ $account->email }}.</p>
                            <form method="POST" action="{{ route('verification.send') }}">
                                @csrf
                                <button type="submit" class="os-focus font-extrabold underline underline-offset-4">Reenviar enlace</button>
                            </form>
                        </div>
                    @endunless

                    @yield('account')
                </div>
            </div>
        </div>
    </div>
@endsection
