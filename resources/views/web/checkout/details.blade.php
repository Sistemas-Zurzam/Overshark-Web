@extends('web.checkout.layout', ['step' => 2])

{{--
    C2 · Datos y entrega: compra como invitado, entrega a domicilio (solo Lima), recojo en agencia o recojo en
    almacén (gratis, sin dirección), dirección (departamento → provincia → distrito) y comprobante. Se guarda en sesión (web.checkout.personal.store).
    Selects en cascada, agencias y factura: resources/js/checkout.js.
--}}

@section('title', 'Datos y entrega | Overshark')

@push('head')
    @vite('resources/js/checkout.js')
@endpush

@php
    $value = fn (string $field, $default = '') => old($field, $customer[$field] ?? $default);
    $distritoId = (int) $value('distrito_id', 0);
    $delivery = $value('delivery', 'domicilio');
    $invoice = $value('invoice', 'boleta');
    // Costo de cada entrega (S/ 15 domicilio, S/ 30 agencia Shalom, almacén gratis; 0 con envío gratis).
    $shippingCosts = \App\Support\Shipping::costsFor($subtotal);
    $costLabel = fn (string $delivery) => $shippingCosts[$delivery] > 0 ? 'S/ '.number_format($shippingCosts[$delivery], 2) : 'Gratis';
    $pickupAddress = \App\Support\Shipping::pickupAddress();
@endphp

