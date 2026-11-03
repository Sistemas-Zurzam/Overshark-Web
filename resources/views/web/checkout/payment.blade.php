@extends('web.checkout.layout', ['step' => 3])

{{--
    C3 · Elige cómo pagar + C5 · Paga con Yape o Plin, con el pago actual de la tienda: los medios activos del
    admin (número, titular y QR) con captura del pago, o pago contraentrega. El pedido se crea en
    web.checkout.place (token contra doble envío). Tarjeta y QR de Culqi quedan para cuando se integre Culqi.
    Captura y selección: resources/js/checkout.js.
--}}

@section('title', 'Pago | Overshark')

@push('head')
    @vite('resources/js/checkout.js')
@endpush

@php
    $title = fn ($text) => \Illuminate\Support\Str::title(mb_strtolower((string) $text));
    $selected = old('method', $methods->first()['id'] ?? null);
    $deliveryText = ($customer['delivery'] ?? '') === 'agencia'
        ? 'Recojo en agencia: '.($customer['agency'] ?? '')
        : 'Envío a domicilio: '.($customer['address'] ?? '');
@endphp

@section('checkout')
    <h1 class="text-[30px] font-extrabold leading-[1.45] text-[#0b1015] sm:text-[36px]">ELIGE CÓMO PAGAR</h1>

    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_448px] lg:gap-12">
        <form method="POST" action="{{ route('web.checkout.place') }}" class="flex flex-col gap-6 text-[#0b1015]"
            data-checkout-payment data-receipt-url="{{ route('web.checkout.receipt') }}" data-has-receipt="{{ $receiptPath ? '1' : '0' }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="terms" value="1">

            <div class="flex flex-col gap-1 bg-[#f5f7f9] p-5 leading-[1.45]">
                <p class="text-[16px] font-bold">Datos y dirección guardados</p>
                <p class="text-[14px] text-[#65717c]">{{ $title(($customer['nombres'] ?? '').' '.($customer['apellidos'] ?? '')) }} · {{ $customer['email'] ?? '' }} · {{ $customer['phone'] ?? '' }}</p>
                <p class="text-[14px] text-[#65717c]">{{ $deliveryText }}</p>
                <a href="{{ route('web.checkout.personal') }}" class="os-focus mt-1 self-start text-[13px] font-bold text-[#2467cf] hover:underline">Editar datos de entrega</a>
            </div>

            @if ($errors->any())
                <p class="bg-[#fdecec] px-4 py-3 text-[14px] font-bold text-[#a3261c]" role="alert">{{ $errors->first() }}</p>
            @endif

            <fieldset class="flex flex-col gap-3">
                <legend class="mb-3 text-[14px] text-[#65717c]">Revisa tu pedido y elige tu medio de pago.</legend>
                @foreach ($methods as $method)
                    <label class="os-ck-method">
                        <input type="radio" name="method" value="{{ $method['id'] }}" @checked($selected === $method['id']) class="sr-only" data-ck-method data-receipt="{{ $method['receipt'] ? '1' : '0' }}">
                        <span class="flex items-center gap-3">
                            @if ($method['logo'])
                                <img src="{{ $method['logo'] }}" alt="" class="h-7 w-7 object-contain">
                            @endif
                            <span class="text-[13px] font-bold uppercase">{{ $method['name'] }}</span>
                        </span>
                    </label>
                @endforeach
            </fieldset>

            @foreach ($methods as $method)
                <section @class(['flex flex-col gap-5 bg-[#f5f7f9] p-6 sm:p-8', 'hidden' => $selected !== $method['id']]) data-ck-panel="{{ $method['id'] }}" aria-label="{{ $method['name'] }}">
                    @if ($method['receipt'])
                        <h2 class="text-[22px] font-extrabold leading-[1.45] sm:text-[26px]">Paga S/ {{ number_format($total, 2) }} con {{ $method['name'] }}</h2>
                        <ol class="list-decimal pl-5 text-[15px] leading-[1.45]">
                            <li>Abre {{ $method['name'] }}{{ $method['qr'] ? ' y escanea el QR' : '' }}{{ $method['number'] ? ($method['qr'] ? ' o paga al número ' : ' y paga al número ').$method['number'] : '' }}.</li>
                            <li>Confirma el importe exacto: S/ {{ number_format($total, 2) }}.</li>
                            <li>Sube aquí la captura del pago. Verificamos tu pago y te confirmamos el pedido.</li>
                        </ol>
                        <div class="flex flex-wrap items-start gap-6">
                            @if ($method['qr'])
                                <img src="{{ $method['qr'] }}" alt="QR de {{ $method['name'] }}" class="h-[200px] w-[200px] bg-white object-contain p-3">
                            @endif
                            @if ($method['number'])
                                <div class="flex flex-col gap-1 leading-[1.45]">
                                    <p class="text-[12px] font-bold text-[#65717c]">NÚMERO</p>
                                    <p class="text-[22px] font-extrabold">{{ $method['number'] }}</p>
                                    @if ($method['owner'])<p class="text-[13px] text-[#65717c]">A nombre de {{ $method['owner'] }}</p>@endif
                                    <button type="button" class="os-focus mt-1 self-start text-[13px] font-bold text-[#2467cf] hover:underline" data-ck-copy="{{ $method['number'] }}">Copiar número</button>
                                </div>
                            @endif
                        </div>
                    @else
                        <h2 class="text-[22px] font-extrabold leading-[1.45] sm:text-[26px]">Pagas S/ {{ number_format($total, 2) }} al recibir tu pedido</h2>
                        <p class="text-[15px] leading-[1.45]">Puedes pagar en efectivo, Yape o Plin cuando recibas o recojas tu pedido. Te escribiremos por WhatsApp para coordinar la entrega.</p>
                    @endif
                </section>
            @endforeach

            {{-- Captura del pago (para los medios con número o QR) --}}
            <div @class(['flex flex-col gap-3', 'hidden' => ! ($methods->firstWhere('id', $selected)['receipt'] ?? false)]) data-ck-receipt>
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

            <p class="text-[13px] leading-[1.45] text-[#65717c]">Al confirmar aceptas los Términos y condiciones y la Política de privacidad.</p>
            <div class="flex flex-col gap-3">
                <button type="submit" class="os-checkout-btn os-focus w-full" data-ck-submit>CONFIRMAR PEDIDO</button>
                <a href="{{ route('web.checkout.personal') }}" class="os-checkout-btn os-checkout-btn--ghost os-focus w-full">EDITAR DATOS DE ENTREGA</a>
            </div>
        </form>

        @component('web.checkout.partials.summary', compact('items', 'count', 'subtotal', 'shipping', 'total'))
        @endcomponent
    </div>
@endsection
