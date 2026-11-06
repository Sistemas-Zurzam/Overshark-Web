@extends('web.checkout.layout')

{{--
    Estado del pedido: C7 · Estamos confirmando tu pago (pendiente) o C6 · ¡Tu pedido está confirmado! (cuando el
    pedido pasa a "Pago confirmado"). Pendiente no es aprobado: solo el estado del servidor lo cambia.
    Con Yape, mientras se espera el pago, muestra el QR (y en modo simulación, "Simular pago").
--}}

@section('title', ($paid ? 'Pedido confirmado' : ($awaitingQr ? 'Paga con Yape' : 'Pedido recibido')).' | Overshark')

@php
    $title = fn ($text) => \App\Support\ProductName::title((string) $text);
    $cashOnDelivery = $order->isCashOnDelivery();
    $separo = 'S/ '.number_format((float) $order->separo, 2);
    $saldo = 'S/ '.number_format((float) $order->precio_total - (float) $order->separo, 2);
@endphp

@section('checkout')
    @if ($awaitingQr)
        <h1 class="text-[28px] font-extrabold leading-[1.45] text-[#0b1015] sm:text-[36px]">PAGA CON YAPE</h1>
        <section class="flex flex-col items-center gap-5 border-2 border-[#742384] p-6 text-center text-[#0b1015] sm:flex-row sm:items-center sm:gap-10 sm:p-10 sm:text-left" aria-label="QR de Yape">
            <img src="{{ $order->pago_qr }}" alt="QR para pagar con Yape" width="220" height="220" class="h-[220px] w-[220px] shrink-0 bg-white">
            <div class="flex flex-col gap-3">
                <p class="text-[14px] font-extrabold text-[#742384]">ESCANEA CON YAPE</p>
                <p class="text-[26px] font-extrabold leading-[1.2]">S/ {{ number_format((float) $order->pago_monto, 2) }}</p>
                <p class="text-[15px] leading-[1.45]">Abre Yape, toca <strong>Escanear QR</strong> y confirma el pago. Tu pedido se confirma solo{{ $cashOnDelivery ? '; el saldo de '.$saldo.' lo pagas al recibir' : '' }}.</p>
                <p class="text-[13px] text-[#65717c]">El QR vence a las {{ $order->pago_qr_expira?->format('H:i') }}. Si vence, el pedido se cancela y puedes volver a comprar.</p>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ route('web.checkout.done', $order) }}" class="os-checkout-btn os-checkout-btn--ghost os-focus w-full sm:w-[240px]">YA PAGUÉ · ACTUALIZAR</a>
                    @if ($simulating)
                        <form method="POST" action="{{ route('web.checkout.simulate-qr', $order) }}">
                            @csrf
                            <button type="submit" class="os-checkout-btn os-focus w-full bg-[#742384] sm:w-[240px]">SIMULAR PAGO CON YAPE</button>
                        </form>
                    @endif
                </div>
            </div>
        </section>
    @elseif ($cancelled)
        <h1 class="text-[28px] font-extrabold leading-[1.45] text-[#0b1015] sm:text-[36px]">PEDIDO CANCELADO</h1>
        <p class="max-w-[900px] text-[18px] leading-[1.45]">El pedido #{{ $order->displayNumber() }} se canceló{{ $order->pago_qr_expira ? ' porque el QR de pago venció' : '' }}. No se hizo ningún cobro. Puedes volver a la tienda y comprar de nuevo.</p>
        <a href="{{ route('web.home') }}" class="os-checkout-btn os-focus w-full sm:w-[320px]">VOLVER A LA TIENDA</a>
    @else
    @php
        $confirmed = $paid || $depositPaid;
        $pickup = $order->entrega === \App\Support\Shipping::PICKUP;
        $lines = collect($order->productos_json ?? []);
        $units = $lines->sum(fn ($line) => (int) ($line['cantidad'] ?? 1));
        $shippingCost = (float) $order->envio;
        $whatsappUrl = \App\Models\BrandSetting::current()->whatsappUrl();
        $paymentStepDone = $confirmed || ($cashOnDelivery && (float) $order->separo <= 0);
        $shipDate = $order->entrega === 'domicilio' && $confirmed && $order->fecha_envio
            ? \App\Support\Checkout::shippingDateLabel($order->fecha_envio->toDateString())
            : null;
        $steps = [
            ['Pedido recibido', 'Ya tenemos tu compra registrada.', true],
            [
                $confirmed ? 'Pago confirmado' : ($paymentStepDone ? 'Pagas al recibir' : 'Confirmando tu pago'),
                $confirmed ? 'Gracias, tu pago fue aprobado.' : ($paymentStepDone ? 'No necesitas pagar nada ahora.' : 'No vuelvas a pagar; te avisaremos al confirmarlo.'),
                $paymentStepDone,
            ],
            ['Preparamos tu pedido', 'Lo alistamos y empacamos con cuidado.', false],
            [
                $pickup ? 'Recoge tu pedido' : 'Te lo entregamos',
                $pickup ? 'Te avisaremos por WhatsApp cuando esté listo para recoger.' : ($shipDate ? 'Fecha de envío: '.$shipDate.'. ' : '').'Entrega estimada: 2 a 4 días hábiles.',
                false,
            ],
        ];
        $card = 'border border-[#dae0e5] bg-white p-5 sm:p-6';
    @endphp

    <section class="flex flex-col items-center gap-4 border border-[#dae0e5] bg-[#f5f7f9] px-5 py-10 text-center text-[#0b1015] sm:px-10 sm:py-12" aria-label="Estado del pedido">
        <span @class(['grid h-16 w-16 place-items-center rounded-full', 'bg-[#e3f4ec] text-[#147d5b]' => $confirmed, 'bg-[#e6eefc] text-[#2467cf]' => ! $confirmed]) aria-hidden="true">
            @if ($confirmed)
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
            @else
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>
            @endif
        </span>
        <p @class(['rounded-full px-3 py-1 text-[12px] font-extrabold tracking-[0.5px]', 'bg-[#e3f4ec] text-[#147d5b]' => $confirmed, 'bg-[#e6eefc] text-[#2467cf]' => ! $confirmed])>
            {{ $paid ? 'PAGO APROBADO' : ($depositPaid ? 'SEPARO PAGADO' : ($cashOnDelivery ? 'PAGO CONTRAENTREGA' : 'PAGO EN PROCESO')) }}
        </p>
        <h1 class="text-[28px] font-extrabold leading-[1.2] sm:text-[38px]">{{ $paid ? '¡TU PEDIDO ESTÁ CONFIRMADO!' : ($cashOnDelivery ? '¡RECIBIMOS TU PEDIDO!' : 'ESTAMOS CONFIRMANDO TU PAGO') }}</h1>
        @if ($paid)
            <p class="max-w-[640px] text-[17px] leading-[1.5] sm:text-[18px]">Gracias por comprar en Overshark. Ya estamos preparando tu pedido.</p>
        @elseif ($depositPaid)
            <p class="max-w-[640px] text-[17px] leading-[1.5] sm:text-[18px]">Gracias por tu compra. Recibimos tu separo de <strong>{{ $separo }}</strong>. El saldo de {{ $saldo }} lo pagas al recibir tu pedido; te escribiremos por WhatsApp para coordinar la entrega.</p>
        @elseif ($cashOnDelivery && (float) $order->separo > 0)
            <p class="max-w-[640px] text-[17px] leading-[1.5] sm:text-[18px]">Gracias por tu compra. Estamos verificando la captura de tu separo de <strong>{{ $separo }}</strong>; no vuelvas a pagar. Despachamos tu pedido cuando se confirme y el saldo de {{ $saldo }} lo pagas al recibir.</p>
        @elseif ($cashOnDelivery)
            <p class="max-w-[640px] text-[17px] leading-[1.5] sm:text-[18px]">Gracias por tu compra. Pagas al recibir tu pedido; te escribiremos por WhatsApp para coordinar la entrega.</p>
        @else
            <p class="max-w-[640px] text-[17px] leading-[1.5] sm:text-[18px]">Tu pedido está pendiente. No vuelvas a pagar mientras verificamos tu captura. Te avisaremos al confirmar la operación.</p>
        @endif
        <p class="border border-[#dae0e5] bg-white px-4 py-2 text-[15px] font-bold sm:text-[16px]">Pedido #{{ $order->displayNumber() }} · {{ $units }} {{ $units === 1 ? 'prenda' : 'prendas' }} · {{ $paid ? 'Total pagado' : 'Total' }} S/ {{ number_format((float) $order->precio_total, 2) }}</p>
        <p class="max-w-[640px] text-[14px] leading-[1.45] text-[#65717c] sm:text-[15px]">Te enviaremos los detalles del pedido y las novedades del envío a <strong class="text-[#0b1015]">{{ $order->email }}</strong>.</p>
    </section>

    <div class="grid gap-6 text-[#0b1015] lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start">
        <div class="flex flex-col gap-6">
            <section class="{{ $card }}" aria-labelledby="next-steps">
                <h2 id="next-steps" class="text-[18px] font-extrabold">¿Qué sigue?</h2>
                <ol class="mt-5 flex flex-col">
                    @foreach ($steps as [$stepTitle, $stepText, $stepDone])
                        @php($lineDone = $stepDone && ($steps[$loop->index + 1][2] ?? false))
                        <li class="relative flex gap-4 pb-6 last:pb-0">
                            @unless ($loop->last)
                                <span @class(['absolute left-[13px] top-7 h-[calc(100%-1.75rem)] w-px', 'bg-[#147d5b]' => $lineDone, 'bg-[#dae0e5]' => ! $lineDone]) aria-hidden="true"></span>
                            @endunless
                            <span @class(['relative z-[1] grid h-7 w-7 shrink-0 place-items-center rounded-full border-2 text-[12px] font-extrabold', 'border-[#147d5b] bg-[#147d5b] text-white' => $stepDone, 'border-[#dae0e5] bg-white text-[#65717c]' => ! $stepDone]) aria-hidden="true">
                                @if ($stepDone)
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </span>
                            <div class="leading-[1.45]">
                                <p class="text-[15px] font-extrabold">{{ $stepTitle }}</p>
                                <p class="text-[14px] text-[#65717c]">{{ $stepText }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="{{ $card }}" aria-labelledby="order-items">
                <h2 id="order-items" class="text-[18px] font-extrabold">Tu pedido</h2>
                <ul class="mt-2 flex flex-col">
                    @foreach ($lines as $line)
                        @php($isCombo = ($line['tipo'] ?? '') === 'combo')
                        <li class="flex gap-4 border-b border-[#eef1f4] py-4 last:border-b-0 sm:gap-5">
                            <img src="{{ $line['imagen'] ?? asset('images/default-hero-banner.png') }}" alt="" class="h-[96px] w-[76px] shrink-0 bg-[#f5f7f9] object-cover">
                            <div class="flex min-w-0 flex-1 flex-col gap-1 leading-[1.45]">
                                <p class="text-[15px] font-bold">{{ $isCombo ? ($line['producto'] ?? 'Combo') : $title($line['producto'] ?? 'Producto') }}@if (($line['cantidad'] ?? 1) > 1) × {{ $line['cantidad'] }}@endif</p>
                                @if ($isCombo)
                                    <p class="text-[13px] text-[#65717c]">Combo · {{ count($line['selecciones'] ?? []) }} prendas</p>
                                    @foreach (collect($line['selecciones'] ?? [])->take(6) as $selection)
                                        <p class="truncate text-[12px] text-[#65717c]">{{ $title($selection['producto'] ?? '') }} · {{ $title($selection['color'] ?? '') }} · {{ $selection['talla'] ?? '-' }}</p>
                                    @endforeach
                                @else
                                    <p class="text-[13px] text-[#65717c]">{{ $title($line['color'] ?? '') }} · Talla {{ $line['talla'] ?? '-' }}</p>
                                @endif
                                <p class="mt-auto text-[16px] font-extrabold">S/ {{ number_format((float) ($line['precio'] ?? 0) * (int) ($line['cantidad'] ?? 1), 2) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        <aside class="flex flex-col gap-6 lg:sticky lg:top-[112px]">
            <section class="{{ $card }}" aria-labelledby="order-summary">
                <h2 id="order-summary" class="text-[18px] font-extrabold">Resumen</h2>
                <dl class="mt-4 flex flex-col gap-2 text-[15px]">
                    <div class="flex justify-between"><dt class="text-[#65717c]">Subtotal</dt><dd class="font-bold">S/ {{ number_format((float) $order->subtotal, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-[#65717c]">Envío</dt><dd class="font-bold">{{ $shippingCost > 0 ? 'S/ '.number_format($shippingCost, 2) : 'Gratis' }}</dd></div>
                    <div class="mt-2 flex items-baseline justify-between border-t border-[#dae0e5] pt-3"><dt class="text-[16px] font-extrabold">Total</dt><dd class="text-[22px] font-extrabold">S/ {{ number_format((float) $order->precio_total, 2) }}</dd></div>
                    @if ($cashOnDelivery && (float) $order->separo > 0)
                        <div class="flex justify-between text-[#147d5b]"><dt>{{ $depositPaid ? 'Separo pagado' : 'Separo' }}</dt><dd class="font-bold">{{ $separo }}</dd></div>
                        <div class="flex justify-between"><dt class="text-[#65717c]">Saldo al recibir</dt><dd class="font-bold">{{ $saldo }}</dd></div>
                    @elseif ($cashOnDelivery)
                        <div class="flex justify-between"><dt class="text-[#65717c]">Pagas al recibir</dt><dd class="font-bold">S/ {{ number_format((float) $order->precio_total, 2) }}</dd></div>
                    @elseif ($paid)
                        <div class="flex justify-between text-[#147d5b]"><dt>Pagado en línea</dt><dd class="font-bold">S/ {{ number_format((float) $order->precio_total, 2) }}</dd></div>
                    @endif
                </dl>
                @if ($order->metodo_pago_nombre)
                    <p class="mt-4 border-t border-[#eef1f4] pt-3 text-[13px] text-[#65717c]">Método de pago: <span class="font-bold text-[#0b1015]">{{ $order->metodo_pago_nombre }}</span></p>
                @endif
                @if ($order->tipo_comprobante)
                    <p class="mt-1 text-[13px] text-[#65717c]">Tu {{ $order->tipo_comprobante === 'factura' ? 'factura' : 'boleta' }} electrónica te llegará por correo cuando se emita.</p>
                @endif
            </section>

            <section class="{{ $card }}" aria-labelledby="order-delivery">
                <h2 id="order-delivery" class="text-[18px] font-extrabold">Entrega</h2>
                <p class="mt-3 text-[15px] leading-[1.5]">{{ \App\Support\Shipping::deliveryLabel($order->entrega, $order->agencia, $order->direccion) }}</p>
                @if ($pickup)
                    <p class="mt-2 text-[14px] text-[#65717c]">Te avisaremos por WhatsApp cuando tu pedido esté listo para recoger.</p>
                @else
                    @if ($shipDate)
                        <p class="mt-2 text-[14px] text-[#65717c]">Fecha de envío: {{ $shipDate }}</p>
                    @endif
                    <p class="mt-1 text-[14px] text-[#65717c]">Entrega estimada: 2 a 4 días hábiles</p>
                @endif
            </section>

            <div class="flex flex-col gap-3">
                <a href="{{ route('web.home') }}" class="os-checkout-btn os-focus w-full">SEGUIR COMPRANDO</a>
                @unless ($confirmed)
                    <a href="{{ route('web.checkout.done', $order) }}" class="os-checkout-btn os-checkout-btn--ghost os-focus w-full">ACTUALIZAR ESTADO</a>
                @endunless
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="os-checkout-btn os-checkout-btn--ghost os-focus w-full">¿DUDAS? ESCRÍBENOS POR WHATSAPP</a>
                @endif
            </div>
        </aside>
    </div>
    @endif
@endsection
