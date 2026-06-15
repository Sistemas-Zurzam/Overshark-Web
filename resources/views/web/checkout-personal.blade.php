@extends('layouts.web')

@section('title', 'Datos personales | Overshark')

@section('content')
    <section class="bg-[#fbfbfb] px-4 py-7 text-slate-950 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-[1180px] gap-12 lg:grid-cols-[minmax(0,760px)_310px] lg:items-start lg:justify-center">
            <div>
                <div class="mx-auto grid max-w-md grid-cols-3 items-start gap-0 text-center text-xs font-medium text-slate-500">
                    @foreach ([['1', 'Datos personales', true], ['2', 'Entrega y pago', false], ['3', 'Confirmacion', false]] as [$step, $label, $active])
                        <div class="relative flex flex-col items-center gap-3 pb-4">
                            @if (! $loop->first)
                                <span class="absolute right-1/2 top-3.5 h-px w-full -translate-x-4 bg-slate-200"></span>
                            @endif
                            <span @class([
                                'relative z-10 grid h-7 w-7 place-items-center rounded-full border text-sm font-bold',
                                'border-[#2f6fbd] bg-[#2f6fbd] text-white' => $active,
                                'border-slate-200 bg-white text-slate-400' => ! $active,
                            ])>{{ $step }}</span>
                            <span @class(['text-slate-950' => $active])>{{ $label }}</span>
                            @if ($active)
                                <span class="absolute bottom-0 h-px w-32 bg-[#2f6fbd]"></span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <form class="mt-8 space-y-6">
                    <section class="rounded-2xl bg-white p-6 shadow-[0_18px_50px_rgba(15,23,42,0.04)] sm:p-7">
                        <div class="mb-5 flex items-center gap-3">
                            <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="7" r="3.5"/><path d="M5.5 21c.4-5 2.6-7.5 6.5-7.5s6.1 2.5 6.5 7.5"/></svg>
                            <h2 class="text-base font-black">Informacion personal</h2>
                        </div>

                        <div class="grid gap-x-7 gap-y-5 md:grid-cols-2">
                            <label class="block text-sm font-medium">
                                Nombres y apellidos <span class="text-red-500">*</span>
                                <input name="name" type="text" required autocomplete="name" class="mt-2 h-9 w-full rounded-md border border-slate-200 px-3 text-xs font-medium outline-none transition placeholder:text-slate-400 focus:border-[#2f6fbd] focus:ring-2 focus:ring-blue-100" placeholder="Juan Perez Gomez">
                            </label>

                            <div>
                                <p class="text-sm font-medium">Tipo y numero de documento <span class="text-red-500">*</span></p>
                                <div class="mt-2 grid grid-cols-[86px_1fr] gap-2">
                                    <select name="tipo_documento_id" required class="h-9 w-full rounded-md border border-slate-200 px-3 text-xs font-semibold outline-none transition focus:border-[#2f6fbd] focus:ring-2 focus:ring-blue-100">
                                        @forelse ($documentTypes as $documentType)
                                            <option value="{{ $documentType->id }}">{{ $documentType->code ?: $documentType->name }}</option>
                                        @empty
                                            <option value="">DNI</option>
                                        @endforelse
                                    </select>
                                    <input name="documento_identidad" type="text" required inputmode="numeric" class="h-9 w-full rounded-md border border-slate-200 px-3 text-xs font-medium outline-none transition placeholder:text-slate-400 focus:border-[#2f6fbd] focus:ring-2 focus:ring-blue-100" placeholder="75351525">
                                </div>
                            </div>

                            <label class="block text-sm font-medium">
                                Correo electronico
                                <input name="email" type="email" required autocomplete="email" class="mt-2 h-9 w-full rounded-md border border-slate-200 px-3 text-xs font-medium outline-none transition placeholder:text-slate-400 focus:border-[#2f6fbd] focus:ring-2 focus:ring-blue-100" placeholder="Ej. correo@ejemplo.com">
                            </label>

                            <label class="block text-sm font-medium">
                                Telefono / Celular <span class="text-red-500">*</span>
                                <input name="phone" type="tel" required autocomplete="tel" class="mt-2 h-9 w-full rounded-md border border-slate-200 px-3 text-xs font-medium outline-none transition placeholder:text-slate-400 focus:border-[#2f6fbd] focus:ring-2 focus:ring-blue-100" placeholder="Ej. 920332344">
                            </label>
                        </div>

                        <label class="mt-5 inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                            <input name="invoice" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-[#2f6fbd]">
                            Deseo factura
                        </label>
                    </section>

                    <section class="rounded-2xl bg-white p-6 shadow-[0_18px_50px_rgba(15,23,42,0.04)] sm:p-7">
                        <div class="mb-5 flex items-center gap-3">
                            <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 21s7-4.7 7-11a7 7 0 1 0-14 0c0 6.3 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                            <h2 class="text-base font-black">Datos de envio</h2>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <label class="block text-sm font-medium">
                                Departamento <span class="text-red-500">*</span>
                                <select name="departamento_id" required class="mt-2 h-9 w-full rounded-md border border-slate-200 px-3 text-xs font-medium outline-none transition focus:border-[#2f6fbd] focus:ring-2 focus:ring-blue-100">
                                    <option value="">Seleccionar</option>
                                    @foreach ($departamentos as $departamento)
                                        <option value="{{ $departamento->id }}">{{ $departamento->name }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block text-sm font-medium">
                                Provincia <span class="text-red-500">*</span>
                                <select name="provincia_id" required class="mt-2 h-9 w-full rounded-md border border-slate-200 px-3 text-xs font-medium outline-none transition focus:border-[#2f6fbd] focus:ring-2 focus:ring-blue-100">
                                    @foreach ($provincias as $provincia)
                                        <option value="{{ $provincia->id }}">{{ $provincia->name }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block text-sm font-medium">
                                Distrito <span class="text-red-500">*</span>
                                <select name="distrito_id" required class="mt-2 h-9 w-full rounded-md border border-slate-200 px-3 text-xs font-medium outline-none transition focus:border-[#2f6fbd] focus:ring-2 focus:ring-blue-100">
                                    @foreach ($distritos as $distrito)
                                        <option value="{{ $distrito->id }}">{{ $distrito->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <label class="mt-5 block text-sm font-medium">
                            Direccion <span class="text-red-500">*</span>
                            <input name="address" type="text" required autocomplete="street-address" class="mt-2 h-9 w-full rounded-md border border-slate-200 px-3 text-xs font-medium outline-none transition placeholder:text-slate-400 focus:border-[#2f6fbd] focus:ring-2 focus:ring-blue-100" placeholder="Ej. Av. Universitaria 200">
                        </label>

                        <label class="mt-5 block text-sm font-medium">
                            Referencia
                            <input name="reference" type="text" class="mt-2 h-9 w-full rounded-md border border-slate-200 px-3 text-xs font-medium outline-none transition placeholder:text-slate-400 focus:border-[#2f6fbd] focus:ring-2 focus:ring-blue-100" placeholder="Ej. correo@ejemplo.com">
                        </label>

                        <div class="mt-6 flex flex-col gap-4 rounded-lg border border-blue-100 bg-blue-50/60 px-5 py-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex gap-3">
                                <svg class="h-10 w-10 shrink-0 text-[#2f6fbd]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 21s6-4.3 6-10a6 6 0 1 0-12 0c0 5.7 6 10 6 10Z"/><circle cx="12" cy="11" r="2"/><path d="M5 21h14"/></svg>
                                <div>
                                    <p class="text-sm font-black">Ubicacion exacta de entrega (Opcional)</p>
                                    <p class="mt-1 max-w-sm text-xs leading-5 text-slate-600">Selecciona tu ubicacion en el mapa para ayudarnos a encontrar tu direccion con mayor precision.</p>
                                </div>
                            </div>
                            <button type="button" class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-4 text-xs font-black transition hover:border-slate-950">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s6-4.3 6-10a6 6 0 1 0-12 0c0 5.7 6 10 6 10Z"/><circle cx="12" cy="11" r="2"/></svg>
                                Seleccionar ubicacion
                            </button>
                        </div>
                    </section>

                    <label class="inline-flex items-start gap-2 text-xs font-medium text-slate-600">
                        <input name="terms" type="checkbox" required checked class="mt-0.5 h-4 w-4 rounded border-slate-300 bg-slate-950 text-slate-950 focus:ring-[#2f6fbd]">
                        <span>Acepto los <a href="#" class="font-bold underline">Terminos y Condiciones</a> y la <a href="#" class="font-bold underline">Politica de Privacidad</a></span>
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
                    @if ($itemCount > 4)
                        <span class="grid h-9 w-9 place-items-center rounded-md bg-slate-100 text-xs font-black">+{{ $itemCount - 4 }}</span>
                    @endif
                </div>

                <div class="mt-5 space-y-4 border-b border-slate-100 pb-5 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-slate-600">Envio</span>
                        <span class="font-semibold">--------</span>
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
