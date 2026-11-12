@extends('web.checkout.layout', ['step' => 3])

{{--
    Pago con tarjeta: formulario incrustado de Izipay (REST V4). El servidor ya creó el pago y pasa el formToken;
    los datos de la tarjeta van directo del navegador a Izipay. Al aprobarse, Izipay envía el resultado firmado a
    web.checkout.paid (kr-post-url-success) y, por su lado, a web.izipay.ipn; solo la firma válida confirma el pedido.
    En modo simulación (PAYMENTS_SIMULATE, solo local/testing) se muestran botones de prueba en su lugar.
--}}

@section('title', 'Pago con tarjeta | Overshark')

@push('head')
    @if ($formToken)
        <link rel="stylesheet" href="{{ \App\Services\Payments\IzipayGateway::themeCssUrl() }}">
        <script src="{{ \App\Services\Payments\IzipayGateway::scriptUrl() }}"
            kr-public-key="{{ $publicKey }}"
            kr-post-url-success="{{ route('web.checkout.paid', $order) }}"
            kr-language="es-ES"></script>
        <script src="{{ \App\Services\Payments\IzipayGateway::themeScriptUrl() }}"></script>
    @endif
@endpush

@section('checkout')
    <h1 class="text-[30px] font-extrabold leading-[1.45] text-[#0b1015] sm:text-[36px]">PAGO CON TARJETA</h1>

    <div class="flex max-w-[640px] flex-col gap-6 text-[#0b1015]">
        <p class="text-[15px] leading-[1.5]">
            Pedido #{{ $order->displayNumber() }} · Pagas <strong>S/ {{ number_format((float) $order->pago_monto, 2) }}</strong>@if ($order->isCashOnDelivery() && (float) $order->separo > 0) como separo; el saldo de S/ {{ number_format((float) $order->precio_total - (float) $order->separo, 2) }} lo pagas al recibir @endif.
            Tu pedido se reserva hasta las {{ $order->pago_qr_expira?->format('H:i') }}.
        </p>

        @if ($errors->any())
            <p class="bg-[#fdecec] px-4 py-3 text-[14px] font-bold text-[#a3261c]" role="alert">{{ $errors->first() }}</p>
        @endif

        @if ($error)
            <p class="bg-[#fdecec] px-4 py-3 text-[14px] font-bold text-[#a3261c]" role="alert">{{ $error }}</p>
            <a href="{{ route('web.checkout.pay', $order) }}" class="os-checkout-btn os-checkout-btn--ghost os-focus w-full sm:w-[280px]">INTENTAR DE NUEVO</a>
        @elseif ($simulating)
            <div class="border-2 border-dashed border-[#742384] bg-[#faf6fb] px-4 py-4 text-[13px] leading-[1.5] text-[#3d0a49]" role="note">
                <p class="font-extrabold">MODO SIMULACIÓN · no se cobra dinero real</p>
                <p>En producción aquí aparece el formulario seguro de Izipay.</p>
                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                    @foreach (['approved' => 'PAGAR (APROBADO)', 'declined' => 'PAGAR (RECHAZADO)'] as $result => $label)
                        <form method="POST" action="{{ route('web.checkout.simulate-payment', $order) }}">
                            @csrf
                            <input type="hidden" name="result" value="{{ $result }}">
                            <button type="submit" @class(['os-checkout-btn os-focus w-full sm:w-[240px]', 'os-checkout-btn--ghost' => $result === 'declined'])>{{ $label }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
        @elseif ($formToken)
            <div class="kr-smart-form" kr-card-form-expanded kr-form-token="{{ $formToken }}"></div>
        @endif

        <p class="text-[12px] leading-[1.45] text-[#65717c]">Pago seguro procesado por Izipay. Overshark no ve ni guarda los datos de tu tarjeta.</p>
        <a href="{{ route('web.checkout.done', $order) }}" class="text-[13px] font-bold underline underline-offset-4">Ver el estado de mi pedido</a>
    </div>
@endsection
