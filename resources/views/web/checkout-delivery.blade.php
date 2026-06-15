@extends('layouts.web')

@section('title', 'Entrega y pago | Overshark')

@section('content')
    <section class="bg-[#fbfbfb] px-4 py-7 text-slate-950 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-[980px] gap-10 lg:grid-cols-[minmax(0,620px)_270px] lg:items-start lg:justify-center">
            <div>
                <div class="mx-auto grid max-w-md grid-cols-3 items-start gap-0 text-center text-xs font-medium text-slate-500">
                    @foreach ([['1', 'Datos personales', false], ['2', 'Entrega y pago', true], ['3', 'Confirmacion', false]] as [$step, $label, $active])
                        <div class="relative flex flex-col items-center gap-3 pb-4">
                            @if (! $loop->first)
                                <span class="absolute right-1/2 top-3.5 h-px w-full -translate-x-4 bg-slate-200"></span>
                            @endif
                            <span @class([
                                'relative z-10 grid h-7 w-7 place-items-center rounded-full border text-sm font-bold',
                                'border-[#2f6fbd] bg-[#2f6fbd] text-white' => $active || $step === '1',
                                'border-slate-200 bg-white text-slate-400' => ! $active && $step !== '1',
                            ])>{{ $step }}</span>
                            <span @class(['text-slate-950' => $active])>{{ $label }}</span>
                            @if ($active)
                                <span class="absolute bottom-0 h-px w-32 bg-[#2f6fbd]"></span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <form class="mt-8 space-y-4">
                    <section class="rounded-lg border border-[#2f6fbd] bg-white p-5 shadow-[0_18px_45px_rgba(47,111,189,0.08)]">
                        <label class="flex cursor-pointer items-start gap-4">
                            <input type="radio" name="delivery_type" checked class="mt-7 h-4 w-4 border-slate-300 text-[#2f6fbd] focus:ring-[#2f6fbd]">
                            <div class="flex-1">
                                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                                    <div class="flex items-start gap-4">
                                        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-slate-100">
                                            <img src="{{ asset('images/iconos/Truck.svg') }}" alt="" class="h-7 w-7 object-contain" aria-hidden="true">
                                        </div>
                                        <div>
                                            <h2 class="text-base font-black">Delivery a domicilio</h2>
                                            <p class="mt-1 text-xs text-slate-600">Recibe tu pedido en la direccion registrada.</p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2 text-base font-black">
                                        S/{{ number_format($shipping, 2) }}
                                        <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                                    </div>
                                </div>
                                <p class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    Entrega estimada: 1 a 2 dias habiles
                                </p>
                            </div>
                        </label>

                        <div class="mt-7">
                            <h3 class="text-base font-black">Agencia seleccionada</h3>
                            <div class="mt-4 flex items-center gap-4 rounded-lg border border-slate-100 bg-slate-50 px-5 py-4">
                                <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-blue-100 text-[#2f6fbd]">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 21s6-4.3 6-10a6 6 0 1 0-12 0c0 5.7 6 10 6 10Z"/><circle cx="12" cy="11" r="2"/></svg>
                                </div>
                                <div class="min-w-0 flex-1 text-xs">
                                    <p class="font-black">Av. Universitaria 2200, Los Olivos, Lima</p>
                                    <p class="mt-1 text-slate-500">Akapana 1261, Lima 15427</p>
                                </div>
                                <a href="{{ route('web.checkout.personal') }}" class="shrink-0 text-xs font-bold underline">Cambiar direccion</a>
                            </div>
                        </div>

                        <div class="mt-7">
                            <h3 class="text-base font-black">Como deseas realizar el pago?</h3>

                            <label class="mt-4 flex cursor-pointer items-center gap-4 rounded-lg border border-blue-200 bg-white px-5 py-4">
                                <input type="radio" name="payment_type" checked class="h-4 w-4 border-slate-300 text-[#2f6fbd] focus:ring-[#2f6fbd]">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100">
                                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 8h16l-2 12H6Z"/><path d="M9 8a3 3 0 0 1 6 0"/><path d="M9 14h6"/></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-black">Pagar pedido completo</span>
                                    <span class="mt-1 block text-xs text-slate-500">Realiza el pago total ahora y recoge tu pedido cuando este listo.</span>
                                </span>
                                <span class="shrink-0 text-base font-black">S/{{ number_format($shipping, 2) }}</span>
                            </label>

                            <label class="mt-4 flex cursor-pointer items-center gap-4 rounded-lg border border-slate-100 bg-white px-5 py-4 shadow-sm">
                                <input type="radio" name="payment_type" class="h-4 w-4 border-slate-300 text-[#2f6fbd] focus:ring-[#2f6fbd]">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100">
                                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 7h14v12H5Z"/><path d="M8 7V5h8v2"/><path d="M9 13h6"/><path d="M12 10v6"/></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-black">Pago contraentrega</span>
                                    <span class="mt-1 block text-xs text-slate-500">Separa tu pedido hoy y paga el saldo restante al recogerlo.</span>
                                </span>
                                <span class="shrink-0 text-base font-black">S/{{ number_format($shipping, 2) }}</span>
                            </label>

                            <p class="mt-4 rounded-md bg-blue-50 px-3 py-2 text-xs text-slate-600">Te notificaremos por WhatsApp cuando tu pedido este listo para recoger.</p>
                        </div>
                    </section>

                    <label class="flex cursor-pointer items-start gap-4 rounded-lg border border-slate-100 bg-white p-5 shadow-sm">
                        <input type="radio" name="delivery_type" class="mt-7 h-4 w-4 border-slate-300 text-[#2f6fbd] focus:ring-[#2f6fbd]">
                        <div class="flex flex-1 items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-slate-100">
                                    <svg class="h-7 w-7 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 7h16v13H4Z"/><path d="M8 7V4h8v3"/><path d="M8 12h8"/></svg>
                                </span>
                                <span>
                                    <span class="block text-base font-black">Retiro en agencia Shalom</span>
                                    <span class="mt-1 block text-xs text-slate-600">Retira tu pedido en la agencia Shalom mas cercana.</span>
                                    <span class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                        Entrega estimada: 1 a 2 dias habiles
                                    </span>
                                </span>
                            </div>
                            <span class="shrink-0 text-base font-black">S/{{ number_format($shipping, 2) }}</span>
                        </div>
                    </label>

                    <label class="flex cursor-pointer items-start gap-4 rounded-lg border border-slate-100 bg-white p-5 shadow-sm">
                        <input type="radio" name="delivery_type" class="mt-7 h-4 w-4 border-slate-300 text-[#2f6fbd] focus:ring-[#2f6fbd]">
                        <div class="flex flex-1 items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-slate-100">
                                    <svg class="h-7 w-7 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 10h16"/><path d="M5 10l1-5h12l1 5v10H5Z"/><path d="M9 20v-6h6v6"/></svg>
                                </span>
                                <span>
                                    <span class="block text-base font-black">Retiro en almacen</span>
                                    <span class="mt-1 block text-xs text-slate-600">Paga el saldo restante al recoger.</span>
                                    <span class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                        Disponible para recojo hoy
                                    </span>
                                </span>
                            </div>
                            <span class="shrink-0 text-base font-black text-[#2f6fbd]">Gratis</span>
                        </div>
                    </label>

                    <button type="button" class="inline-flex h-12 w-full items-center justify-center gap-4 rounded-md bg-[#111] px-5 text-sm font-black uppercase tracking-normal text-white transition hover:bg-[#2f6fbd]">
                        Continuar
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </button>
                </form>
            </div>

            <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.06)] lg:sticky lg:top-28">
                <h2 class="text-base font-black">Resumen de compra</h2>

                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($items->take(4) as $item)
                        @php
                            $variantLabel = trim('Color: '.($item['color'] ?? '-').' | Talla: '.($item['talla'] ?? '-'));
                            $productLabel = ($item['producto'] ?? 'Producto').' - '.$variantLabel;
                        @endphp
                        <div class="group relative h-9 w-9 rounded-md bg-slate-50" title="{{ $productLabel }}">
                            <img src="{{ $item['image'] ?? asset('images/default-hero-banner.png') }}" alt="{{ $item['producto'] ?? 'Producto' }}" class="h-full w-full object-contain">
                            <div class="pointer-events-none absolute left-1/2 top-full z-20 mt-2 w-48 -translate-x-1/2 rounded-lg bg-slate-950 px-3 py-2 text-left text-xs font-bold leading-4 text-white opacity-0 shadow-xl transition group-hover:opacity-100">
                                <p class="truncate">{{ $item['producto'] ?? 'Producto' }}</p>
                                <p class="mt-1 font-medium text-slate-300">{{ $variantLabel }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 space-y-4 border-b border-slate-100 pb-5 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-slate-600">Envio</span>
                        <span class="font-semibold">S/{{ number_format($shipping, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-slate-600">Subtotal</span>
                        <span class="font-semibold">S/{{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-slate-600">IGV (18%)</span>
                        <span class="font-semibold">S/{{ number_format($igv, 2) }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between py-5">
                    <span class="text-base font-black">Total</span>
                    <span class="text-base font-black">S/{{ number_format($total, 2) }}</span>
                </div>

                <button type="button" class="flex h-9 w-full items-center justify-between rounded-none border border-slate-200 px-3 text-xs font-semibold transition hover:border-slate-950">
                    <span class="inline-flex items-center gap-2">
                        <img src="{{ asset('images/iconos/Ticket.svg') }}" alt="" class="h-4 w-4 object-contain" aria-hidden="true">
                        Tienes un cupon?
                    </span>
                    <span class="text-base leading-none">-</span>
                </button>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <p class="text-xs font-black">Aceptamos</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse ($paymentMethods as $method)
                            <div class="grid h-9 min-w-11 place-items-center rounded bg-slate-50 px-2">
                                @if ($method->imageUrl())
                                    <img src="{{ $method->imageUrl() }}" alt="{{ $method->name }}" class="h-6 w-10 object-contain">
                                @else
                                    <span class="text-[11px] font-black">{{ $method->name }}</span>
                                @endif
                            </div>
                        @empty
                            <span class="rounded bg-slate-50 px-3 py-2 text-xs font-black text-slate-600">Sin metodos activos</span>
                        @endforelse
                    </div>
                </div>
            </aside>
        </div>
    </section>
@endsection
