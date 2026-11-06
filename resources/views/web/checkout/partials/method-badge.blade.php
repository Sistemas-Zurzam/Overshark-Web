{{-- Distintivo a la derecha de cada medio de pago: marcas de tarjeta, Yape, logo del admin o el separo. --}}
@if ($method['kind'] === 'card')
    @include('web.checkout.partials.card-brands')
@elseif ($method['kind'] === 'yape')
    <span class="shrink-0 bg-[#742384] px-2 py-1 text-[11px] font-extrabold text-white" aria-hidden="true">yape</span>
@elseif ($method['kind'] === 'cod')
    <span class="shrink-0 whitespace-nowrap text-[12px] font-bold text-[#65717c]">{{ $deposit > 0 ? 'Separo S/ '.number_format($deposit, 2) : 'Sin separo' }}</span>
@elseif ($method['logo'])
    <img src="{{ $method['logo'] }}" alt="" class="h-8 w-8 shrink-0 object-contain">
@endif
