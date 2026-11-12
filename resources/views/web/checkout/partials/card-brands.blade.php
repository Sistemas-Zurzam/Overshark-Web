{{-- Marcas de tarjeta aceptadas por Izipay (texto, sin logos de terceros). --}}
<span class="flex shrink-0 flex-wrap justify-end gap-1" aria-label="Visa, Mastercard, American Express y Diners Club">
    @foreach (['VISA', 'MASTERCARD', 'AMEX', 'DINERS'] as $brand)
        <span class="border border-[#dae0e5] bg-white px-1.5 py-0.5 text-[9px] font-extrabold tracking-[.5px] text-[#1a1f71]" aria-hidden="true">{{ $brand }}</span>
    @endforeach
</span>
