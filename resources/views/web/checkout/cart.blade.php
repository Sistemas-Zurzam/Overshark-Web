@extends('web.checkout.layout', ['step' => 1])

{{-- C1 · Tu carrito (y E1 · Tu carrito está vacío). Cantidades y "Eliminar" usan las rutas del carrito con stay=1. --}}

@section('title', 'Tu carrito | Overshark')

@php($title = fn ($text) => \App\Support\ProductName::title((string) $text))

@section('checkout')
    @if ($items->isEmpty())
        <section class="flex max-w-[560px] flex-col items-start gap-5 py-6" aria-labelledby="cart-title">
            <h1 id="cart-title" class="text-[30px] font-extrabold leading-[1.45] text-[#0b1015] sm:text-[36px]">TU CARRITO ESTÁ VACÍO</h1>
            <p class="text-[16px] leading-[1.45] text-[#65717c]">Agrega tus prendas favoritas y arma tu próximo look. Enviamos a todo el Perú desde S/ 15.</p>
            <a href="{{ route('web.products.search') }}" class="os-checkout-btn os-focus w-full sm:w-[280px]">IR A LA TIENDA</a>
        </section>
    @else
        <h1 class="text-[30px] font-extrabold leading-[1.45] text-[#0b1015] sm:text-[36px]">TU CARRITO</h1>

        @if ($errors->any())
            <p class="bg-[#fdecec] px-4 py-3 text-[14px] font-bold text-[#a3261c]" role="alert">{{ $errors->first() }}</p>
        @elseif (session('cart_notice'))
            <p class="bg-[#fff6e0] px-4 py-3 text-[14px] font-bold text-[#8a5a00]" role="status">{{ session('cart_notice') }}</p>
        @endif

        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_448px] lg:gap-12">
            <section class="flex flex-col gap-6" aria-labelledby="cart-items">
                <h2 id="cart-items" class="text-[18px] font-bold leading-[1.45] text-[#0b1015]">{{ $count }} {{ $count === 1 ? 'prenda' : 'prendas' }} para tu próximo look</h2>
                <ul class="flex flex-col">
                    @foreach ($items as $key => $item)
                        @php($isCombo = ($item['kind'] ?? null) === 'combo')
                        <li class="flex gap-4 border-b border-[#dae0e5] py-6 first:pt-0 last:border-b-0 sm:gap-5">
                            <img src="{{ $item['image'] ?? asset('images/default-hero-banner.png') }}" alt="" class="h-[112px] w-[88px] shrink-0 bg-[#f5f7f9] object-cover sm:h-[132px] sm:w-[104px]">
                            <div class="flex min-w-0 flex-1 flex-col gap-2 leading-[1.45] text-[#0b1015]">
                                <p class="text-[17px] font-bold">{{ $isCombo ? ($item['producto'] ?? 'Combo') : $title($item['producto'] ?? 'Producto') }}</p>
                                @if ($isCombo)
                                    <p class="text-[13px] text-[#65717c]">Combo · {{ collect($item['selections'] ?? [])->map(fn ($selection) => $title($selection['producto'] ?? '').' '.$title($selection['color'] ?? '').' '.($selection['talla'] ?? ''))->implode(' · ') }}</p>
                                @else
                                    <p class="text-[13px] text-[#65717c]">{{ $title($item['color'] ?? '') }} · Talla {{ $item['talla'] ?? '-' }}</p>
                                @endif
                                <p class="text-[16px] font-extrabold">S/ {{ number_format((float) $item['price'] * (int) $item['qty'], 2) }}@if ((int) $item['qty'] > 1) <span class="text-[13px] font-medium text-[#65717c]">· S/ {{ number_format((float) $item['price'], 2) }} c/u</span>@endif</p>
                                <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                                    @if ($isCombo)
                                        <span class="text-[14px] font-bold">{{ $item['qty'] }} {{ (int) $item['qty'] === 1 ? 'combo' : 'combos' }}</span>
                                    @else
                                        <div class="flex items-center gap-1 text-[14px] font-bold">
                                            <form method="POST" action="{{ route('web.cart.update', $item['variant_id']) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="action" value="decrement"><input type="hidden" name="stay" value="1">
                                                <button type="submit" class="os-focus grid h-8 w-8 place-items-center hover:bg-[#f5f7f9]" aria-label="Quitar una unidad">−</button>
                                            </form>
                                            <span class="w-6 text-center" aria-label="Cantidad">{{ $item['qty'] }}</span>
                                            <form method="POST" action="{{ route('web.cart.update', $item['variant_id']) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="action" value="increment"><input type="hidden" name="stay" value="1">
                                                <button type="submit" class="os-focus grid h-8 w-8 place-items-center hover:bg-[#f5f7f9]" aria-label="Agregar una unidad">+</button>
                                            </form>
                                        </div>
                                    @endif
                                    <form method="POST" action="{{ $isCombo ? route('web.cart.combo.destroy', $key) : route('web.cart.destroy', $item['variant_id']) }}">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="stay" value="1">
                                        <button type="submit" class="os-focus text-[13px] text-[#65717c] hover:text-[#a3261c] hover:underline">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('web.products.search') }}" class="os-checkout-btn os-checkout-btn--ghost os-focus w-full sm:w-[280px]">SEGUIR COMPRANDO</a>
            </section>

            {{-- En el carrito aún no se eligió la entrega: sin fila de envío y el total es el subtotal. --}}
            @component('web.checkout.partials.summary', ['items' => $items, 'count' => $count, 'subtotal' => $subtotal, 'shipping' => null, 'total' => $subtotal, 'priceChanges' => $priceChanges])
                <a href="{{ route('web.checkout.personal') }}" class="os-checkout-btn os-focus w-full">CONTINUAR CON MI COMPRA</a>
                <p class="text-[12px] leading-[1.45] text-[#65717c]">El envío se suma al elegir la entrega: S/ 15 a domicilio en Lima, S/ 30 en agencia Shalom o gratis si recoges en almacén.</p>
            @endcomponent
        </div>

        {{-- Te puede interesar: reglas del panel (Sugerencias). Se suma sin recargar el flujo (quiet=1). --}}
        @php($suggestions = \App\Support\CartSuggestions::forCart())
        @if ($suggestions->isNotEmpty())
            <section class="os-hh border-t border-[#dae0e5] pt-8" aria-labelledby="cart-suggest">
                <h2 id="cart-suggest" class="text-[20px] font-extrabold text-[#0b1015]">Te puede interesar</h2>
                <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                    @foreach ($suggestions as $card)
                        @include('web.partials.suggestion-card', ['card' => $card, 'quiet' => true])
                    @endforeach
                </div>
            </section>
        @endif
    @endif
@endsection
