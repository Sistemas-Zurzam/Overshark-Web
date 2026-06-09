@extends('layouts.web')

@section('title', 'Mi carrito | Overshark')

@section('content')
    <section class="bg-white px-5 py-10 text-slate-950 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <h1 class="text-3xl font-black">Mi carrito ({{ $itemCount }})</h1>

            <div class="mt-7 grid gap-10 lg:grid-cols-[minmax(0,1fr)_300px] xl:grid-cols-[minmax(0,1fr)_320px]">
                <div>
                    <div class="hidden border-b border-slate-200 pb-3 text-xs font-bold uppercase tracking-wide text-slate-500 lg:grid lg:grid-cols-[minmax(260px,1.2fr)_150px_150px_150px_44px] lg:items-center">
                        <span>Producto</span>
                        <span class="text-center">Precio</span>
                        <span class="text-center">Cantidad</span>
                        <span class="text-center">Subtotal</span>
                        <span></span>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse ($items as $item)
                            @php
                                $variantId = $item['variant_id'] ?? 0;
                                $qty = (int) ($item['qty'] ?? 1);
                                $price = (float) ($item['price'] ?? 0);
                                $oldPrice = $price > 0 ? $price / 0.8 : 0;
                                $lineTotal = $price * $qty;
                                $oldLineTotal = $oldPrice * $qty;
                            @endphp
                            <article class="grid gap-5 py-5 lg:grid-cols-[minmax(260px,1.2fr)_150px_150px_150px_44px] lg:items-center">
                                <div class="grid grid-cols-[108px_1fr] items-center gap-4">
                                    <div class="h-[135px] w-[108px] overflow-hidden rounded-md bg-slate-50">
                                        <img src="{{ $item['image'] ?? asset('images/default-hero-banner.png') }}" alt="{{ $item['producto'] ?? 'Producto' }}" class="h-full w-full object-contain object-center">
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="text-base font-semibold">{{ $item['producto'] ?? 'Producto' }}</h2>
                                        <p class="mt-3 text-sm text-slate-700">Color: {{ $item['color'] ?? '-' }}</p>
                                        <p class="mt-2 text-sm text-slate-700">Talla: {{ $item['talla'] ?? '-' }}</p>
                                    </div>
                                </div>

                                <div class="flex items-baseline justify-between lg:block lg:text-center">
                                    <span class="text-xs font-bold uppercase text-slate-400 lg:hidden">Precio</span>
                                    <div>
                                        <p class="text-2xl font-black">S/ {{ number_format($price, 2) }}</p>
                                        @if ($oldPrice > 0)
                                            <p class="mt-1 text-base text-slate-400 line-through">S/ {{ number_format($oldPrice, 2) }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center justify-between lg:justify-center">
                                    <span class="text-xs font-bold uppercase text-slate-400 lg:hidden">Cantidad</span>
                                    <div class="grid w-28 grid-cols-3 overflow-hidden rounded-md border border-slate-200">
                                        <form method="POST" action="{{ route('web.cart.update', $variantId) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="increment">
                                            <button type="submit" class="grid h-10 w-full place-items-center text-2xl font-bold text-slate-500 transition hover:bg-slate-100">+</button>
                                        </form>
                                        <span class="grid h-10 place-items-center text-sm font-medium text-slate-500">{{ $qty }}</span>
                                        <form method="POST" action="{{ route('web.cart.update', $variantId) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="decrement">
                                            <button type="submit" class="grid h-10 w-full place-items-center text-2xl font-bold text-slate-500 transition hover:bg-slate-100">-</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="flex items-baseline justify-between lg:block lg:text-center">
                                    <span class="text-xs font-bold uppercase text-slate-400 lg:hidden">Subtotal</span>
                                    <div>
                                        <p class="text-2xl font-black">S/ {{ number_format($lineTotal, 2) }}</p>
                                        @if ($oldLineTotal > 0)
                                            <p class="mt-1 text-base text-slate-400 line-through">S/ {{ number_format($oldLineTotal, 2) }}</p>
                                        @endif
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('web.cart.destroy', $variantId) }}" class="justify-self-end lg:justify-self-center">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="grid h-10 w-10 place-items-center text-slate-400 transition hover:text-red-600" aria-label="Eliminar producto">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 7h14"/><path d="M10 11v6M14 11v6"/><path d="M8 7l1-3h6l1 3"/><path d="M6.5 7 8 21h8l1.5-14"/></svg>
                                    </button>
                                </form>
                            </article>
                        @empty
                            <div class="grid min-h-80 place-items-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 text-center">
                                <div>
                                    <p class="text-xl font-black">Tu carrito esta vacio</p>
                                    <p class="mt-2 text-sm text-slate-500">Agrega productos para ver el resumen de compra.</p>
                                    <a href="{{ route('web.home') }}#productos" class="btn-primary mt-6 px-6 py-3">Ver productos</a>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <aside class="h-fit border border-slate-200 bg-white lg:sticky lg:top-28">
                    <div class="p-6">
                        <h2 class="text-xl font-black">Resumen de compra</h2>

                        <div class="mt-7 space-y-5 border-b border-slate-100 pb-6 text-base">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-700">Subtotal</span>
                                <span class="font-semibold">S/{{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-700">IGV (18%)</span>
                                <span class="font-semibold">S/{{ number_format($igv, 2) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between py-6">
                            <span class="text-xl font-black text-slate-700">Total</span>
                            <span class="text-lg font-black">S/{{ number_format($total, 2) }}</span>
                        </div>

                        <button type="button" class="flex w-full items-center justify-between border border-slate-200 px-3 py-3 text-sm font-semibold transition hover:border-slate-950">
                            <span class="inline-flex items-center gap-3">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M20 12v7a1 1 0 0 1-1 1h-7L4 12l8-8h7a1 1 0 0 1 1 1v7Z"/><circle cx="15" cy="9" r="1"/></svg>
                                Tienes un cupon?
                            </span>
                            <span class="text-2xl leading-none">+</span>
                        </button>

                        <button type="button" class="btn-primary mt-6 w-full justify-between px-5 py-4">
                            Finalizar compra
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </button>
                    </div>

                    <div class="border-t border-slate-200 p-4">
                        <p class="text-base font-semibold">Aceptamos</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($paymentMethods as $method)
                                <div class="grid h-10 min-w-12 place-items-center rounded bg-slate-50 px-2">
                                    @if ($method->imageUrl())
                                        <img src="{{ $method->imageUrl() }}" alt="{{ $method->name }}" class="h-7 w-12 object-contain">
                                    @else
                                        <span class="text-[11px] font-black">{{ $method->name }}</span>
                                    @endif
                                </div>
                            @empty
                                <span class="rounded bg-slate-50 px-3 py-2 text-xs font-black text-slate-600">Sin metodos activos</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="mx-0 mt-3 flex gap-3 rounded-lg bg-slate-50 px-5 py-4 text-center text-sm font-medium leading-5 text-slate-600">
                        <svg class="mt-0.5 h-6 w-6 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 7h11v9H4z"/><path d="M15 10h4l2 3v3h-6z"/><circle cx="8" cy="18" r="1.6"/><circle cx="18" cy="18" r="1.6"/></svg>
                        <p>El costo de envio se calculara en el siguiente paso.</p>
                    </div>

                    <a href="{{ route('web.home') }}#productos" class="mt-4 inline-flex items-center gap-2 px-1 text-sm font-semibold transition hover:text-cyan-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                        Seguir comprando
                    </a>
                </aside>
            </div>
        </div>
    </section>
@endsection
