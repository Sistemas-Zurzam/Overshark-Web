@extends('layouts.web')

@section('title', 'Entrega y pago | Overshark')

@section('content')
    <style>
        .checkout-option:has(> input:checked) > .checkout-card {
            border-color: #2f6fbd;
            box-shadow: 0 18px 45px rgba(47, 111, 189, 0.08);
        }

        .checkout-option:has(> input:checked) .checkout-expanded {
            display: block;
        }

        .checkout-option:has(> input:checked) .checkout-radio {
            border-color: #2f6fbd;
            background: radial-gradient(circle at center, #2f6fbd 0 45%, transparent 47%);
        }
    </style>

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
                    <label class="checkout-option block cursor-pointer">
                        <input type="radio" name="delivery_type" value="home" class="sr-only">
                        <div class="checkout-card rounded-lg border border-slate-100 bg-white p-5 shadow-sm transition">
                            <div class="flex items-start gap-4">
                                <span class="checkout-radio mt-6 h-4 w-4 rounded-full border border-slate-300"></span>
                                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-slate-100">
                                    <img src="{{ asset('images/iconos/Truck.svg') }}" alt="" class="h-7 w-7 object-contain" aria-hidden="true">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                        <div>
                                            <h2 class="text-base font-black">Delivery a domicilio</h2>
                                            <p class="mt-1 text-xs text-slate-600">Recibe tu pedido en la direccion registrada.</p>
                                        </div>
                                        <span class="shrink-0 text-base font-black">S/14.00</span>
                                    </div>
                                    <p class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                        Entrega estimada: 1 a 2 dias habiles
                                    </p>
                                </div>
                            </div>

                            <div class="checkout-expanded hidden">
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
                                    <div class="mt-4 space-y-4">
                                        <label class="flex cursor-pointer items-center gap-4 rounded-lg border border-slate-100 bg-white px-5 py-4 shadow-sm">
                                            <input type="radio" name="home_payment_type" class="h-4 w-4 border-slate-300 text-[#2f6fbd] focus:ring-[#2f6fbd]">
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100">
                                                <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 8h16l-2 12H6Z"/><path d="M9 8a3 3 0 0 1 6 0"/><path d="M9 14h6"/></svg>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-black">Pagar pedido completo</span>
                                                <span class="mt-1 block text-xs text-slate-500">Realiza el pago total ahora y recibe tu pedido sin pagos pendientes.</span>
                                            </span>
                                            <span class="shrink-0 text-base font-black">S/14.00</span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-4 rounded-lg border border-slate-100 bg-white px-5 py-4 shadow-sm">
                                            <input type="radio" name="home_payment_type" class="h-4 w-4 border-slate-300 text-[#2f6fbd] focus:ring-[#2f6fbd]">
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100">
                                                <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 7h14v12H5Z"/><path d="M8 7V5h8v2"/><path d="M9 13h6"/><path d="M12 10v6"/></svg>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-black">Pago contraentrega</span>
                                                <span class="mt-1 block text-xs text-slate-500">Separa tu pedido hoy y paga el saldo restante al recibirlo.</span>
                                            </span>
                                            <span class="shrink-0 text-base font-black">S/14.00</span>
                                        </label>
                                    </div>
                                    <p class="mt-4 rounded-md bg-blue-50 px-3 py-2 text-xs text-slate-600">Te notificaremos por WhatsApp cuando tu pedido este listo.</p>
                                </div>
                            </div>
                        </div>
                    </label>

                    <label class="checkout-option block cursor-pointer">
                        <input type="radio" name="delivery_type" value="shalom" class="sr-only">
                        <div class="checkout-card rounded-lg border border-slate-100 bg-white p-5 shadow-sm transition">
                            <div class="flex items-start gap-4">
                                <span class="checkout-radio mt-6 h-4 w-4 rounded-full border border-slate-300"></span>
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-slate-100">
                                    <svg class="h-7 w-7 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 7h16v13H4Z"/><path d="M8 7V4h8v3"/><path d="M8 12h8"/></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                        <span>
                                            <span class="block text-base font-black">Retiro en agencia Shalom</span>
                                            <span class="mt-1 block text-xs text-slate-600">Retira tu pedido en la agencia Shalom mas cercana.</span>
                                        </span>
                                        <span class="flex shrink-0 items-center gap-2 text-base font-black">S/12.00 <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg></span>
                                    </span>
                                    <span class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                        Entrega estimada: 1 a 2 dias habiles
                                    </span>
                                </span>
                            </div>

                            <div class="checkout-expanded hidden">
                                <div class="mt-7">
                                    <h3 class="text-base font-black">Agencia seleccionada</h3>
                                    <div class="mt-4 flex items-center gap-4 rounded-lg border border-slate-100 bg-slate-50 px-5 py-4">
                                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-blue-100 text-[#2f6fbd]">
                                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 21s6-4.3 6-10a6 6 0 1 0-12 0c0 5.7 6 10 6 10Z"/><circle cx="12" cy="11" r="2"/></svg>
                                        </div>
                                        <div class="min-w-0 flex-1 text-xs">
                                            <p data-shalom-selected-name class="font-black">Shalom Manco Capac</p>
                                            <p data-shalom-selected-address class="mt-1 text-slate-500">Av. Manco Capac 123, Los Olivos</p>
                                            <span class="mt-3 inline-flex rounded-md bg-blue-50 px-3 py-1 text-[11px] font-bold text-[#2f6fbd]">Mas cercana a tu direccion</span>
                                        </div>
                                        <button type="button" data-shalom-agency-modal-open class="shrink-0 text-xs font-bold underline">Cambiar agencia</button>
                                    </div>
                                </div>

                                <div class="mt-7">
                                    <h3 class="text-base font-black">Tipo de envio</h3>
                                    <div class="mt-4 space-y-4">
                                        <label class="flex cursor-pointer items-center gap-4 rounded-lg border border-slate-100 bg-white px-5 py-4 shadow-sm">
                                            <input type="radio" name="shalom_shipping" class="h-4 w-4 border-slate-300 text-[#2f6fbd] focus:ring-[#2f6fbd]">
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100">
                                                <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 7h11v10H4Z"/><path d="M15 11h3l2 3v3h-5Z"/><circle cx="8" cy="18" r="1.5"/><circle cx="17" cy="18" r="1.5"/></svg>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-black">Envio terrestre</span>
                                                <span class="mt-1 block text-xs text-slate-500">Entrega estimada: 2 a 5 dias habiles</span>
                                            </span>
                                            <span class="shrink-0 text-base font-black">S/12.00</span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-4 rounded-lg border border-slate-100 bg-white px-5 py-4 shadow-sm">
                                            <input type="radio" name="shalom_shipping" class="h-4 w-4 border-slate-300 text-[#2f6fbd] focus:ring-[#2f6fbd]">
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100">
                                                <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m3 13 18-8-8 18-2-8Z"/></svg>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-black">Envio aereo</span>
                                                <span class="mt-1 block text-xs text-slate-500">Entrega estimada: 1 a 2 dias habiles</span>
                                            </span>
                                            <span class="shrink-0 text-base font-black">S/12.00</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </label>

                    <label class="checkout-option block cursor-pointer">
                        <input type="radio" name="delivery_type" value="warehouse" class="sr-only">
                        <div class="checkout-card rounded-lg border border-slate-100 bg-white p-5 shadow-sm transition">
                            <div class="flex items-start gap-4">
                                <span class="checkout-radio mt-6 h-4 w-4 rounded-full border border-slate-300"></span>
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-slate-100">
                                    <svg class="h-7 w-7 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 10h16"/><path d="M5 10l1-5h12l1 5v10H5Z"/><path d="M9 20v-6h6v6"/></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                        <span>
                                            <span class="block text-base font-black">Retiro en almacen</span>
                                            <span class="mt-1 block text-xs text-slate-600">Recoge tu pedido en nuestro almacen.</span>
                                        </span>
                                        <span class="flex shrink-0 items-center gap-2 text-base font-black text-[#2f6fbd]">Gratis <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg></span>
                                    </span>
                                    <span class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                        Disponible para recojo hoy
                                    </span>
                                </span>
                            </div>

                            <div class="checkout-expanded hidden">
                                <div class="mt-7">
                                    <h3 class="text-base font-black">Agencia seleccionada</h3>
                                    <div class="mt-4 flex items-center gap-4 rounded-lg border border-slate-100 bg-slate-50 px-5 py-4">
                                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-blue-100 text-[#2f6fbd]">
                                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 21s6-4.3 6-10a6 6 0 1 0-12 0c0 5.7 6 10 6 10Z"/><circle cx="12" cy="11" r="2"/></svg>
                                        </div>
                                        <div class="min-w-0 flex-1 text-xs">
                                            <p class="font-black">Almacen Overshark</p>
                                            <p class="mt-1 text-slate-500">Akapana 1261, Lima 15427</p>
                                            <span class="mt-3 inline-flex rounded-md bg-blue-50 px-3 py-1 text-[11px] font-bold text-[#2f6fbd]">Mas cercana a tu direccion</span>
                                        </div>
                                        <button type="button" class="shrink-0 text-xs font-bold underline">Ver ubicacion</button>
                                    </div>
                                </div>

                                <div class="mt-7">
                                    <h3 class="text-base font-black">Como deseas realizar el pago?</h3>
                                    <div class="mt-4 space-y-4">
                                        <label class="flex cursor-pointer items-center gap-4 rounded-lg border border-slate-100 bg-white px-5 py-4 shadow-sm">
                                            <input type="radio" name="warehouse_payment_type" class="h-4 w-4 border-slate-300 text-[#2f6fbd] focus:ring-[#2f6fbd]">
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100">
                                                <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 8h16l-2 12H6Z"/><path d="M9 8a3 3 0 0 1 6 0"/><path d="M9 14h6"/></svg>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-black">Pagar pedido completo</span>
                                                <span class="mt-1 block text-xs text-slate-500">Realiza el pago total ahora y recibe tu pedido sin pagos pendientes.</span>
                                            </span>
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-4 rounded-lg border border-slate-100 bg-white px-5 py-4 shadow-sm">
                                            <input type="radio" name="warehouse_payment_type" class="h-4 w-4 border-slate-300 text-[#2f6fbd] focus:ring-[#2f6fbd]">
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100">
                                                <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 7h14v12H5Z"/><path d="M8 7V5h8v2"/><path d="M12 10v6"/></svg>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-black">Reservar con S/5</span>
                                                <span class="mt-1 block text-xs text-slate-500">Separa tu pedido hoy y paga el saldo restante al recogerlo.</span>
                                            </span>
                                        </label>
                                    </div>
                                    <p class="mt-4 rounded-md bg-blue-50 px-3 py-2 text-xs text-slate-600">Te notificaremos por WhatsApp cuando tu pedido este listo para recoger.</p>
                                </div>
                            </div>
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

        <div data-shalom-agency-modal class="fixed inset-0 z-[90] hidden overflow-y-auto bg-black/70 px-4 py-6 text-slate-950 sm:px-6" role="dialog" aria-modal="true" aria-labelledby="shalom-agency-modal-title">
            <div class="mx-auto flex min-h-full w-full max-w-[820px] items-center">
                <div class="relative w-full rounded-3xl bg-white px-5 py-6 shadow-2xl sm:px-8">
                    <button type="button" data-shalom-agency-modal-close class="absolute right-5 top-5 grid h-10 w-10 place-items-center rounded-full text-slate-700 transition hover:bg-slate-100" aria-label="Cerrar modal">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>

                    <header class="pr-12">
                        <div class="flex items-start gap-3">
                            <svg class="mt-1 h-6 w-6 shrink-0 text-slate-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 21s7-4.7 7-11a7 7 0 1 0-14 0c0 6.3 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                            <div>
                                <h2 id="shalom-agency-modal-title" class="text-xl font-black sm:text-2xl">Selecciona tu ubicacion</h2>
                                <p class="mt-1 text-sm font-medium leading-6 text-slate-500 sm:text-base">Busca una direccion o mueve el pin para marcar el punto exacto de entrega.</p>
                            </div>
                        </div>
                    </header>

                    <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_356px]">
                        <div class="relative h-[430px] overflow-hidden rounded-2xl bg-slate-100">
                            <div data-shalom-agency-map class="h-full w-full"></div>
                            <div data-shalom-agency-map-loading class="absolute inset-0 grid place-items-center bg-slate-100 text-center text-sm font-bold text-slate-500">
                                Cargando mapa...
                            </div>
                        </div>

                        <div class="flex min-h-0 flex-col">
                            <label class="flex h-10 items-center gap-2 rounded-lg border border-slate-200 px-3 text-slate-400 focus-within:border-[#2f6fbd] focus-within:ring-2 focus-within:ring-blue-100">
                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                                <input data-shalom-agency-search type="text" class="h-full min-w-0 flex-1 border-0 bg-transparent text-sm font-medium text-slate-700 outline-none placeholder:text-slate-400" placeholder="Ej. Shalom Rimac">
                                <button type="button" data-shalom-agency-search-clear class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-slate-400 text-white transition hover:bg-slate-600" aria-label="Limpiar busqueda">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                </button>
                            </label>

                            <div class="mt-4 max-h-[376px] space-y-3 overflow-y-auto pr-1">
                                @foreach ([
                                    ['Shalom Rimac', 'Av. Amancaes 644, Lima 15094', '-12.0346', '-77.0290', '1.2 Km'],
                                    ['Shalom Los Olivos', 'Av. Alfredo Mendiola 3698, Los Olivos', '-11.9687', '-77.0746', '2.6 Km'],
                                    ['Shalom Comas', 'Av. Tupac Amaru 3205, Comas', '-11.9491', '-77.0584', '3.8 Km'],
                                    ['Shalom Independencia', 'Av. Carlos Izaguirre 275, Independencia', '-11.9894', '-77.0615', '4.1 Km'],
                                    ['Shalom San Martin', 'Av. Peru 3490, San Martin de Porres', '-12.0147', '-77.0782', '5.4 Km'],
                                ] as $index => [$name, $address, $lat, $lng, $distance])
                                    <button
                                        type="button"
                                        data-shalom-agency-option
                                        data-name="{{ $name }}"
                                        data-address="{{ $address }}"
                                        data-lat="{{ $lat }}"
                                        data-lng="{{ $lng }}"
                                        class="flex w-full items-center gap-4 rounded-lg border bg-white px-4 py-4 text-left transition hover:border-red-300 {{ $index === 0 ? 'border-red-300 bg-red-50/30' : 'border-slate-100' }}"
                                        aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                                    >
                                        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-red-600 text-[10px] font-black italic text-white">SHALOM</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-center gap-3">
                                                <span class="block truncate text-sm font-black">{{ $name }}</span>
                                                @if ($index === 0)
                                                    <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-black">{{ $distance }}</span>
                                                @endif
                                            </span>
                                            <span class="mt-2 block truncate text-xs font-medium text-slate-600">{{ $address }}</span>
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <button type="button" data-shalom-agency-modal-close class="h-12 rounded-lg border border-slate-200 bg-white px-5 text-sm font-black uppercase transition hover:border-slate-950">Cancelar</button>
                        <button type="button" data-shalom-agency-confirm class="h-12 rounded-lg bg-[#111] px-5 text-sm font-black uppercase text-white transition hover:bg-[#2f6fbd]">Confirmar ubicacion</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    @if (config('services.google_maps.key'))
        <script>
            window.initCheckoutAgencyMap = function () {
                window.dispatchEvent(new Event('checkout-agency-map-ready'));
            };
        </script>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initCheckoutAgencyMap"></script>
    @endif
@endsection
