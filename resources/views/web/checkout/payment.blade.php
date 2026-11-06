@extends('web.checkout.layout', ['step' => 3])

{{--
    C3 · Pago: resumen de envío (Enviar a / Método de envío / Comprobante, con "Editar") y métodos de pago en
    tarjetas que se despliegan (Checkout::paymentMethods): tarjeta de crédito o débito con Culqi (formulario en
    la página; checkout.js crea el token directo en Culqi), Yape con QR de Ligo Pay (pendiente de su API), los
    medios activos del admin con captura, y contraentrega, cuyo separo (S/ 15 domicilio, S/ 30 agencia;
    Checkout::depositFor) se paga aquí mismo con tarjeta o Yape. Lo no configurado se ve con un aviso.
    El pedido se crea en web.checkout.place (token contra doble envío y total confirmado).
--}}

@section('title', 'Pago | Overshark')

@push('head')
    @vite('resources/js/checkout.js')
@endpush

@php
    $title = fn ($text) => \App\Support\ProductName::title((string) $text);
    $selected = old('method', $methods->first()['id'] ?? null);
    // Con qué se puede pagar el separo (tarjeta, Yape y los medios del admin).
    $online = $methods->where('kind', '!=', 'cod')->values();
    $selectedDeposit = old('deposit_method', $online->first()['id'] ?? null);
    // ¿Hay que subir captura con lo elegido? (medio del admin para el total, o para el separo en contraentrega)
    $payWith = $selected === \App\Support\Checkout::CASH_ON_DELIVERY ? ($deposit > 0 ? $online->firstWhere('id', $selectedDeposit) : null) : $methods->firstWhere('id', $selected);
    $needsReceipt = (bool) ($payWith['receipt'] ?? false);
    $delivery = $customer['delivery'] ?? 'domicilio';
    $pickupAddress = \App\Support\Shipping::pickupAddress();
    $money = fn ($amount) => 'S/ '.number_format((float) $amount, 2);
    $editUrl = route('web.checkout.personal');
    // Aviso dentro de Tarjeta (sin llaves de Culqi) o Yape (Ligo Pay pendiente): se ve el diseño pero no se puede pagar.
    $notActive = fn (array $method) => $method['available'] ? '' : '<p class="bg-[#fff6e0] px-4 py-3 text-[13px] font-bold leading-[1.45] text-[#8a5a00]" role="note">'.($method['kind'] === 'yape' ? 'El pago con Yape (Ligo Pay) aún no está activo en la tienda.' : 'El pago con tarjeta (Culqi) aún no está activo en la tienda.').' Por ahora elige otro medio de pago.</p>';
@endphp

