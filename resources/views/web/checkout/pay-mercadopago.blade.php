@extends('web.checkout.layout', ['step' => 3])

{{--
    Pago con tarjeta de Mercado Pago: Card Payment Brick (Checkout Bricks, MercadoPago.js v2) con la Public Key de la
    tienda. Los datos de la tarjeta van del navegador a Mercado Pago, que devuelve un token de un solo uso; ese token
    se envía a web.checkout.mercadopago.charge, que crea el pago por el importe del pedido y lo confirma con la API.
--}}

@section('title', 'Pago con tarjeta | Overshark')

@push('head')
    <script src="https://sdk.mercadopago.com/js/v2"></script>
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

        <p class="hidden bg-[#fdecec] px-4 py-3 text-[14px] font-bold text-[#a3261c]" role="alert" data-mp-error></p>
        <p class="text-[14px] text-[#65717c]" data-mp-loading>Cargando el formulario seguro de Mercado Pago…</p>
        <div id="mp-card-brick"></div>

        <p class="text-[12px] leading-[1.45] text-[#65717c]">Pago seguro procesado por Mercado Pago. Overshark no ve ni guarda los datos de tu tarjeta.</p>
        <a href="{{ route('web.checkout.done', $order) }}" class="text-[13px] font-bold underline underline-offset-4">Ver el estado de mi pedido</a>
    </div>

    @php
        $mpConfig = [
            'publicKey' => $publicKey,
            'amount' => round((float) $order->pago_monto, 2),
            'email' => (string) $order->email,
            'chargeUrl' => route('web.checkout.mercadopago.charge', $order),
        ];
    @endphp
    <script>
        (function () {
            const config = {{ \Illuminate\Support\Js::from($mpConfig) }};
            const errorBox = document.querySelector('[data-mp-error]');
            const loading = document.querySelector('[data-mp-loading]');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            const showError = (message) => {
                errorBox.textContent = message;
                errorBox.classList.remove('hidden');
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            };

            if (typeof window.MercadoPago !== 'function') {
                loading.classList.add('hidden');
                showError('No pudimos cargar el formulario de Mercado Pago. Revisa tu conexión y recarga la página.');
                return;
            }

            const mp = new window.MercadoPago(config.publicKey, { locale: 'es-PE' });
            mp.bricks().create('cardPayment', 'mp-card-brick', {
                initialization: {
                    amount: config.amount,
                    payer: config.email ? { email: config.email } : undefined,
                },
                customization: {
                    paymentMethods: { maxInstallments: 12 },
                    visual: { style: { theme: 'default' } },
                },
                callbacks: {
                    onReady: () => loading.classList.add('hidden'),
                    onSubmit: (formData) => new Promise((resolve, reject) => {
                        errorBox.classList.add('hidden');
                        fetch(config.chargeUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify({ ...formData, device_id: window.MP_DEVICE_SESSION_ID || null }),
                        })
                            .then(async (response) => {
                                const data = await response.json().catch(() => ({}));
                                if (response.ok && data.redirect) {
                                    resolve();
                                    window.location.href = data.redirect;
                                    return;
                                }
                                showError(data.message || 'No pudimos procesar el pago. Inténtalo de nuevo.');
                                reject();
                            })
                            .catch(() => {
                                showError('Se perdió la conexión al pagar. Antes de intentar de nuevo, revisa el estado de tu pedido para no pagar dos veces.');
                                reject();
                            });
                    }),
                    onError: (error) => {
                        console.error('Mercado Pago', error);
                        if (error?.type === 'critical') {
                            loading.classList.add('hidden');
                            showError('No pudimos cargar el formulario de Mercado Pago. Recarga la página o elige otro medio de pago.');
                        }
                    },
                },
            });
        })();
    </script>
@endsection
