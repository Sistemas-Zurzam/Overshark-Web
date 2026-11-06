{{-- Estado real del pedido (status_orders), sin inventar estados: verde = pagado/avanzando, rojo = cancelado, azul = en espera. --}}
@php
    $name = $order->statusOrder?->name ?? 'Recibido';
    $tone = match (true) {
        in_array($name, [\App\Support\Checkout::STATUS_PAID, \App\Support\Checkout::STATUS_DEPOSIT_PAID, 'Enviado', 'Entregado'], true) => 'bg-[#e3f4ec] text-[#147d5b]',
        $name === \App\Support\Checkout::STATUS_CANCELLED => 'bg-[#fdecec] text-[#a3261c]',
        default => 'bg-[#e6eefc] text-[#2467cf]',
    };
@endphp
<span class="inline-block rounded-full px-3 py-1 text-[12px] font-extrabold {{ $tone }}">{{ $name }}</span>
