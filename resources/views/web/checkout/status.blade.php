@extends('web.checkout.layout')

{{--
    Estado del pedido: C7 · Estamos confirmando tu pago (pendiente) o C6 · ¡Tu pedido está confirmado! (cuando el
    pedido pasa a "Pago confirmado" en el admin). Pendiente no es aprobado: solo el estado del servidor lo cambia.
--}}

@section('title', ($paid ? 'Pedido confirmado' : 'Pedido recibido').' | Overshark')

@php
    $title = fn ($text) => \Illuminate\Support\Str::title(mb_strtolower((string) $text));
    $cashOnDelivery = $order->metodo_pago_nombre === 'Pago contraentrega';
@endphp

@section('checkout')
    <h1 class="text-[28px] font-extrabold leading-[1.45] text-[#0b1015] sm:text-[36px]">{{ $paid ? '¡TU PEDIDO ESTÁ CONFIRMADO!' : ($cashOnDelivery ? '¡RECIBIMOS TU PEDIDO!' : 'ESTAMOS CONFIRMANDO TU PAGO') }}</h1>

    <section class="flex flex-col gap-6 bg-[#f5f7f9] p-6 text-[#0b1015] sm:p-10" aria-label="Estado del pedido">
        <p @class(['text-[14px] font-extrabold leading-[1.45]', 'text-[#147d5b]' => $paid, 'text-[#2467cf]' => ! $paid])>
            {{ $paid ? 'PAGO APROBADO' : ($cashOnDelivery ? 'PAGO CONTRAENTREGA' : 'PAGO EN PROCESO') }}
        </p>
        @if ($paid)
            <p class="text-[22px] font-extrabold leading-[1.45] sm:text-[28px]">Gracias por comprar en Overshark.</p>
        @elseif ($cashOnDelivery)
            <p class="max-w-[1000px] text-[18px] leading-[1.45] sm:text-[20px]">Gracias por tu compra. Pagas al recibir tu pedido; te escribiremos por WhatsApp para coordinar la entrega.</p>
        @else
            <p class="max-w-[1000px] text-[18px] leading-[1.45] sm:text-[20px]">Tu pedido está pendiente. No vuelvas a pagar mientras verificamos tu captura. Te avisaremos al confirmar la operación.</p>
        @endif
        <p class="text-[16px] font-bold leading-[1.45] sm:text-[18px]">Pedido #{{ $order->number_zazu }} · {{ $paid ? 'Total pagado' : 'Total' }} S/ {{ number_format((float) $order->precio_total, 2) }}</p>
        <p class="max-w-[1100px] text-[15px] leading-[1.45] text-[#65717c] sm:text-[16px]">Te enviaremos los detalles del pedido y las novedades del envío a {{ $order->email }}.</p>

        <hr class="border-[#dae0e5]">

        <p class="text-[16px] leading-[1.45]">
            {{ $order->entrega === 'agencia' ? 'Recojo en agencia · '.$order->agencia : 'Entrega a domicilio · '.$order->direccion }}<br>
            Entrega estimada: 2 a 4 días hábiles
        </p>

        <ul class="flex flex-col gap-4">
            @foreach ($order->productos_json ?? [] as $line)
                <li class="flex gap-5">
                    <img src="{{ $line['imagen'] ?? asset('images/default-hero-banner.png') }}" alt="" class="h-20 w-16 shrink-0 bg-white object-cover">
                    <div class="flex flex-col gap-1 leading-[1.45]">
                        <p class="text-[14px] font-bold">{{ ($line['tipo'] ?? '') === 'combo' ? ($line['producto'] ?? 'Combo') : $title($line['producto'] ?? 'Producto') }}@if (($line['cantidad'] ?? 1) > 1) × {{ $line['cantidad'] }}@endif</p>
                        <p class="text-[13px] text-[#65717c]">{{ ($line['tipo'] ?? '') === 'combo' ? 'Combo · '.count($line['selecciones'] ?? []).' prendas' : $title($line['color'] ?? '').' · Talla '.($line['talla'] ?? '-') }}</p>
                        <p class="text-[16px] font-extrabold">S/ {{ number_format((float) ($line['precio'] ?? 0) * (int) ($line['cantidad'] ?? 1), 2) }}</p>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('web.home') }}" class="os-checkout-btn os-focus w-full sm:w-[320px]">SEGUIR COMPRANDO</a>
            @unless ($paid)
                <a href="{{ route('web.checkout.done', $order) }}" class="os-checkout-btn os-checkout-btn--ghost os-focus w-full sm:w-[320px]">ACTUALIZAR ESTADO</a>
            @endunless
        </div>
    </section>
@endsection