@section('checkout')
    <h1 class="text-[30px] font-extrabold leading-[1.45] text-[#0b1015] sm:text-[36px]">DATOS Y ENTREGA</h1>

    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_448px] lg:gap-12">
        <form method="POST" action="{{ route('web.checkout.personal.store') }}" class="flex flex-col gap-6 text-[#0b1015]" novalidate
            data-checkout-details data-ubigeo='@json($ubigeo)' data-distrito="{{ $distritoId }}" data-agencies-url="{{ route('web.checkout.courier-agencies') }}" data-google-maps-key="{{ config('services.google_maps.key') }}">
            @csrf

            @if ($errors->any())
                <div class="bg-[#fdecec] px-4 py-3 text-[14px] text-[#a3261c]" role="alert">
                    <p class="font-bold">Revisa estos datos:</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <section class="flex flex-col gap-4" aria-labelledby="ck-guest">
                <div>
                    @if (auth('customer')->check())
                        <h2 id="ck-guest" class="text-[20px] font-bold leading-[1.45]">Tus datos</h2>
                        <p class="mt-1 text-[13px] text-[#65717c]">Comprando como {{ auth('customer')->user()->email }}. Puedes cambiar los datos de este pedido sin modificar tu perfil.</p>
                    @else
                        <h2 id="ck-guest" class="text-[20px] font-bold leading-[1.45]">Compra como invitado</h2>
                        <p class="mt-1 text-[13px] text-[#65717c]">Usaremos estos datos para enviarte tu pedido. <a href="{{ route('login') }}" class="font-bold text-[#2467cf] hover:underline">¿Ya tienes cuenta? Inicia sesión</a> para usar tus datos guardados.</p>
                    @endif
                </div>
                <label class="os-ck-field">Correo electrónico
                    <input type="email" name="email" value="{{ $value('email') }}" required autocomplete="email" placeholder="nombre@correo.com" class="os-ck-input">
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="os-ck-field">Nombres
                        <input type="text" name="nombres" value="{{ $value('nombres') }}" required autocomplete="given-name" placeholder="Ingresa tus nombres" class="os-ck-input">
                    </label>
                    <label class="os-ck-field">Apellidos
                        <input type="text" name="apellidos" value="{{ $value('apellidos') }}" required autocomplete="family-name" placeholder="Ingresa tus apellidos" class="os-ck-input">
                    </label>
                    <label class="os-ck-field">Celular
                        <input type="tel" name="phone" value="{{ $value('phone') }}" required autocomplete="tel-national" inputmode="numeric" maxlength="9" placeholder="999 999 999" class="os-ck-input" data-digits-only>
                    </label>
                    <div class="os-ck-field">
                        <span id="ck-doc">DNI / CE</span>
                        <div class="grid grid-cols-[88px_minmax(0,1fr)] gap-2" role="group" aria-labelledby="ck-doc">
                            <select name="doc_type" class="os-ck-input" aria-label="Tipo de documento">
                                @foreach (['DNI', 'CE'] as $docType)
                                    <option value="{{ $docType }}" @selected($value('doc_type', 'DNI') === $docType)>{{ $docType }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="doc_number" value="{{ $value('doc_number') }}" required inputmode="numeric" maxlength="8" placeholder="8 dígitos" class="os-ck-input" aria-label="Número de documento" data-doc-number="doc_type">
                        </div>
                    </div>
                </div>
            </section>

            <fieldset class="flex flex-col gap-3">
                <legend class="mb-3 text-[20px] font-bold leading-[1.45]">Entrega</legend>
                <label class="os-ck-option" data-ck-home>
                    <input type="radio" name="delivery" value="domicilio" @checked($delivery === 'domicilio') class="os-ck-radio">
                    <span class="flex flex-col gap-1">
                        <span class="text-[15px] font-bold">Envío a domicilio — {{ $costLabel('domicilio') }}</span>
                        <span class="text-[12px] text-[#65717c]" data-ck-home-note>Disponible dentro del área de cobertura marcada en el mapa.</span>
                    </span>
                </label>
                <label class="os-ck-option">
                    <input type="radio" name="delivery" value="agencia" @checked($delivery === 'agencia') class="os-ck-radio">
                    <span class="flex flex-col gap-1">
                        <span class="text-[15px] font-bold">Recojo en agencia Shalom — {{ $costLabel('agencia') }}</span>
                        <span class="text-[12px] text-[#65717c]">Lima y provincias. Enviamos con Shalom; recoges con tu DNI en la agencia que elijas. Te avisamos cuando llegue.</span>
                    </span>
                </label>
                <label class="os-ck-option">
                    <input type="radio" name="delivery" value="almacen" @checked($delivery === 'almacen') class="os-ck-radio">
                    <span class="flex flex-col gap-1">
                        <span class="text-[15px] font-bold">Recojo en almacén — Gratis</span>
                        <span class="text-[12px] text-[#65717c]">
                            {{ $pickupAddress !== '' ? $pickupAddress.'.' : 'Te enviamos la dirección por WhatsApp.' }}
                            Te avisamos cuando tu pedido esté listo para recoger.
                        </span>
                    </span>
                </label>
            </fieldset>

            <section @class(['flex flex-col gap-4', 'hidden' => $delivery === 'almacen']) aria-labelledby="ck-address" data-ck-address>
                <h2 id="ck-address" class="text-[20px] font-bold leading-[1.45]">Dirección de envío</h2>
                @if (($savedAddresses ?? collect())->isNotEmpty())
                    <div class="flex flex-col gap-2" aria-label="Tus direcciones guardadas">
                        <p class="text-[13px] font-bold text-[#65717c]">Tus direcciones guardadas (delivery a domicilio)</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($savedAddresses as $saved)
                                <a href="{{ route('web.checkout.personal', ['direccion' => $saved->id]) }}" @class(['os-focus flex flex-col gap-0.5 border p-3 text-[13px] leading-[1.45] transition hover:border-[#0b1015]', 'border-[#0b1015] bg-[#f5f7f9]' => $selectedAddressId === $saved->id, 'border-[#dae0e5]' => $selectedAddressId !== $saved->id]) @if ($selectedAddressId === $saved->id) aria-current="true" @endif>
                                    <span class="font-extrabold">{{ $saved->alias }}@if ($saved->is_default) <span class="font-bold text-[#65717c]">· Principal</span>@endif</span>
                                    <span>{{ $saved->address }}</span>
                                    <span class="text-[#65717c]">{{ $saved->placeLabel() }}</span>
                                </a>
                            @endforeach
                        </div>
                        <p class="text-[12px] text-[#65717c]">Al elegir una, recargamos esta página con sus datos. Si prefieres otra dirección, escríbela abajo.</p>
                    </div>
                @endif
                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="os-ck-field">Departamento
                        <select class="os-ck-input" data-ck-departamento required><option value="">Elige</option></select>
                    </label>
                    <label class="os-ck-field">Provincia
                        <select class="os-ck-input" data-ck-provincia required disabled><option value="">Elige</option></select>
                    </label>
                    <label class="os-ck-field">Distrito
                        <select name="distrito_id" class="os-ck-input" data-ck-distrito required disabled><option value="">Selecciona tu distrito</option></select>
                    </label>
                </div>
                <div @class(['flex flex-col gap-4', 'hidden' => $delivery === 'agencia']) data-ck-address-fields>
                    <label class="os-ck-field">Dirección
                        <input type="text" name="address" value="{{ $value('address') }}" autocomplete="street-address" placeholder="Calle, número, interior o departamento" class="os-ck-input">
                    </label>
                    <button type="button" class="os-checkout-btn os-checkout-btn--ghost os-focus !w-auto !px-4" data-delivery-address-search @class(['hidden' => $delivery !== 'domicilio'])>Buscar dirección en el mapa</button>
                    <p class="hidden text-[12px] font-semibold text-[#a3261c]" data-delivery-switch-hint>La dirección está fuera de cobertura. Elige una agencia Shalom o recojo en almacén.</p>
                    <label class="os-ck-field">Referencia (opcional)
                        <input type="text" name="reference" value="{{ $value('reference') }}" placeholder="Ayúdanos a encontrar tu dirección" class="os-ck-input">
                    </label>
                </div>
                <section @class(['flex flex-col gap-3 rounded-lg border border-[#d7dfe5] p-4', 'hidden' => $delivery !== 'domicilio']) data-delivery-coverage>
                    <div>
                        <h3 class="text-[15px] font-bold">Confirma el punto de entrega</h3>
                        <p class="text-[12px] leading-5 text-[#65717c]">Mueve el pin a tu dirección exacta. El delivery está disponible dentro del área marcada.</p>
                    </div>
                    <div class="h-[300px] overflow-hidden rounded-md" data-delivery-map aria-label="Mapa de cobertura de delivery"></div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" class="os-checkout-btn os-checkout-btn--ghost os-focus !w-auto !px-4 !py-2 text-[12px]" data-delivery-locate>Usar mi ubicación</button>
                        <button type="button" class="os-checkout-btn os-focus !w-auto !px-4 !py-2 text-[12px]" data-delivery-switch-agency hidden>Cambiar a agencia Shalom</button>
                        <p class="text-[12px] font-semibold text-[#65717c]" data-delivery-coverage-status aria-live="polite">Marca tu dirección en el mapa.</p>
                    </div>
                    <input type="hidden" name="delivery_lat" value="{{ $value('delivery_lat') }}" data-delivery-lat>
                    <input type="hidden" name="delivery_lng" value="{{ $value('delivery_lng') }}" data-delivery-lng>
                    @if (auth('customer')->check())
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[13px]">
                            <label class="flex cursor-pointer items-center gap-2"><input type="checkbox" name="save_address" value="1" class="os-ck-radio" @checked(old('save_address'))> Guardar esta dirección en mi cuenta</label>
                            <label class="flex items-center gap-2">como
                                <select name="address_alias" class="os-ck-input !h-9 !w-auto" aria-label="Nombre de la dirección guardada">
                                    @foreach (['Casa', 'Trabajo', 'Otro'] as $alias)<option value="{{ $alias }}" @selected(old('address_alias') === $alias)>{{ $alias }}</option>@endforeach
                                </select>
                            </label>
                        </div>
                    @endif
                </section>
                <div class="os-ck-field hidden" data-ck-agency-field>
                    <span id="ck-agency-label">Agencia Shalom de recojo</span>
                    <input type="search" class="os-ck-input" placeholder="Busca por distrito, avenida o nombre de la agencia" aria-label="Buscar agencia Shalom" autocomplete="off" data-ck-agency-search>
                    <select name="agency" class="os-ck-input" aria-labelledby="ck-agency-label" data-ck-agency data-selected="{{ old('agency', $customer['agency_id'] ?? '') }}">
                        <option value="">Elige primero tu distrito</option>
                    </select>
                    <p class="text-[12px] text-[#65717c]" data-ck-agency-detail aria-live="polite"></p>
                </div>
            </section>

            <fieldset class="flex flex-col gap-3">
                <legend class="mb-3 text-[14px] font-bold">Comprobante</legend>
                <div class="flex gap-6 text-[14px] font-bold">
                    @foreach (['boleta' => 'Boleta', 'factura' => 'Factura'] as $invoiceValue => $invoiceLabel)
                        <label class="flex cursor-pointer items-center gap-2"><input type="radio" name="invoice" value="{{ $invoiceValue }}" @checked($invoice === $invoiceValue) class="os-ck-radio" data-ck-invoice> {{ $invoiceLabel }}</label>
                    @endforeach
                </div>
                <div @class(['grid gap-4 sm:grid-cols-2', 'hidden' => $invoice !== 'factura']) data-ck-company>
                    <label class="os-ck-field">RUC
                        <input type="text" name="ruc" value="{{ $value('ruc') }}" inputmode="numeric" maxlength="11" placeholder="20XXXXXXXXX" class="os-ck-input">
                    </label>
                    <label class="os-ck-field">Razón social
                        <input type="text" name="razon_social" value="{{ $value('razon_social') }}" placeholder="Nombre de la empresa" class="os-ck-input">
                    </label>
                </div>
            </fieldset>

            <div class="flex flex-col gap-3">
                <button type="submit" class="os-checkout-btn os-focus w-full">CONTINUAR AL PAGO</button>
                <a href="{{ route('web.cart.index') }}" class="os-checkout-btn os-checkout-btn--ghost os-focus w-full">VOLVER AL CARRITO</a>
            </div>
        </form>

        @component('web.checkout.partials.summary', compact('items', 'count', 'subtotal', 'shipping', 'total', 'shippingCosts', 'priceChanges'))
        @endcomponent
    </div>
@endsection
