@extends('layouts.web')

@section('title', 'Mi carrito | Overshark')

@section('content')
    <section class="px-5 py-10 text-white lg:px-8">
        <div class="mx-auto max-w-7xl">
            <h1 class="text-center text-3xl font-black">Mi carrito ({{ $itemCount }})</h1>
            @if (session('status'))
                <p class="mx-auto mt-4 max-w-xl rounded-xl bg-emerald-400/10 px-4 py-3 text-center text-sm font-bold text-emerald-300" role="status">{{ session('status') }}</p>
            @endif

            <div class="mt-7 grid gap-8 lg:grid-cols-[minmax(0,1fr)_300px] xl:grid-cols-[minmax(0,1fr)_320px]">
                <div>
                    <div class="hidden border-b border-edge pb-3 text-xs font-bold uppercase tracking-wide text-haze xl:grid xl:grid-cols-[minmax(320px,1fr)_170px_170px_170px_44px] xl:items-center">
                        <span>Producto</span>
                        <span class="text-center">Precio</span>
                        <span class="text-center">Cantidad</span>
                        <span class="text-center">Subtotal</span>
                        <span></span>
                    </div>

                    <div class="divide-y divide-edge">
                        @forelse ($items as $itemKey => $item)
                            @php
                                $variantId = $item['variant_id'] ?? 0;
                                $isCombo = ($item['kind'] ?? null) === 'combo';
                                $qty = (int) ($item['qty'] ?? 1);
                                $price = (float) ($item['price'] ?? 0);
                                $oldPrice = $price > 0 ? $price / 0.8 : 0;
                                $lineTotal = $price * $qty;
                                $oldLineTotal = $oldPrice * $qty;
                            @endphp
                            <article class="grid gap-5 py-5 xl:grid-cols-[minmax(320px,1fr)_170px_170px_170px_44px] xl:items-center">
                                <div class="grid grid-cols-[88px_1fr] items-center gap-4 sm:grid-cols-[108px_1fr]">
                                    <div class="h-[110px] w-[88px] overflow-hidden rounded-md bg-panel-2 sm:h-[135px] sm:w-[108px]">
                                        <img src="{{ $item['image'] ?? asset('images/default-hero-banner.png') }}" alt="{{ $item['producto'] ?? 'Producto' }}" class="h-full w-full object-contain object-center">
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="text-base font-semibold">{{ $item['producto'] ?? 'Producto' }}</h2>
                                        @if ($isCombo)
                                            <p class="mt-3 text-xs font-black uppercase tracking-wide text-neon-soft">Combo · {{ $qty }} unidad{{ $qty === 1 ? '' : 'es' }}</p>
                                            <div class="mt-2 space-y-1">
                                                @foreach ($item['selections'] ?? [] as $selection)
                                                    <p class="text-sm text-mist">{{ $selection['producto'] ?? 'Producto' }} · {{ $selection['talla'] ?? '-' }} · {{ $selection['color'] ?? '-' }}</p>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="mt-3 text-sm text-mist">Color: {{ $item['color'] ?? '-' }}</p>
                                            <p class="mt-2 text-sm text-mist">Talla: {{ $item['talla'] ?? '-' }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-baseline justify-between xl:flex xl:min-h-[135px] xl:items-center xl:justify-center xl:text-center">
                                    <span class="text-xs font-bold uppercase text-haze xl:hidden">Precio</span>
                                    <div>
                                        <p class="text-xl font-black sm:text-2xl">S/ {{ number_format($price, 2) }}</p>
                                        @if ($oldPrice > 0)
                                            <p class="mt-1 text-base text-haze line-through">S/ {{ number_format($oldPrice, 2) }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center justify-between xl:min-h-[135px] xl:justify-center">
                                    <span class="text-xs font-bold uppercase text-haze xl:hidden">Cantidad</span>
                                    @if ($isCombo)
                                        <span class="rounded-md bg-panel-2 px-3 py-2 text-xs font-black text-mist">{{ $qty }} combo{{ $qty === 1 ? '' : 's' }}</span>
                                    @else
                                        <div class="grid w-28 grid-cols-3 overflow-hidden rounded-md border border-edge">
                                            <form method="POST" action="{{ route('web.cart.update', $variantId) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="action" value="increment">
                                                <button type="submit" class="grid h-10 w-full place-items-center text-2xl font-bold text-haze transition hover:bg-white/5">+</button>
                                            </form>
                                            <span class="grid h-10 place-items-center text-sm font-medium text-haze">{{ $qty }}</span>
                                            <form method="POST" action="{{ route('web.cart.update', $variantId) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="action" value="decrement">
                                                <button type="submit" class="grid h-10 w-full place-items-center text-2xl font-bold text-haze transition hover:bg-white/5">-</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-baseline justify-between xl:flex xl:min-h-[135px] xl:items-center xl:justify-center xl:text-center">
                                    <span class="text-xs font-bold uppercase text-haze xl:hidden">Subtotal</span>
                                    <div>
                                        <p class="text-xl font-black sm:text-2xl">S/ {{ number_format($lineTotal, 2) }}</p>
                                        @if ($oldLineTotal > 0)
                                            <p class="mt-1 text-base text-haze line-through">S/ {{ number_format($oldLineTotal, 2) }}</p>
                                        @endif
                                    </div>
                                </div>

                                <form method="POST" action="{{ $isCombo ? route('web.cart.combo.destroy', $itemKey) : route('web.cart.destroy', $variantId) }}" class="justify-self-end xl:justify-self-center">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="grid h-10 w-10 place-items-center text-haze transition hover:text-red-400" aria-label="Eliminar producto">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 7h14"/><path d="M10 11v6M14 11v6"/><path d="M8 7l1-3h6l1 3"/><path d="M6.5 7 8 21h8l1.5-14"/></svg>
                                    </button>
                                </form>
                            </article>
                        @empty
                            <div class="grid min-h-80 place-items-center rounded-2xl border border-dashed border-edge bg-panel-2 px-6 text-center">
                                <div>
                                    <p class="text-xl font-black">Tu carrito esta vacio</p>
                                    <p class="mt-2 text-sm text-haze">Agrega productos para ver el resumen de compra.</p>
                                    <a href="{{ route('web.products.search', ['orden' => 'populares']) }}" class="btn-primary mt-6 px-6 py-3">Ver productos</a>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <aside class="h-fit lg:sticky lg:top-32">
                    <div class="border border-edge bg-panel">
                        <div class="p-6">
                            <h2 class="text-xl font-black">Resumen de compra</h2>

                            <div class="mt-7 space-y-5 border-b border-edge pb-6 text-base">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-mist">Subtotal</span>
                                    <span class="font-semibold">S/{{ number_format($subtotal, 2) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-mist">IGV (18%)</span>
                                    <span class="font-semibold">S/{{ number_format($igv, 2) }}</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between py-6">
                                <span class="text-xl font-black text-mist">Total</span>
                                <span class="text-lg font-black">S/{{ number_format($total, 2) }}</span>
                            </div>

                            <button type="button" class="flex w-full items-center justify-between border border-edge px-3 py-3 text-sm font-semibold transition hover:border-neon-soft">
                                <span class="inline-flex items-center gap-3">
                                    <img src="{{ asset('images/iconos/Ticket.svg') }}" alt="" class="h-5 w-5 object-contain" aria-hidden="true">
                                    Tienes un cupon?
                                </span>
                                <span class="text-2xl leading-none">+</span>
                            </button>

                            <a href="{{ route('web.checkout.personal') }}" class="btn-primary mt-6 w-full justify-between px-5 py-4">
                                Finalizar compra
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>

                        <div class="border-t border-edge p-4">
                            <p class="text-base font-semibold">Aceptamos</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse ($paymentMethods as $method)
                                    <div class="grid h-10 min-w-12 place-items-center rounded bg-panel-2 px-2">
                                        @if ($method->imageUrl())
                                            <img src="{{ $method->imageUrl() }}" alt="{{ $method->name }}" class="h-7 w-12 object-contain">
                                        @else
                                            <span class="text-[11px] font-black">{{ $method->name }}</span>
                                        @endif
                                    </div>
                                @empty
                                    <span class="rounded bg-panel-2 px-3 py-2 text-xs font-black text-mist">Sin metodos activos</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="mx-0 mt-3 flex items-center gap-3 rounded-lg bg-panel-2 px-5 py-4 text-left text-sm font-medium leading-5 text-mist">
                        <img src="{{ asset('images/iconos/Truck.svg') }}" alt="" class="mt-0.5 h-6 w-6 shrink-0 object-contain" aria-hidden="true">
                        <p>El costo de envio se calculara en el siguiente paso.</p>
                    </div>

                    <a href="{{ route('web.products.search', ['orden' => 'populares']) }}" class="mt-4 inline-flex items-center gap-2 px-1 text-sm font-semibold transition hover:text-neon-soft">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                        Seguir comprando
                    </a>
                </aside>
            </div>
        </div>
    </section>
@endsection
