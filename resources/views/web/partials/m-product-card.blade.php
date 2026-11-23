{{--
    Tarjeta de producto en celular (Figma "02 · MOBILE" → "Product Card / Mobile / Overshark"): foto 165 × 214,
    categoría, nombre · color, precio y «VER PRENDA →». Toda la tarjeta lleva a la ficha. Si la prenda está en
    promoción por horas, el precio va en rojo con el regular tachado.
--}}
@php
    $mTitle = fn ($text) => \App\Support\ProductName::title((string) $text);
    $mCategory = \App\Support\StorefrontCategories::slugFor($product->name);
    $mColor = $product->card_color ?? (collect($product->display_colors ?? [])->first()['name'] ?? null);
@endphp
<a href="{{ route('web.products.show', $product->id) }}" class="os-mcard os-focus">
    <span class="os-mcard__photo">
        <img src="{{ $product->display_image }}" alt="" loading="lazy" decoding="async">
        @if ($product->promo)
            <span class="os-mcard__badge">{{ $product->promo['label'] ?: '-'.$product->promo['percent'].'%' }}</span>
        @endif
    </span>
    @if ($mCategory)
        <span class="os-mcard__category">{{ \App\Support\StorefrontCategories::label($mCategory) }}</span>
    @endif
    <span class="os-mcard__name">{{ $mTitle($product->name) }}@if ($mColor) · {{ $mTitle($mColor) }}@endif</span>
    @if ($product->promo)
        <span class="os-mcard__price">
            <span class="text-[#b42318]">S/ {{ number_format($product->promo['price'], 2) }}</span>
            <s class="ml-1.5 text-[12px] font-medium text-[#666]">S/ {{ number_format($product->promo['regular'], 2) }}</s>
        </span>
    @else
        <span class="os-mcard__price">S/ {{ number_format((float) $product->min_price, 2) }}</span>
    @endif
    <span class="os-mcard__cta">Ver prenda →</span>
</a>
