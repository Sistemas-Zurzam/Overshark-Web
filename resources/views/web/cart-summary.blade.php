@extends('layouts.web')

@section('title', 'Mi carrito | Overshark')

@section('content')
    <section class="bg-white px-5 py-10 text-slate-950 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <h1 class="text-3xl font-black">Mi carrito ({{ $itemCount }})</h1>

            <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div>
                    <div class="grid grid-cols-4 items-start gap-2 border-b border-slate-200 pb-8 text-center text-xs font-bold text-slate-500">
                        @foreach ([['1', 'Carrito'], ['2', 'Envio'], ['3', 'Datos'], ['4', 'Confirmacion']] as [$step, $label])
                            <div class="relative">
                                @if (! $loop->last)
                                    <span class="absolute left-1/2 top-4 h-px w-full bg-slate-200"></span>
                                @endif
                                <span @class([
                                    'relative mx-auto grid h-8 w-8 place-items-center rounded-full border text-sm font-black',
                                    'border-slate-950 bg-slate-950 text-white' => $loop->first,
                                    'border-slate-200 bg-white text-slate-500' => ! $loop->first,
                                ])>{{ $step }}</span>
                                <span class="mt-3 block">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="divide-y divide-slate-200">
                        @forelse ($items as $item)
                            @php
                                $variantId = $item['variant_id'] ?? 0;
                                $qty = (int) ($item['qty'] ?? 1);
                                $price = (float) ($item['price'] ?? 0);
                            @endphp
                            <article class="grid gap-5 py-7 sm:grid-cols-[140px_1fr_auto] sm:items-center">
                                <div class="overflow-hidden rounded-lg bg-slate-50">
                                    <img src="{{ $item['image'] ?? asset('images/default-hero-banner.png') }}" alt="{{ $item['producto'] ?? 'Producto' }}" class="aspect-[4/5] h-full w-full object-cover">
                                </div>

                                <div class="min-w-0">
                                    <h2 class="text-lg font-black">{{ $item['producto'] ?? 'Producto' }}</h2>
                                    <p class="mt-3 text-sm text-slate-600">
                                        Color: {{ $item['color'] ?? '-' }}
                                        <span class="mx-2 text-slate-300">|</span>
                                        Talla: {{ $item['talla'] ?? '-' }}
                                    </p>
                                    <p class="mt-4 text-lg font-black">S/ {{ number_format($price, 2) }}</p>
                                    <div class="mt-5 flex items-center gap-4 text-sm font-bold">
                                        <a href="{{ route('web.products.show', $item['producto_id'] ?? 0) }}" class="underline transition hover:text-cyan-700">Editar</a>
                                        <span class="text-slate-300">|</span>
                                        <form method="POST" action="{{ route('web.cart.destroy', $variantId) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="underline transition hover:text-red-600">Eliminar</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-6 sm:min-w-[280px]">
                                    <div class="grid w-32 grid-cols-3 overflow-hidden rounded-md border border-slate-200">
                                        <form method="POST" action="{{ route('web.cart.update', $variantId) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="decrement">
                                            <button type="submit" class="grid h-11 w-full place-items-center text-xl font-bold transition hover:bg-slate-100">-</button>
                                        </form>
                                        <span class="grid h-11 place-items-center text-sm font-bold">{{ $qty }}</span>
                                        <form method="POST" action="{{ route('web.cart.update', $variantId) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="increment">
                                            <button type="submit" class="grid h-11 w-full place-items-center text-xl font-bold transition hover:bg-slate-100">+</button>
                                        </form>
                                    </div>
                                    <p class="text-lg font-black">S/ {{ number_format($price * $qty, 2) }}</p>
                                </div>
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

                    <a href="{{ route('web.home') }}#productos" class="mt-6 inline-flex items-center gap-2 text-sm font-bold transition hover:text-cyan-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                        Seguir comprando
                    </a>
                </div>

                <aside class="h-fit rounded-lg border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-28">
                    <h2 class="text-xl font-black">Resumen de compra</h2>

                    <div class="mt-7 space-y-5 border-b border-slate-200 pb-6 text-sm">
                        <div class="flex items-center justify-between font-bold">
                            <span>Productos ({{ $itemCount }})</span>
                            <span>S/ {{ number_format($subtotal, 2) }}</span>
                        </div>
                    </div>

                    <div class="space-y-5 border-b border-slate-200 py-6 text-sm font-bold">
                        <div class="flex items-center justify-between">
                            <span>Subtotal</span>
                            <span>S/ {{ number_format($subtotal, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-2">Envio <span class="grid h-4 w-4 place-items-center rounded-full border border-slate-400 text-[10px]">i</span></span>
                            <span>S/ {{ number_format($shipping, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>IGV (18%)</span>
                            <span>S/ {{ number_format($igv, 2) }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between py-6">
                        <span class="text-2xl font-black">Total</span>
                        <span class="text-3xl font-black">S/ {{ number_format($total, 2) }}</span>
                    </div>

                    <button type="button" class="flex w-full items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold transition hover:border-slate-950">
                        <span class="inline-flex items-center gap-3">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M20 12v7a1 1 0 0 1-1 1h-7L4 12l8-8h7a1 1 0 0 1 1 1v7Z"/><circle cx="15" cy="9" r="1"/></svg>
                            Tienes un cupon?
                        </span>
                        <span class="text-2xl leading-none">+</span>
                    </button>

                    <div class="mt-5 flex gap-4 rounded-lg bg-slate-50 px-4 py-4 text-sm font-semibold leading-5 text-slate-700">
                        <svg class="h-7 w-7 shrink-0 text-slate-950" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 7h11v9H4z"/><path d="M15 10h4l2 3v3h-6z"/><circle cx="8" cy="18" r="1.6"/><circle cx="18" cy="18" r="1.6"/></svg>
                        <p>El costo de envio se calcula segun la direccion que ingreses en el siguiente paso.</p>
                    </div>

                    <button type="button" class="btn-primary mt-5 w-full justify-between px-5 py-4">
                        Continuar con el envio
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </button>

                    <div class="mt-7 border-t border-slate-200 pt-5">
                        <p class="text-xs font-black">Aceptamos:</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($paymentMethods as $method)
                                <div class="flex h-9 items-center gap-2 rounded-md border border-slate-200 px-2">
                                    @if ($method->imageUrl())
                                        <img src="{{ $method->imageUrl() }}" alt="{{ $method->name }}" class="h-6 w-8 object-contain">
                                    @endif
                                    <span class="text-[11px] font-black">{{ $method->name }}</span>
                                </div>
                            @empty
                                @foreach (['VISA', 'MC', 'AMEX', 'Diners', 'Yape'] as $method)
                                    <span class="rounded-md border border-slate-200 px-3 py-2 text-xs font-black text-slate-600">{{ $method }}</span>
                                @endforeach
                            @endforelse
                        </div>
                    </div>
                </aside>
            </div>

            <div class="mt-10 grid gap-4 rounded-lg bg-slate-50 px-6 py-6 text-sm sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Envios a todo el Peru', 'Envios seguros y rapidos', 'truck'],
                    ['Calidad Overshark', 'Prendas premium', 'badge'],
                    ['Compra segura', 'Tus datos protegidos', 'shield'],
                    ['Necesitas ayuda?', 'Escribenos por WhatsApp', 'whatsapp'],
                ] as [$title, $copy, $icon])
                    <div class="flex items-center gap-4">
                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white">
                            @if ($icon === 'truck')
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 7h11v9H4z"/><path d="M15 10h4l2 3v3h-6z"/><circle cx="8" cy="18" r="1.6"/><circle cx="18" cy="18" r="1.6"/></svg>
                            @elseif ($icon === 'badge')
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m12 3 2.2 2.3 3.2-.4.8 3.1 2.8 1.6-1.5 2.8 1.5 2.8-2.8 1.6-.8 3.1-3.2-.4L12 21l-2.2-2.3-3.2.4-.8-3.1L3 14.4l1.5-2.8L3 8.8l2.8-1.6.8-3.1 3.2.4Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>
                            @elseif ($icon === 'shield')
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 3 5 6v5c0 4.4 2.8 7.9 7 10 4.2-2.1 7-5.6 7-10V6Z"/><path d="m9 12 2 2 4-5"/></svg>
                            @else
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M20.5 11.8A8.5 8.5 0 0 1 8.4 19.5L4 20.5l1-4.2A8.5 8.5 0 1 1 20.5 11.8Z"/><path d="M9 8.8c.3 3 2.1 4.9 5.2 5.7l1.2-1.2"/></svg>
                            @endif
                        </div>
                        <div>
                            <p class="font-black">{{ $title }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $copy }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
