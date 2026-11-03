{{--
    Resumen "TU PEDIDO" del checkout: prendas, subtotal, envío y total. $items/$count/$subtotal/$shipping/$total
    vienen de App\Support\Checkout::summary(). El botón de cada paso va en el slot $slot (opcional).
--}}
@php($title = fn ($text) => \Illuminate\Support\Str::title(mb_strtolower((string) $text)))
<aside class="flex flex-col gap-5 bg-[#f5f7f9] p-6 text-[#0b1015] lg:sticky lg:top-[112px] lg:self-start" aria-labelledby="checkout-summary">
    <h2 id="checkout-summary" class="text-[20px] font-extrabold leading-[1.45]">TU PEDIDO</h2>
    <ul class="flex flex-col gap-4">
        @foreach ($items as $item)
            <li class="flex gap-5">
                <img src="{{ $item['image'] ?? asset('images/default-hero-banner.png') }}" alt="" class="h-20 w-16 shrink-0 bg-white object-cover">
                <div class="flex min-w-0 flex-col gap-1 leading-[1.45]">
                    <p class="text-[14px] font-bold">{{ ($item['kind'] ?? null) === 'combo' ? ($item['producto'] ?? 'Combo') : $title($item['producto'] ?? 'Producto') }}@if (($item['qty'] ?? 1) > 1) <span class="font-medium text-[#65717c]">× {{ $item['qty'] }}</span>@endif</p>
                    @if (($item['kind'] ?? null) === 'combo')
                        <p class="text-[13px] text-[#65717c]">Combo · {{ count($item['selections'] ?? []) }} prendas</p>
                    @else
                        <p class="text-[13px] text-[#65717c]">{{ $title($item['color'] ?? '') }} · Talla {{ $item['talla'] ?? '-' }}</p>
                    @endif
                    <p class="text-[16px] font-extrabold">S/ {{ number_format((float) $item['price'] * (int) ($item['qty'] ?? 1), 2) }}</p>
                </div>
            </li>
        @endforeach
    </ul>
    <hr class="border-[#dae0e5]">
    <dl class="flex flex-col gap-3 text-[14px] leading-[1.45]">
        <div class="flex justify-between"><dt>Subtotal ({{ $count }} {{ $count === 1 ? 'producto' : 'productos' }})</dt><dd class="font-bold">S/ {{ number_format($subtotal, 2) }}</dd></div>
        <div class="flex justify-between"><dt>Envío</dt><dd class="font-bold">{{ $shipping > 0 ? 'S/ '.number_format($shipping, 2) : 'Gratis' }}</dd></div>
    </dl>
    <hr class="border-[#dae0e5]">
    <div class="flex items-baseline justify-between font-extrabold leading-[1.45]">
        <span class="text-[17px]">TOTAL</span>
        <span class="text-[24px]">S/ {{ number_format($total, 2) }}</span>
    </div>
    <p class="text-[12px] leading-[1.45] text-[#65717c]">Precios en soles. Impuestos incluidos.</p>
    {{ $slot ?? '' }}
</aside>
