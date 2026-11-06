@extends('layouts.web')

{{--
    Detalle de combo: cada prenda del combo es una fila con su foto, color y talla (los colores salen
    del stock real y las tallas se habilitan según el color). A la derecha, "Tu combo" resume lo elegido
    y lleva el botón de compra (en celular, barra fija abajo). Lógica: resources/js/combo-builder.js.
    Los textos y la paleta siguen la línea de la marca del combo (Overshark Girls usa .os-hh--girls).
--}}

@section('title', $combo->name.' · Overshark')

@push('head')
    @vite('resources/js/combo-builder.js')
@endpush

@php
    $title = fn ($text) => \App\Support\ProductName::title((string) $text);
    $girls = str_contains(mb_strtolower((string) $combo->brand), 'girl');
    $hasUnavailableSlot = $comboSlots->isEmpty() || $comboSlots->contains(fn (array $slot): bool => $slot['variants']->isEmpty());
    $units = max(1, (int) $comboSlots->sum(fn (array $slot) => max(1, (int) ($slot['quantity'] ?? 1))));
    $fallback = asset('images/default-hero-banner.png');

    // Foto de cada variante: la galería de su color o la imagen del producto.
    $variantsAll = $comboSlots->flatMap(fn (array $slot) => $slot['variants']);
    $galleries = \App\Models\Admin\ProductoColorImage::query()
        ->whereIn('product_name', $variantsAll->pluck('name')->unique())
        ->get()
        ->keyBy(fn ($gallery) => $gallery->product_name.'|'.$gallery->color);
    $photo = fn ($variant) => \App\Support\Thumb::url($galleries->get($variant->name.'|'.$variant->color)?->imageUrls()[0] ?? $variant->imageUrl() ?? $fallback, 320);

    // Sin imagen propia del combo, se arma un collage con una foto de cada prenda incluida.
    $ownImage = $combo->imagen && $combo->imagen !== 'images/default-hero-banner.png' ? $combo->imageUrl() : null;
    $collage = $comboSlots->map(fn (array $slot) => $slot['variants']->first())->filter()->take(3)->map($photo)->values();
    $selectionOffset = 0;
@endphp

