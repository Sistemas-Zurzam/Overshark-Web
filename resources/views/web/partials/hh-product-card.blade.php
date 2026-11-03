{{--
    Tarjeta de producto del diseño Figma ("Product Card / Ezzeta"), usada en el inicio y la tienda.
    $rank (opcional): número de los más vendidos. $photoClass: alto o proporción de la foto.
--}}
@php
    $title = fn (string $name) => \Illuminate\Support\Str::title(mb_strtolower($name));
    $category = \App\Support\StorefrontCategories::slugFor($product->name);
    $color = $product->card_color ?? ($product->display_colors->first()['name'] ?? null);
    $rank ??= null;
@endphp
<a href="{{ route('web.products.show', $product->id) }}" class="os-focus group block">
    <div class="relative {{ $photoClass ?? 'aspect-[3/4]' }} overflow-hidden bg-white">
        <img src="{{ $product->display_image }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async">
        @if ($rank)
            <span @class(['os-hh-rank', 'bg-[var(--hh-accent)]' => $rank <= 3, 'bg-[#0b0f14]' => $rank > 3])>{{ $rank }}</span>
        @endif
    </div>
    <div class="mt-2.5 flex flex-col gap-1">
        @if ($category)
            <p class="text-[11px] font-bold uppercase tracking-[1px] text-[#666]">{{ \App\Support\StorefrontCategories::label($category) }}</p>
        @endif
        <div class="flex items-center justify-between gap-2">
            <h3 class="min-w-0 truncate text-[15px] font-bold">{{ $title($product->name) }}@if ($color) · {{ $title($color) }}@endif</h3>
            <img src="{{ asset('images/home-hombre/icon-carrito.svg') }}" alt="" width="18" height="18" class="shrink-0">
        </div>
        <p class="text-[16px] font-extrabold">S/ {{ number_format((float) $product->min_price, 2) }}</p>
    </div>
</a>
