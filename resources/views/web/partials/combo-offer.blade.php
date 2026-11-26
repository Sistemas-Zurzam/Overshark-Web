@php($combo = $offer['combo'])
<article class="os-hh grid gap-2 border border-[#dae0e5] bg-white p-4">
    <p class="text-sm font-bold text-[#545966]">{{ $combo->brand }}</p>
    <h3 class="text-lg font-extrabold text-[#0b1015]">{{ $combo->name }}</h3>
    <p class="text-xl font-extrabold text-[#0b1015]">S/ {{ number_format((float) $combo->price, 2) }}</p>
    <p class="text-sm text-[#545966]">
        <strong>Llevas {{ $offer['have'] }} de {{ $offer['have'] + $offer['missing'] }} prendas.</strong>
        @if ($offer['delta'] > 0)
            Completa el combo por <strong>S/ {{ number_format($offer['delta'], 2) }} adicionales</strong>.
        @elseif ($offer['delta'] < 0)
            <strong>Ahorra S/ {{ number_format(-$offer['delta'], 2) }}</strong> al aplicar el combo.
        @else
            El subtotal se mantiene al aplicar el combo.
        @endif
    </p>
    <a href="{{ route('web.combos.show', ['combo' => $combo, 'convert' => 1]) }}" class="os-hh-btn os-focus justify-self-start bg-[#0b1015] text-white">
        {{ $offer['missing'] ? 'Completar combo' : 'Aplicar combo' }}
    </a>
</article>
