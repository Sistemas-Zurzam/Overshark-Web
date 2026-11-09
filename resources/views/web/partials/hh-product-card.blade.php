{{--
    Tarjeta de producto del diseño Figma ("Product Card / Ezzeta"), usada en el inicio, la tienda y Bravos.
    $rank (opcional): número de los más vendidos. $photoClass: alto o proporción de la foto.
    Debajo van los colores disponibles: al pasar el mouse o tocar uno, la foto y el nombre cambian a ese
    color (resources/js/app.js, data-card-swatch). Se ven hasta 8; el resto se indica con "+N".
--}}
@php
    $title = fn ($text) => \App\Support\ProductName::title((string) $text);
    $category = \App\Support\StorefrontCategories::slugFor($product->name);
    $colors = collect($product->display_colors ?? []);
    $color = $product->card_color ?? ($colors->first()['name'] ?? null);
    $rank ??= null;
@endphp
<div class="group" data-card>
    <a href="{{ route('web.products.show', $product->id) }}" class="os-focus block" tabindex="-1" aria-hidden="true">
        <div class="relative {{ $photoClass ?? 'aspect-[3/4]' }} overflow-hidden bg-white">
            <img src="{{ $product->display_image }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async" data-card-image>
            @if ($rank)
                <span @class(['os-hh-rank', 'bg-[var(--hh-accent)]' => $rank <= 3, 'bg-[#0b0f14]' => $rank > 3])>{{ $rank }}</span>
            @endif
            @if ($product->promo)
                <span class="absolute right-2 top-2 bg-[#b42318] px-2 py-1 text-[12px] font-extrabold leading-none text-white">{{ $product->promo['label'] ?: '-'.$product->promo['percent'].'%' }}</span>
            @endif
        </div>
    </a>
    <div class="mt-2.5 flex flex-col gap-1">
        @if ($category)
            <p class="text-[11px] font-bold uppercase tracking-[1px] text-[#666]">{{ \App\Support\StorefrontCategories::label($category) }}</p>
        @endif
        <a href="{{ route('web.products.show', $product->id) }}" class="os-focus flex items-center justify-between gap-2">
            <h3 class="min-w-0 truncate text-[15px] font-bold" data-card-title="{{ $title($product->name) }}">{{ $title($product->name) }}@if ($color) · {{ $title($color) }}@endif</h3>
            <img src="{{ asset('images/home-hombre/icon-carrito.svg') }}" alt="" width="18" height="18" class="shrink-0">
        </a>
        @if ($product->promo)
            <p class="flex items-baseline gap-2">
                <span class="text-[16px] font-extrabold text-[#b42318]">S/ {{ number_format($product->promo['price'], 2) }}</span>
                <span class="text-[13px] text-[#666] line-through">S/ {{ number_format($product->promo['regular'], 2) }}</span>
            </p>
            @include('web.partials.promo-countdown', ['promo' => $product->promo, 'class' => 'text-[11px] font-bold uppercase tracking-[0.5px] text-[#b42318]'])
        @else
            <p class="text-[16px] font-extrabold">S/ {{ number_format((float) $product->min_price, 2) }}</p>
        @endif
        @if ($colors->count() > 1)
            <div class="mt-1 flex flex-wrap items-center gap-1.5" role="group" aria-label="Colores de {{ $title($product->name) }}">
                @foreach ($colors->take(8) as $option)
                    <button type="button" class="os-card-swatch os-focus" style="--sw: {{ \App\Support\GarmentSwatch::hex($option['name']) }}"
                        data-card-swatch data-image="{{ $option['image'] }}" data-name="{{ $title($option['name']) }}"
                        aria-pressed="{{ $option['name'] === $color ? 'true' : 'false' }}" aria-label="Ver color {{ $title($option['name']) }}" title="{{ $title($option['name']) }}"></button>
                @endforeach
                @if ($colors->count() > 8)
                    <span class="text-[11px] font-bold text-[#666]">+{{ $colors->count() - 8 }}</span>
                @endif
            </div>
        @endif
    </div>
</div>
