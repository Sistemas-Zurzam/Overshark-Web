@extends('web.account.layout')

@section('title', 'Pedido '.$order->displayNumber().' | Overshark')
@section('heading', 'Pedido #'.$order->displayNumber())

@php
    $title = fn ($text) => \App\Support\ProductName::title((string) $text);
    $lines = collect($order->productos_json ?? []);
    $place = $order->distrito
        ? collect([$order->distrito->name, $order->distrito->provincia?->name, $order->distrito->provincia?->departamento?->name])->filter()->implode(', ')
        : null;
    $cod = $order->isCashOnDelivery();
    $card = 'border border-[#dae0e5] bg-white p-5';
@endphp

@section('account')
    <a href="{{ route('customer.orders') }}" class="os-focus text-[13px] font-bold text-[#2467cf] hover:underline">← Mis pedidos</a>

    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
        @include('web.account.partials.order-status', ['order' => $order])
        <p class="text-[14px] text-[#65717c]">Realizado el {{ $order->created_at?->format('d/m/Y \a \l\a\s H:i') }}</p>
    </div>

    <div class="mt-6 grid grid-cols-[minmax(0,1fr)] gap-6 xl:grid-cols-[minmax(0,1fr)_340px] xl:items-start">
        <section class="{{ $card }}" aria-labelledby="po-items">
            <h2 id="po-items" class="text-[18px] font-extrabold">Productos</h2>
            <ul class="mt-2 flex flex-col">
                @foreach ($lines as $line)
                    @php($isCombo = ($line['tipo'] ?? '') === 'combo')
                    <li class="flex gap-4 border-b border-[#eef1f4] py-4 last:border-b-0">
                        <img src="{{ $line['imagen'] ?? asset('images/default-hero-banner.png') }}" alt="" class="h-[96px] w-[76px] shrink-0 bg-[#f5f7f9] object-cover">
                        <div class="flex min-w-0 flex-1 flex-col gap-1 leading-[1.45]">
                            <p class="text-[15px] font-bold">{{ $isCombo ? ($line['producto'] ?? 'Combo') : $title($line['producto'] ?? 'Producto') }}</p>
                            @if ($isCombo)
                                @foreach ($line['selecciones'] ?? [] as $selection)
                                    <p class="text-[12px] text-[#65717c]">{{ $title($selection['producto'] ?? '') }} · {{ $title($selection['color'] ?? '') }} · Talla {{ $selection['talla'] ?? '-' }}</p>
                                @endforeach
                            @else
                                <p class="text-[13px] text-[#65717c]">{{ $title($line['color'] ?? '') }} · Talla {{ $line['talla'] ?? '-' }}</p>
                            @endif
                            <p class="text-[13px] text-[#65717c]">{{ (int) ($line['cantidad'] ?? 1) }} × S/ {{ number_format((float) ($line['precio'] ?? 0), 2) }}</p>
                            <p class="mt-auto text-[16px] font-extrabold">S/ {{ number_format((float) ($line['precio'] ?? 0) * (int) ($line['cantidad'] ?? 1), 2) }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="flex flex-col gap-6">
            <section class="{{ $card }}" aria-labelledby="po-summary">
                <h2 id="po-summary" class="text-[18px] font-extrabold">Resumen</h2>
                <dl class="mt-4 flex flex-col gap-2 text-[15px]">
                    <div class="flex justify-between"><dt class="text-[#65717c]">Subtotal</dt><dd class="font-bold">S/ {{ number_format((float) $order->subtotal, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-[#65717c]">Envío</dt><dd class="font-bold">{{ (float) $order->envio > 0 ? 'S/ '.number_format((float) $order->envio, 2) : 'Gratis' }}</dd></div>
                    <div class="mt-2 flex items-baseline justify-between border-t border-[#dae0e5] pt-3"><dt class="text-[16px] font-extrabold">Total</dt><dd class="text-[22px] font-extrabold">S/ {{ number_format((float) $order->precio_total, 2) }}</dd></div>
                    @if ((float) $order->pago_monto > 0)
                        <div class="flex justify-between text-[#147d5b]"><dt>{{ $cod ? 'Separo pagado en línea' : 'Pagado en línea' }}</dt><dd class="font-bold">S/ {{ number_format((float) $order->pago_monto, 2) }}</dd></div>
                    @endif
                    @if ($cod && (float) $order->separo > 0)
                        <div class="flex justify-between"><dt class="text-[#65717c]">Saldo al recibir</dt><dd class="font-bold">S/ {{ number_format((float) $order->precio_total - (float) $order->separo, 2) }}</dd></div>
                    @endif
                </dl>
                @if ($order->metodo_pago_nombre)
                    <p class="mt-4 border-t border-[#eef1f4] pt-3 text-[13px] text-[#65717c]">Método de pago: <span class="font-bold text-[#0b1015]">{{ $order->metodo_pago_nombre }}</span></p>
                @endif
                @if ($order->tipo_comprobante)
                    <p class="mt-1 text-[13px] text-[#65717c]">Comprobante: <span class="font-bold text-[#0b1015]">{{ ucfirst($order->tipo_comprobante) }}</span>@if ($order->ruc) · RUC {{ $order->ruc }}@endif</p>
                @endif
            </section>

            <section class="{{ $card }}" aria-labelledby="po-delivery">
                <h2 id="po-delivery" class="text-[18px] font-extrabold">Entrega</h2>
                <p class="mt-3 text-[15px] leading-[1.5]">{{ \App\Support\Shipping::deliveryLabel($order->entrega, $order->agencia, $order->direccion) }}</p>
                @if ($place)<p class="mt-1 text-[14px] text-[#65717c]">{{ $place }}</p>@endif
                @if ($order->referencia)<p class="mt-1 text-[14px] text-[#65717c]">Referencia: {{ $order->referencia }}</p>@endif
                @if ($order->fecha_envio)
                    <p class="mt-2 text-[14px] text-[#65717c]">Fecha de envío: {{ \App\Support\Checkout::shippingDateLabel($order->fecha_envio->toDateString()) }}</p>
                @endif
                <p class="mt-3 border-t border-[#eef1f4] pt-3 text-[13px] text-[#65717c]">Recibe: <span class="font-bold text-[#0b1015]">{{ $order->cliente_name }}</span>@if ($order->cliente_cel) · {{ $order->cliente_cel }}@endif</p>
            </section>
        </div>
    </div>
@endsection
