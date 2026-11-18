@extends('web.checkout.layout', ['step' => 3])

{{--
    Pago con Mercado Pago dentro de la tienda (MercadoPago.js v2 con la Public Key), con dos opciones:
     - Tarjeta: Card Payment Brick (Checkout Bricks).
     - Yape: celular + código de aprobación (OTP) de la app Yape; mp.yape({otp, phoneNumber}).create() da el token.
    En ambos casos Mercado Pago devuelve un token de un solo uso (el navegador nunca manda datos de la tarjeta ni de
    Yape a Overshark) y web.checkout.mercadopago.charge crea el pago por el importe del pedido y lo confirma con la API.
--}}

@section('title', 'Pago | Overshark')

@push('head')
    <script src="https://sdk.mercadopago.com/js/v2"></script>
@endpush

@section('checkout')
    <h1 class="text-[30px] font-extrabold leading-[1.45] text-[#0b1015] sm:text-[36px]">PAGO</h1>

    <div class="flex max-w-[640px] flex-col gap-6 text-[#0b1015]">
        <p class="text-[15px] leading-[1.5]">
            Pedido #{{ $order->displayNumber() }} · Pagas <strong>S/ {{ number_format((float) $order->pago_monto, 2) }}</strong>@if ($order->isCashOnDelivery() && (float) $order->separo > 0) como separo; el saldo de S/ {{ number_format((float) $order->precio_total - (float) $order->separo, 2) }} lo pagas al recibir @endif.
            Tu pedido se reserva hasta las {{ $order->pago_qr_expira?->format('H:i') }}.
        </p>

        @if ($errors->any())
            <p class="bg-[#fdecec] px-4 py-3 text-[14px] font-bold text-[#a3261c]" role="alert">{{ $errors->first() }}</p>
        @endif

        <div class="grid grid-cols-2 gap-3" role="radiogroup" aria-label="Cómo quieres pagar">
            <label class="os-ck-option !items-center !py-3">
                <input type="radio" name="mp_option" value="card" checked class="os-ck-radio" data-mp-option>
                <span class="flex-1 text-[15px] font-bold">Tarjeta</span>
            </label>
            <label class="os-ck-option !items-center !py-3">
                <input type="radio" name="mp_option" value="yape" class="os-ck-radio" data-mp-option>
                <span class="flex-1 text-[15px] font-bold">Yape</span>
            </label>
        </div>

        <p class="hidden bg-[#fdecec] px-4 py-3 text-[14px] font-bold text-[#a3261c]" role="alert" data-mp-error></p>

        {{-- Tarjeta de crédito o débito --}}
        <div data-mp-panel="card">
            <p class="text-[14px] text-[#65717c]" data-mp-loading>Cargando el formulario seguro de Mercado Pago…</p>
            <div id="mp-card-brick"></div>
        </div>

        {{-- Yape: celular y código de aprobación de la app --}}
        <form class="hidden flex-col gap-4" data-mp-panel="yape" data-mp-yape-form novalidate>
            <p class="text-[14px] leading-[1.5]">Abre tu app <strong>Yape</strong>, entra a <strong>Código de aprobación</strong> en el menú y escribe aquí el código de 6 dígitos junto con tu celular. El código vence en unos minutos.</p>
            <label class="flex flex-col gap-1.5">
                <span class="text-[14px] font-bold">Celular registrado en Yape</span>
                <input type="tel" inputmode="numeric" autocomplete="tel-national" maxlength="9" placeholder="9XXXXXXXX" required
                    class="os-ck-input" data-mp-yape-phone>
            </label>
            <label class="flex flex-col gap-1.5">
                <span class="text-[14px] font-bold">Código de aprobación</span>
                <input type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="6 dígitos" required
                    class="os-ck-input tracking-[4px]" data-mp-yape-otp>
            </label>
            <button type="submit" class="os-checkout-btn os-focus w-full" data-mp-yape-submit>PAGAR S/ {{ number_format((float) $order->pago_monto, 2) }} CON YAPE</button>
            <p class="text-[12px] leading-[1.45] text-[#65717c]">Yape tiene un límite por pago (S/ 500, 900 o 2000, según lo configuraste en tu app).</p>
        </form>

        <p class="text-[12px] leading-[1.45] text-[#65717c]">Pago seguro procesado por Mercado Pago. Overshark no ve ni guarda los datos de tu tarjeta ni de tu Yape.</p>
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
            const hideError = () => errorBox.classList.add('hidden');

            // Pestañas Tarjeta / Yape.
            document.querySelectorAll('[data-mp-option]').forEach((radio) => radio.addEventListener('change', () => {
                hideError();
                document.querySelectorAll('[data-mp-panel]').forEach((panel) => {
                    const visible = panel.dataset.mpPanel === radio.value;
                    panel.classList.toggle('hidden', ! visible);
                    panel.classList.toggle('flex', visible && panel.tagName === 'FORM');
                });
            }));

            // Envía el token al servidor, que cobra. Resuelve si el pago terminó (y redirige); si no, muestra el motivo.
            const charge = (payload) => fetch(config.chargeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ ...payload, device_id: window.MP_DEVICE_SESSION_ID || null }),
            })
                .then(async (response) => {
                    const data = await response.json().catch(() => ({}));
                    if (response.ok && data.redirect) {
                        window.location.href = data.redirect;
                        return true;
                    }
                    showError(data.message || 'No pudimos procesar el pago. Inténtalo de nuevo.');
                    return false;
                })
                .catch(() => {
                    showError('Se perdió la conexión al pagar. Antes de intentar de nuevo, revisa el estado de tu pedido para no pagar dos veces.');
                    return false;
                });

            if (typeof window.MercadoPago !== 'function') {
                loading.classList.add('hidden');
                showError('No pudimos cargar el formulario de Mercado Pago. Revisa tu conexión y recarga la página.');
                return;
            }

            const mp = new window.MercadoPago(config.publicKey, { locale: 'es-PE' });

            // Tarjeta: Card Payment Brick.
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
                    onSubmit: (formData) => {
                        hideError();
                        return charge({ ...formData, method: 'card' }).then((ok) => (ok ? undefined : Promise.reject()));
                    },
                    onError: (error) => {
                        console.error('Mercado Pago', error);
                        if (error?.type === 'critical') {
                            loading.classList.add('hidden');
                            showError('No pudimos cargar el formulario de tarjeta. Recarga la página o paga con Yape.');
                        }
                    },
                },
            });

            // Yape: el SDK cambia celular + código de aprobación por un token de un solo uso.
            const yapeForm = document.querySelector('[data-mp-yape-form]');
            const phoneInput = yapeForm.querySelector('[data-mp-yape-phone]');
            const otpInput = yapeForm.querySelector('[data-mp-yape-otp]');
            const yapeButton = yapeForm.querySelector('[data-mp-yape-submit]');
            [phoneInput, otpInput].forEach((input) => input.addEventListener('input', () => {
                input.value = input.value.replace(/\D+/g, '');
            }));

            yapeForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                hideError();
                const phoneNumber = phoneInput.value.trim();
                const otp = otpInput.value.trim();
                if (! /^9\d{8}$/.test(phoneNumber)) {
                    showError('Escribe tu celular de 9 dígitos (empieza con 9).');
                    phoneInput.focus();
                    return;
                }
                if (! /^\d{6}$/.test(otp)) {
                    showError('Escribe el código de aprobación de 6 dígitos de tu app Yape.');
                    otpInput.focus();
                    return;
                }

                yapeButton.disabled = true;
                const label = yapeButton.textContent;
                yapeButton.textContent = 'PROCESANDO…';
                try {
                    const result = await mp.yape({ otp, phoneNumber }).create();
                    const token = typeof result === 'string' ? result : result?.id;
                    if (! token) {
                        throw new Error('Yape sin token');
                    }
                    const ok = await charge({
                        method: 'yape',
                        token,
                        payment_method_id: 'yape',
                        installments: 1,
                        payer: { email: config.email || null },
                    });
                    if (ok) {
                        return;
                    }
                } catch (error) {
                    console.error('Yape', error);
                    showError('No pudimos validar tu Yape. Revisa tu celular y genera un código de aprobación nuevo en la app.');
                }
                // El código se usa una sola vez: para reintentar hay que generar otro.
                otpInput.value = '';
                yapeButton.disabled = false;
                yapeButton.textContent = label;
            });
        })();
    </script>
@endsection
