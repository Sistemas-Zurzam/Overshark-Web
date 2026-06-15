@extends('layouts.web')

@section('title', 'Datos personales | Overshark')

@section('content')
    <section class="bg-white px-5 py-10 text-slate-950 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div>
                <div class="grid grid-cols-3 items-start gap-3 text-center text-xs font-bold text-slate-400 sm:max-w-2xl">
                    @foreach ([['1', 'Datos personales', true], ['2', 'Entrega y pago', false], ['3', 'Confirmacion', false]] as [$step, $label, $active])
                        <div class="relative flex flex-col items-center gap-2">
                            @if (! $loop->first)
                                <span class="absolute right-1/2 top-4 h-px w-full -translate-x-5 bg-slate-200"></span>
                            @endif
                            <span @class([
                                'relative z-10 grid h-8 w-8 place-items-center rounded-full border text-sm font-black',
                                'border-slate-950 bg-slate-950 text-white' => $active,
                                'border-slate-200 bg-white text-slate-400' => ! $active,
                            ])>{{ $step }}</span>
                            <span @class(['text-slate-950' => $active])>{{ $label }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    <h1 class="text-3xl font-black">Datos personales</h1>
                    <p class="mt-2 text-sm font-medium text-slate-500">Completa tus datos para continuar con tu pedido.</p>
                </div>

                <form class="mt-6 space-y-5">
                    <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                        <div class="mb-5 flex items-center gap-3">
                            <svg class="h-6 w-6 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="7" r="3.5"/><path d="M5.5 21c.4-5 2.6-7.5 6.5-7.5s6.1 2.5 6.5 7.5"/></svg>
                            <h2 class="text-base font-black">Informacion personal</h2>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="block text-sm font-bold">
                                Nombres y apellidos <span class="text-red-500">*</span>
                                <input name="name" type="text" required autocomplete="name" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100" placeholder="Juan Perez Gomez">
                            </label>

                            <div>
                                <p class="text-sm font-bold">Tipo y numero de documento <span class="text-red-500">*</span></p>
                                <div class="mt-2 grid grid-cols-[120px_1fr] gap-2">
                                    <select name="tipo_documento_id" required class="w-full rounded-lg border border-slate-200 px-3 py-3 text-sm font-medium outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100">
                                        @forelse ($documentTypes as $documentType)
                                            <option value="{{ $documentType->id }}">{{ $documentType->code ?: $documentType->name }}</option>
                                        @empty
                                            <option value="">DNI</option>
                                        @endforelse
                                    </select>
                                    <input name="documento_identidad" type="text" required inputmode="numeric" class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100" placeholder="75351525">
                                </div>
                            </div>

                            <label class="block text-sm font-bold">
                                Correo electronico <span class="text-red-500">*</span>
                                <input name="email" type="email" required autocomplete="email" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100" placeholder="juanperez@gmail.com">
                            </label>

                            <label class="block text-sm font-bold">
                                Telefono / Celular <span class="text-red-500">*</span>
                                <input name="phone" type="tel" required autocomplete="tel" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100" placeholder="920 332 344">
                            </label>
                        </div>

                        <label class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-slate-600">
                            <input name="invoice" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-cyan-500">
                            Deseo factura
                        </label>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                        <div class="mb-5 flex items-center gap-3">
                            <svg class="h-6 w-6 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 21s7-4.7 7-11a7 7 0 1 0-14 0c0 6.3 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                            <h2 class="text-base font-black">Datos de envio</h2>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <label class="block text-sm font-bold">
                                Departamento <span class="text-red-500">*</span>
                                <select name="departamento_id" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100">
                                    @forelse ($departamentos as $departamento)
                                        <option value="{{ $departamento->id }}">{{ $departamento->name }}</option>
                                    @empty
                                        <option value="">Seleccionar</option>
                                    @endforelse
                                </select>
                            </label>

                            <label class="block text-sm font-bold">
                                Provincia <span class="text-red-500">*</span>
                                <select name="provincia_id" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100">
                                    @foreach ($provincias as $provincia)
                                        <option value="{{ $provincia->id }}">{{ $provincia->name }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block text-sm font-bold">
                                Distrito <span class="text-red-500">*</span>
                                <select name="distrito_id" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100">
                                    @foreach ($distritos as $distrito)
                                        <option value="{{ $distrito->id }}">{{ $distrito->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <label class="mt-4 block text-sm font-bold">
                            Direccion <span class="text-red-500">*</span>
                            <input name="address" type="text" required autocomplete="street-address" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100" placeholder="Av. Universitaria 2200">
                        </label>

                        <label class="mt-4 block text-sm font-bold">
                            Referencia
                            <input name="reference" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100" placeholder="Frente a Plaza Vea, al lado de la puerta principal.">
                        </label>

                        <div class="mt-5 flex flex-col gap-4 rounded-lg border border-orange-200 bg-orange-50 px-4 py-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex gap-3">
                                <svg class="h-8 w-8 shrink-0 text-orange-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 21s6-4.3 6-10a6 6 0 1 0-12 0c0 5.7 6 10 6 10Z"/><circle cx="12" cy="11" r="2"/></svg>
                                <div>
                                    <p class="font-black">Tu direccion es dificil de encontrar?</p>
                                    <p class="mt-1 text-slate-600">Puedes seleccionar tu ubicacion exacta en el mapa.</p>
                                </div>
                            </div>
                            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-3 text-xs font-black transition hover:border-slate-950">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s6-4.3 6-10a6 6 0 1 0-12 0c0 5.7 6 10 6 10Z"/><circle cx="12" cy="11" r="2"/></svg>
                                Seleccionar ubicacion
                            </button>
                        </div>
                    </section>

                    <label class="inline-flex items-start gap-2 text-sm font-medium text-slate-600">
                        <input name="terms" type="checkbox" required class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-cyan-500">
                        <span>Acepto los <a href="#" class="font-bold underline">Terminos y Condiciones</a> y la <a href="#" class="font-bold underline">Politica de Privacidad</a></span>
                    </label>

                    <button type="button" class="btn-primary w-full px-5 py-4 sm:w-auto">
                        Continuar a entrega y pago
                    </button>
                </form>
            </div>

            <aside class="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-28">
                <h2 class="text-lg font-black">Resumen de compra</h2>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($items->take(4) as $item)
                        <div class="h-14 w-14 overflow-hidden rounded-md bg-slate-50">
                            <img src="{{ $item['image'] ?? asset('images/default-hero-banner.png') }}" alt="{{ $item['producto'] ?? 'Producto' }}" class="h-full w-full object-contain">
                        </div>
                    @endforeach
                    @if ($itemCount > 4)
                        <span class="grid h-14 w-14 place-items-center rounded-md bg-slate-100 text-sm font-black">+{{ $itemCount - 4 }}</span>
                    @endif
                </div>

                <div class="mt-6 space-y-4 border-b border-slate-100 pb-5 text-sm">
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
                    <span class="text-lg font-black">Total</span>
                    <span class="text-lg font-black">S/{{ number_format($total, 2) }}</span>
                </div>

                <button type="button" class="flex w-full items-center justify-between rounded-lg border border-slate-200 px-3 py-3 text-sm font-semibold transition hover:border-slate-950">
                    <span class="inline-flex items-center gap-3">
                        <img src="{{ asset('images/iconos/Ticket.svg') }}" alt="" class="h-5 w-5 object-contain" aria-hidden="true">
                        Tienes un cupon?
                    </span>
                    <span class="text-xl leading-none">+</span>
                </button>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <p class="text-sm font-black">Aceptamos</p>
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
            </aside>
        </div>
    </section>
@endsection
