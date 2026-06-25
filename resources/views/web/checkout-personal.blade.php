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
                            <button type="button" data-location-modal-open class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-4 text-xs font-black transition hover:border-slate-950" aria-haspopup="dialog">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s6-4.3 6-10a6 6 0 1 0-12 0c0 5.7 6 10 6 10Z"/><circle cx="12" cy="11" r="2"/></svg>
                                Seleccionar ubicacion
                            </button>
                            <input type="hidden" name="delivery_location" data-selected-location-value value="">
                            <input type="hidden" name="delivery_lat" data-selected-location-lat value="">
                            <input type="hidden" name="delivery_lng" data-selected-location-lng value="">
                        </div>
                    </section>

                    <label class="inline-flex items-start gap-2 text-xs font-medium text-slate-600">
                        <input name="terms" type="checkbox" required checked class="mt-0.5 h-4 w-4 rounded border-slate-300 bg-slate-950 text-slate-950 focus:ring-[#2f6fbd]">
                        <span>Acepto los <a href="#" class="font-bold underline">Terminos y Condiciones</a> y la <a href="#" class="font-bold underline">Politica de Privacidad</a></span>
                    </label>

                    <a href="{{ route('web.checkout.delivery') }}" class="inline-flex h-12 w-full items-center justify-center gap-4 rounded-md bg-[#111] px-5 text-sm font-black uppercase tracking-normal text-white transition hover:bg-[#2f6fbd]">
                        Continuar
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
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

        <div data-location-modal class="fixed inset-0 z-[90] hidden overflow-y-auto bg-black/70 px-4 py-6 text-slate-950 sm:px-6" role="dialog" aria-modal="true" aria-labelledby="location-modal-title">
            <div class="mx-auto flex min-h-full w-full max-w-[1180px] items-center">
                <div class="relative w-full rounded-3xl bg-white px-5 py-6 shadow-2xl sm:px-10 sm:py-9">
                    <button type="button" data-location-modal-close class="absolute right-5 top-5 grid h-10 w-10 place-items-center rounded-full text-slate-700 transition hover:bg-slate-100 sm:right-8 sm:top-8" aria-label="Cerrar modal">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>

                    <header class="pr-12">
                        <div class="flex items-start gap-3">
                            <svg class="mt-1 h-8 w-8 shrink-0 text-slate-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 21s7-4.7 7-11a7 7 0 1 0-14 0c0 6.3 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                            <div>
                                <h2 id="location-modal-title" class="text-2xl font-black sm:text-3xl">Selecciona tu ubicacion</h2>
                                <p class="mt-2 text-base font-medium leading-6 text-slate-500 sm:text-xl">Busca una direccion o mueve el pin para marcar el punto exacto de entrega.</p>
                            </div>
                        </div>
                    </header>

                    <label class="mt-8 flex h-12 items-center gap-3 rounded-lg border border-slate-200 px-4 text-slate-400 focus-within:border-[#2f6fbd] focus-within:ring-2 focus-within:ring-blue-100">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                        <input data-location-search type="text" class="h-full min-w-0 flex-1 border-0 bg-transparent text-base font-medium text-slate-700 outline-none placeholder:text-slate-400" placeholder="Ej. Av. Universitaria 200">
                        <button type="button" data-location-search-clear class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-slate-400 text-white transition hover:bg-slate-600" aria-label="Limpiar busqueda">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                    </label>

                    <div class="relative mt-9 h-[340px] overflow-hidden rounded-2xl bg-slate-100 sm:h-[540px]">
                        <div data-location-map class="h-full w-full"></div>
                        <div data-location-map-loading class="absolute inset-0 grid place-items-center bg-slate-100 text-center text-sm font-bold text-slate-500">
                            Cargando mapa...
                        </div>
                    </div>

                    <div class="mt-8 flex items-center gap-5 rounded-2xl border border-slate-200 px-5 py-5 sm:px-8">
                        <div class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-slate-100">
                            <svg class="h-12 w-12 text-[#2f6fbd]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 1.8a7.6 7.6 0 0 0-7.6 7.6c0 5.7 7.6 12.8 7.6 12.8s7.6-7.1 7.6-12.8A7.6 7.6 0 0 0 12 1.8Zm0 10.7a3.1 3.1 0 1 1 0-6.2 3.1 3.1 0 0 1 0 6.2Z"/></svg>
                        </div>
                        <div>
                            <p class="text-lg font-black">Direccion seleccionada</p>
                            <p data-selected-location-label class="mt-2 text-base font-medium text-slate-700">Akpana 1261, Lima 15427</p>
                        </div>
                    </div>

                    <div class="mt-9 grid gap-4 sm:grid-cols-2 sm:gap-6">
                        <button type="button" data-location-modal-close class="h-16 rounded-lg border border-slate-200 bg-white px-5 text-base font-black uppercase transition hover:border-slate-950">Cancelar</button>
                        <button type="button" data-location-confirm class="h-16 rounded-lg bg-[#111] px-5 text-base font-black uppercase text-white transition hover:bg-[#2f6fbd]">Confirmar ubicacion</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    @if (config('services.google_maps.key'))
        <script>
            window.initCheckoutLocationMap = function () {
                window.dispatchEvent(new Event('checkout-location-map-ready'));
            };
        </script>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=initCheckoutLocationMap"></script>
    @endif
@endsection
