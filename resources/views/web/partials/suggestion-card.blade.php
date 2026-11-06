{{-- Tarjeta de "Te puede interesar": se suma con un clic eligiendo color y talla (resources/js/cart-suggestions.js
     deja en la talla solo las del color elegido). $card viene de App\Support\CartSuggestions; $quiet = no reabrir el modal. --}}
@php
    $nice = fn ($text) => \App\Support\ProductName::title((string) $text);
    $colors = array_keys($card['colors']);
    $firstColor = $colors[0] ?? '';
    $field = 'os-focus h-10 w-full border border-[var(--shop-check-line)] bg-white px-2 text-[13px] font-semibold text-[#0b0f14]';
@endphp
<form method="POST" action="{{ route('web.cart.store') }}" data-suggest-card data-variants='@json($card['colors'])' class="flex flex-col gap-2.5">
    @csrf
    <input type="hidden" name="producto_id" value="{{ $card['id'] }}">
    <input type="hidden" name="qty" value="1">
    @if (! empty($quiet))
        <input type="hidden" name="quiet" value="1">
    @endif
    <a href="{{ route('web.products.show', $card['id']) }}" class="block aspect-[4/5] overflow-hidden bg-[var(--hh-soft)]">
        <img src="{{ $card['image'] }}" alt="{{ $nice($card['name']) }}" loading="lazy" class="h-full w-full object-cover">
    </a>
    <div class="min-w-0">
        <p class="line-clamp-2 min-h-[2.5rem] text-[14px] font-extrabold leading-snug">{{ $nice($card['name']) }}</p>
        <p class="text-[14px] font-extrabold text-[var(--hh-accent)]">S/ {{ number_format($card['price'], 2) }}</p>
    </div>
    <div class="grid grid-cols-2 gap-2">
        <select name="color" data-suggest-color class="{{ $field }}" aria-label="Color de {{ $nice($card['name']) }}">
            @foreach ($colors as $color)
                <option value="{{ $color }}" @selected($color === $firstColor)>{{ $nice($color) }}</option>
            @endforeach
        </select>
        <select name="talla" data-suggest-size class="{{ $field }}" aria-label="Talla de {{ $nice($card['name']) }}">
            @foreach ($card['colors'][$firstColor] ?? [] as $size)
                <option value="{{ $size }}">{{ $size }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="os-hh-btn os-focus w-full bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)]">Agregar</button>
</form>