@section('content')
    <div @class(['os-hh os-combo', 'os-hh--girls' => $girls])>
        <div class="os-hh-wrap pb-28 pt-8 lg:pb-[88px] lg:pt-12">
            <nav aria-label="Ruta" class="text-[13px] font-semibold text-[#65717c]">
                <a href="{{ route('web.home') }}" class="hover:text-[#0b0f14]">Inicio</a>
                <span class="mx-1.5" aria-hidden="true">/</span>
                <a href="{{ route('web.combos.index', $girls ? ['marca' => 'Girls'] : []) }}" class="hover:text-[#0b0f14]">Combos</a>
                <span class="mx-1.5" aria-hidden="true">/</span>
                <span class="text-[#0b0f14]">{{ $combo->name }}</span>
            </nav>

            <div class="mt-6 grid gap-10 lg:grid-cols-[minmax(0,1fr)_400px] lg:gap-12 xl:grid-cols-[minmax(0,1fr)_440px]">
                {{-- Arma tu combo --}}
                <div class="flex min-w-0 flex-col gap-8">
                    <header class="flex flex-col gap-3">
                        <p class="text-[12px] font-extrabold uppercase tracking-[2px] text-[var(--hh-accent)]">Combo · {{ $combo->brand ?: 'Overshark' }}</p>
                        <h1 @class(['leading-[0.95]', 'os-hh-display text-[48px] sm:text-[72px]' => ! $girls, 'os-josefin text-[40px] uppercase sm:text-[56px]' => $girls])>{{ $combo->name }}</h1>
                        @if ($combo->price !== null)
                            <p class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <span class="text-[32px] font-extrabold">S/ {{ number_format((float) $combo->price, 2) }}</span>
                                <span class="text-[14px] font-bold text-[#65717c]">{{ $units }} prendas · S/ {{ number_format((float) $combo->price / $units, 2) }} c/u</span>
                            </p>
                        @endif
                        @if ($combo->notes)
                            <p class="bg-[var(--hh-soft)] px-4 py-3 text-[14px] font-medium leading-6 text-[#0b0f14]">{{ $combo->notes }}</p>
                        @endif
                    </header>

                    @if ($errors->any())
                        <p class="bg-[#fdecec] px-4 py-3 text-[14px] font-bold text-[#a3261c]" role="alert">{{ $errors->first('selections') }}</p>
                    @endif

                    @if ($hasUnavailableSlot)
                        <div class="flex flex-col items-start gap-4 bg-[var(--hh-soft)] p-6">
                            <p class="text-[18px] font-extrabold">Este combo está agotado por ahora</p>
                            <p class="text-[14px] font-medium text-[#545966]">Alguna de sus prendas no tiene stock. Mira otros combos o arma tu pedido en la tienda.</p>
                            <a href="{{ route('web.combos.index') }}" class="os-hh-btn os-focus bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)]">Ver otros combos</a>
                        </div>
                    @else
                        <form id="combo-form" action="{{ route('web.combos.cart.store', $combo) }}" method="POST" data-combo-builder novalidate class="flex flex-col gap-6">
                            @csrf
                            <div class="flex flex-col gap-1 border-b-2 border-[#0b0f14] pb-3">
                                <h2 class="text-[20px] font-extrabold">Elige talla y color</h2>
                                <p class="text-[14px] font-medium text-[#545966]">Elige el color y la talla de cada prenda. Las prendas repetidas pueden ir en colores distintos.</p>
                            </div>

                            @foreach ($comboSlots as $index => $slot)
                                @php
                                    $slotQuantity = max(1, (int) ($slot['quantity'] ?? 1));
                                    $variantData = $slot['variants']->map(fn ($variant): array => [
                                        'id' => $variant->id,
                                        'producto' => $variant->name,
                                        'talla' => $variant->talla,
                                        'color' => $variant->color,
                                        'hex' => \App\Support\GarmentSwatch::hex($variant->color),
                                        'image' => $photo($variant),
                                    ])->values();
                                @endphp
                                <section class="flex flex-col border border-[var(--shop-line)]" data-combo-slot aria-label="{{ $title($slot['label']) }}">
                                    <header class="flex flex-wrap items-center justify-between gap-3 bg-[var(--hh-soft)] px-4 py-3 sm:px-5">
                                        <div>
                                            <p class="text-[11px] font-extrabold uppercase tracking-[1.5px] text-[var(--hh-accent)]">{{ $slot['allow_product_choice'] ? 'Elige un producto' : 'Prenda '.($index + 1) }}</p>
                                            <h3 class="text-[16px] font-extrabold">{{ $slot['allow_product_choice'] ? $title($slot['label']) : $title($slot['product']) }}</h3>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            @if ($slotQuantity > 1)
                                                <button type="button" class="os-focus text-[12px] font-bold text-[var(--hh-accent)] hover:underline" data-combo-copy>Usar la misma elección en todas</button>
                                            @endif
                                            <span class="bg-white px-2.5 py-1 text-[12px] font-extrabold">×{{ $slotQuantity }} {{ $slotQuantity === 1 ? 'unidad' : 'unidades' }}</span>
                                        </div>
                                    </header>

                                    <ul class="divide-y divide-[var(--shop-line)]">
                                        @for ($unit = 0; $unit < $slotQuantity; $unit++)
                                            @php
                                                $selectionIndex = $selectionOffset + $unit;
                                                // Sin elección previa, arranca en talla M (la más pedida) si existe.
                                                $initial = $slot['variants']->firstWhere('id', (int) old("selections.{$selectionIndex}")) ?? $slot['variants']->firstWhere('talla', 'M') ?? $slot['variants']->first();
                                            @endphp
                                            <li class="grid grid-cols-[72px_minmax(0,1fr)] gap-4 p-4 sm:grid-cols-[96px_minmax(0,1fr)] sm:p-5" data-combo-unit data-variants='@json($variantData)'>
                                                <img src="{{ $photo($initial) }}" alt="" class="aspect-[4/5] w-full bg-[#f5f7f9] object-cover" data-combo-photo>
                                                <div class="flex min-w-0 flex-col gap-3">
                                                    <p class="flex flex-wrap items-baseline justify-between gap-x-3 text-[14px]">
                                                        <span class="font-extrabold">Unidad {{ $unit + 1 }}</span>
                                                        <span class="font-semibold text-[#545966]" data-combo-choice>{{ $title($initial->color) }} · Talla {{ $initial->talla }}</span>
                                                    </p>
                                                    @if ($slot['allow_product_choice'])
                                                        <label class="flex flex-col gap-1.5 text-[12px] font-extrabold uppercase tracking-[1px] text-[#545966]">Producto
                                                            <select class="os-focus h-10 border border-[var(--shop-check-line)] bg-white px-3 text-[14px] font-semibold normal-case tracking-normal text-[#0b0f14]" data-combo-product>
                                                                @foreach ($slot['variants']->pluck('name')->unique()->values() as $productName)
                                                                    <option value="{{ $productName }}" @selected($initial->name === $productName)>{{ $title($productName) }}</option>
                                                                @endforeach
                                                            </select>
                                                        </label>
                                                    @endif
                                                    <div class="flex flex-col gap-2">
                                                        <p class="text-[11px] font-extrabold uppercase tracking-[1px] text-[#545966]">Color</p>
                                                        <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Color de la unidad {{ $unit + 1 }}" data-combo-colors></div>
                                                    </div>
                                                    <div class="flex flex-col gap-2">
                                                        <p class="text-[11px] font-extrabold uppercase tracking-[1px] text-[#545966]">Talla</p>
                                                        <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Talla de la unidad {{ $unit + 1 }}" data-combo-sizes></div>
                                                    </div>
                                                    <input type="hidden" name="selections[]" value="{{ $initial->id }}" data-combo-variant>
                                                </div>
                                            </li>
                                        @endfor
                                    </ul>
                                </section>
                                @php($selectionOffset += $slotQuantity)
                            @endforeach
                            <p class="hidden bg-[#fdecec] px-4 py-3 text-[14px] font-bold text-[#a3261c]" role="alert" data-combo-error></p>
                        </form>
                    @endif
                </div>

                {{-- Tu combo --}}
                <aside class="flex flex-col gap-5 self-start lg:sticky lg:top-[calc(var(--header-h)+24px)]" aria-label="Tu combo">
                    <div class="relative aspect-[4/3] overflow-hidden bg-[var(--hh-soft)]">
                        @if ($ownImage)
                            <img src="{{ $ownImage }}" alt="{{ $combo->name }}" class="h-full w-full object-cover">
                        @elseif ($collage->isNotEmpty())
                            <div class="grid h-full gap-0.5" style="grid-template-columns: repeat({{ $collage->count() }}, minmax(0, 1fr))">
                                @foreach ($collage as $image)
                                    <img src="{{ $image }}" alt="" class="h-full w-full bg-white object-cover">
                                @endforeach
                            </div>
                        @endif
                        <span class="absolute left-0 top-0 bg-[var(--hh-accent)] px-3 py-2 text-[11px] font-extrabold uppercase tracking-[1px] text-white">{{ $units }} prendas</span>
                    </div>

                    <div class="flex flex-col gap-4 bg-[#f5f7f9] p-5 sm:p-6">
                        <h2 class="text-[18px] font-extrabold">TU COMBO</h2>
                        @unless ($hasUnavailableSlot)
                            <ol class="flex flex-col gap-2 text-[14px]" data-combo-summary></ol>
                        @else
                            <ul class="flex flex-col gap-1 text-[14px] font-semibold">
                                @foreach ($combo->displayItems() as $item)
                                    <li>{{ $title($item['zazu']) }} × {{ $item['cantidad'] }}</li>
                                @endforeach
                            </ul>
                        @endunless
                        <hr class="border-[var(--shop-line)]">
                        <p class="flex items-baseline justify-between font-extrabold">
                            <span class="text-[16px]">Total del combo</span>
                            <span class="text-[24px]">S/ {{ number_format((float) $combo->price, 2) }}</span>
                        </p>
                        <p class="-mt-2 text-[12px] font-medium text-[#65717c]">Envío desde S/ 15 a todo el Perú. Pago contraentrega o Yape / Plin.</p>
                        @unless ($hasUnavailableSlot)
                            <button type="submit" form="combo-form" class="os-hh-btn os-focus hidden w-full bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)] lg:inline-flex" data-combo-submit>Agregar combo al carrito</button>
                        @endunless
                        <a href="{{ route('web.combos.index') }}" class="os-focus text-center text-[13px] font-bold text-[#545966] hover:text-[#0b0f14] hover:underline">Ver otros combos</a>
                    </div>
                </aside>
            </div>
        </div>

        @unless ($hasUnavailableSlot)
            {{-- Celular: precio y compra siempre a mano --}}
            <div class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-between gap-4 border-t border-[var(--shop-line)] bg-white px-4 py-3 shadow-[0_-8px_24px_rgb(11_15_20/8%)] lg:hidden">
                <p class="flex flex-col leading-tight">
                    <span class="text-[12px] font-bold text-[#65717c]">{{ $units }} prendas</span>
                    <span class="text-[20px] font-extrabold">S/ {{ number_format((float) $combo->price, 2) }}</span>
                </p>
                <button type="submit" form="combo-form" class="os-hh-btn os-focus bg-[#0b0f14] px-5 text-white hover:bg-[var(--hh-accent)]" data-combo-submit>Agregar al carrito</button>
            </div>
        @endunless
    </div>
@endsection