@section('checkout')
    <h1 class="text-[30px] font-extrabold leading-[1.45] text-[#0b1015] sm:text-[36px]">PAGO</h1>

    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_448px] lg:gap-12">
        <form method="POST" action="{{ route('web.checkout.place') }}" class="flex flex-col gap-10 text-[#0b1015]" novalidate
            data-checkout-payment data-receipt-url="{{ route('web.checkout.receipt') }}" data-has-receipt="{{ $receiptPath ? '1' : '0' }}"
            data-culqi-key="{{ $culqiKey }}" data-simulate="{{ $simulating ? '1' : '0' }}" data-culqi-url="{{ $culqiSecureUrl }}" data-email="{{ $customer['email'] ?? '' }}"
            data-total="{{ number_format($total, 2, '.', '') }}" data-deposit="{{ number_format($deposit, 2, '.', '') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="culqi_token" value="" data-ck-culqi-token>
            <input type="hidden" name="expected_total" value="{{ number_format($total, 2, '.', '') }}">

            @if ($simulating)
                <div class="border-2 border-dashed border-[#742384] bg-[#faf6fb] px-4 py-3 text-[13px] leading-[1.5] text-[#3d0a49]" role="note">
                    <p class="font-extrabold">MODO SIMULACIÓN · no se cobra dinero real</p>
                    <p>Tarjeta aprobada: <strong>4111 1111 1111 1111</strong> · rechazada: <strong>4000 0000 0000 0002</strong> · CVV 123 y cualquier fecha futura. Con Yape verás un QR de prueba y el botón "Simular pago".</p>
                </div>
            @endif

            @if ($errors->any())
                <p class="bg-[#fdecec] px-4 py-3 text-[14px] font-bold text-[#a3261c]" role="alert">{{ $errors->first() }}</p>
            @endif

            {{-- Envío y comprobante elegidos en "Datos y entrega" --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <section class="os-pay-box" aria-labelledby="pay-ship-to">
                    <div class="flex items-baseline justify-between gap-4">
                        <h2 id="pay-ship-to" class="os-pay-box__title">{{ $delivery === 'domicilio' ? 'Enviar a' : 'Recoge' }}</h2>
                        <a href="{{ $editUrl }}" class="os-pay-box__edit os-focus">Editar</a>
                    </div>
                    <p class="font-bold uppercase">{{ trim(($customer['nombres'] ?? '').' '.($customer['apellidos'] ?? '')) }}</p>
                    <p>{{ $customer['phone'] ?? '' }} · {{ $customer['email'] ?? '' }}</p>
                    @if ($delivery === 'almacen')
                        <p>{{ $pickupAddress !== '' ? $pickupAddress : 'En nuestro almacén. Te enviamos la dirección por WhatsApp.' }}</p>
                    @elseif ($delivery === 'agencia')
                        <p>{{ $customer['agency'] ?? '' }}</p>
                        @if ($place)<p class="uppercase">{{ $place }}</p>@endif
                    @else
                        <p>{{ $customer['address'] ?? '' }}@if (! empty($customer['reference'])) · {{ $customer['reference'] }}@endif</p>
                        @if ($place)<p class="uppercase">{{ $place }}</p>@endif
                    @endif
                </section>

                <div class="flex flex-col gap-4">
                    <section class="os-pay-box" aria-labelledby="pay-ship-method">
                        <div class="flex items-baseline justify-between gap-4">
                            <h2 id="pay-ship-method" class="os-pay-box__title">Método de envío</h2>
                            <a href="{{ $editUrl }}" class="os-pay-box__edit os-focus">Editar</a>
                        </div>
                        <p>
                            {{ match ($delivery) {
                                'agencia' => 'Recojo en agencia Shalom',
                                'almacen' => 'Recojo en almacén',
                                default => 'Envío a domicilio (2 a 4 días hábiles)',
                            } }}
                            · <span class="whitespace-nowrap">{{ $shipping > 0 ? $money($shipping) : 'Gratis' }}</span>
                        </p>
                        @if ($shippingDateLabel)
                            <p class="mt-2 text-[13px] font-bold">Fecha de envío estimada: {{ $shippingDateLabel }}</p>
                        @endif
                    </section>
                    <section class="os-pay-box" aria-labelledby="pay-invoice">
                        <div class="flex items-baseline justify-between gap-4">
                            <h2 id="pay-invoice" class="os-pay-box__title">Comprobante de pago</h2>
                            <a href="{{ $editUrl }}" class="os-pay-box__edit os-focus">Editar</a>
                        </div>
                        @if (($customer['invoice'] ?? 'boleta') === 'factura')
                            <p>Factura · RUC {{ $customer['ruc'] ?? '' }}</p>
                            <p class="uppercase">{{ $customer['razon_social'] ?? '' }}</p>
                        @else
                            <p>Boleta · {{ $customer['doc_type'] ?? 'DNI' }} {{ $customer['doc_number'] ?? '' }}</p>
                        @endif
                    </section>
                </div>
            </div>

            {{-- Métodos de pago: cada tarjeta se despliega al elegirla --}}
            <fieldset class="flex flex-col gap-3">
                <legend class="mb-4 text-[20px] font-extrabold uppercase leading-[1.45]">Método de pago</legend>

                @foreach ($methods as $method)
                    <div class="os-pay-option">
                        <label class="flex cursor-pointer items-center gap-4 px-5 py-5 sm:px-6">
                            <input type="radio" name="method" value="{{ $method['id'] }}" @checked($selected === $method['id']) class="os-ck-radio !h-5 !w-5"
                                data-ck-method data-kind="{{ $method['kind'] }}" data-receipt="{{ $method['receipt'] ? '1' : '0' }}" data-available="{{ $method['available'] ? '1' : '0' }}">
                            <span class="min-w-0 flex-1 text-[15px] font-medium">{{ $method['name'] }}</span>
                            @include('web.checkout.partials.method-badge', ['method' => $method])
                        </label>

                        <div @class(['flex flex-col gap-5 px-5 pb-6 sm:px-6', 'hidden' => $selected !== $method['id']]) data-ck-panel="{{ $method['id'] }}">
                            @if ($method['kind'] === 'card')
                                {!! $notActive($method) !!}
                                @include('web.checkout.partials.culqi-card-form', ['prefix' => 'card', 'customer' => $customer])
                            @elseif ($method['kind'] === 'yape')
                                {!! $notActive($method) !!}
                                @include('web.checkout.partials.ligo-yape-qr', ['amount' => $total])
                            @elseif ($method['kind'] === 'wallet')
                                <hr class="border-[#0b1015]">
                                <p class="text-[13px] font-extrabold uppercase tracking-[.5px]">Paga {{ $money($total) }} con {{ $method['name'] }}</p>
                                @include('web.checkout.partials.wallet-pay', ['method' => $method, 'amount' => $total, 'concept' => 'tu pedido'])
                            @elseif ($deposit > 0)
                                <hr class="border-[#0b1015]">
                                <p class="text-[13px] font-extrabold uppercase tracking-[.5px]">Separa tu pedido con {{ $money($deposit) }}</p>
                                <dl class="flex flex-col gap-2 text-[14px] leading-[1.45]">
                                    <div class="flex justify-between gap-4"><dt>Separo: lo pagas ahora</dt><dd class="shrink-0 whitespace-nowrap font-bold">{{ $money($deposit) }}</dd></div>
                                    <div class="flex justify-between gap-4"><dt>Saldo: lo pagas al recibir tu pedido</dt><dd class="shrink-0 whitespace-nowrap font-bold">{{ $money($total - $deposit) }}</dd></div>
                                </dl>

                                <div class="flex flex-col gap-2" role="radiogroup" aria-label="Cómo pagas el separo">
                                    <p class="text-[14px] font-bold">¿Cómo pagas el separo?</p>
                                    @foreach ($online as $option)
                                        <label class="os-ck-option !items-center !py-3">
                                            <input type="radio" name="deposit_method" value="{{ $option['id'] }}" @checked($selectedDeposit === $option['id']) class="os-ck-radio"
                                                data-ck-deposit data-kind="{{ $option['kind'] }}" data-receipt="{{ $option['receipt'] ? '1' : '0' }}" data-available="{{ $option['available'] ? '1' : '0' }}">
                                            <span class="flex-1 text-[14px] font-bold">{{ $option['name'] }}</span>
                                            @include('web.checkout.partials.method-badge', ['method' => $option])
                                        </label>
                                    @endforeach
                                </div>

                                @foreach ($online as $option)
                                    <div @class(['flex flex-col gap-3', 'hidden' => $selectedDeposit !== $option['id']]) data-ck-deposit-panel="{{ $option['id'] }}">
                                        {!! $notActive($option) !!}
                                        @if ($option['kind'] === 'card')
                                            @include('web.checkout.partials.culqi-card-form', ['prefix' => 'deposit-card', 'customer' => $customer])
                                        @elseif ($option['kind'] === 'yape')
                                            @include('web.checkout.partials.ligo-yape-qr', ['amount' => $deposit])
                                        @else
                                            @include('web.checkout.partials.wallet-pay', ['method' => $option, 'amount' => $deposit, 'concept' => 'tu separo'])
                                        @endif
                                    </div>
                                @endforeach
                                <p class="bg-[#f5f7f9] p-4 text-[13px] leading-[1.45] text-[#3c4650]">Despachamos tu pedido cuando se confirme el separo. El saldo lo pagas en efectivo, Yape o Plin al recibir.</p>
                            @else
                                <hr class="border-[#0b1015]">
                                <p class="text-[13px] font-extrabold uppercase tracking-[.5px]">Pagas {{ $money($total) }} al recoger</p>
                                <p class="text-[14px] leading-[1.45]">Sin separo: pagas en efectivo, Yape o Plin cuando recojas tu pedido en el almacén. Te avisamos por WhatsApp cuando esté listo.</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </fieldset>

            {{-- Captura del pago (medios del admin: del total o del separo) --}}
            <div @class(['-mt-6 flex flex-col gap-3', 'hidden' => ! $needsReceipt]) data-ck-receipt>
                <p class="text-[15px] font-bold">Captura de tu pago</p>
                <label class="os-ck-upload os-focus">
                    <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" data-ck-receipt-input>
                    <span class="text-[14px] font-bold" data-ck-receipt-label>{{ $receiptPath ? 'Captura subida ✓ · Cambiar' : 'Subir captura (JPG, PNG o WEBP)' }}</span>
                    <span class="text-[12px] text-[#65717c]" data-ck-receipt-status aria-live="polite"></span>
                </label>
                @if ($receiptPath)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($receiptPath) }}" alt="Captura del pago" class="max-h-48 w-auto self-start" data-ck-receipt-preview>
                @else
                    <img src="" alt="Captura del pago" class="hidden max-h-48 w-auto self-start" data-ck-receipt-preview>
                @endif
            </div>

            <div class="flex flex-col gap-4">
                <label class="flex cursor-pointer items-start gap-3 text-[14px] leading-[1.45]">
                    <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 h-5 w-5 shrink-0 accent-[#0b1015]" data-ck-terms>
                    <span>He leído y acepto los Términos y condiciones y la Política de privacidad.</span>
                </label>
                <p class="hidden bg-[#fdecec] px-4 py-3 text-[14px] font-bold text-[#a3261c]" role="alert" data-ck-pay-error></p>
                <button type="submit" class="os-checkout-btn os-focus w-full" data-ck-submit>REALIZAR PEDIDO</button>
            </div>
        </form>

        @component('web.checkout.partials.summary', compact('items', 'count', 'subtotal', 'shipping', 'total', 'priceChanges'))
        @endcomponent
    </div>
@endsection
