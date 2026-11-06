{{--
    Instrucciones para pagar con un medio del admin (Yape, Plin…): QR, número y pasos. $method viene de
    Checkout::paymentMethods(); $amount es el importe a pagar (el total o el separo) y $concept lo nombra.
--}}
<div class="flex flex-wrap items-start gap-6">
    @if ($method['qr'])
        <img src="{{ $method['qr'] }}" alt="QR de {{ $method['name'] }}" class="h-[180px] w-[180px] border border-[#dae0e5] bg-white object-contain p-3">
    @endif
    <div class="flex min-w-0 flex-col gap-3 text-[14px] leading-[1.45]">
        @if ($method['number'])
            <div>
                <p class="text-[12px] font-bold text-[#65717c]">NÚMERO</p>
                <p class="text-[22px] font-extrabold">{{ $method['number'] }}</p>
                @if ($method['owner'])<p class="text-[13px] text-[#65717c]">A nombre de {{ $method['owner'] }}</p>@endif
                <button type="button" class="os-focus mt-1 text-[13px] font-bold underline" data-ck-copy="{{ $method['number'] }}">Copiar número</button>
            </div>
        @endif
        <ol class="list-decimal pl-5">
            <li>Abre {{ $method['name'] }}{{ $method['qr'] ? ' y escanea el QR' : '' }}{{ $method['number'] ? ($method['qr'] ? ' o paga al número' : ' y paga al número') : '' }}.</li>
            <li>Paga el importe exacto de {{ $concept }}: <strong>S/ {{ number_format($amount, 2) }}</strong>.</li>
            <li>Sube la captura abajo. Verificamos tu pago y te confirmamos el pedido.</li>
        </ol>
    </div>
</div>
