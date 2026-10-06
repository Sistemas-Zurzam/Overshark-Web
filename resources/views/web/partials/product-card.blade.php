@php
    $swatches = [
        'azul' => '#1d4f91',
        'beige' => '#ddcdbd',
        'perla' => '#e8e0d6',
        'cemento' => '#9b9b95',
        'negro' => '#111111',
        'vino' => '#7b1028',
        'botella' => '#0f4f3b',
        'plomo' => '#9a9aa0',
        'pacay' => '#8c9b73',
        'denim' => '#526f91',
        'blanco' => '#f7f7f2',
        'p. rosa' => '#e8b8bd',
    ];
    $displayColors = collect($product->display_colors ?? []);
    $oldPrice = (float) $product->min_price > 0 ? ((float) $product->min_price / 0.8) : 0;
    $showBag = $showBag ?? false;
@endphp
<article class="group basis-[78vw] shrink-0 snap-start rounded-2xl border border-edge bg-panel p-3 transition hover:-translate-y-1 hover:border-neon/60 hover:shadow-[0_18px_45px_rgb(47_111_208/18%)] sm:basis-auto">
    <a href="{{ route('web.products.show', $product->id) }}" class="relative block aspect-[4/5] overflow-hidden rounded-xl bg-frame">
        <img data-product-card-image src="{{ $product->display_image }}" alt="{{ $product->name }}" class="h-full w-full object-contain object-center transition duration-500 group-hover:scale-105">
        <span class="absolute left-3 top-3 rounded-full bg-live px-2.5 py-1 text-xs font-bold text-white">-20%</span>
        @if ($showBag)
            <span class="absolute bottom-3 right-3 grid h-10 w-10 place-items-center rounded-full bg-neon text-white shadow-lg shadow-neon/40" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
            </span>
        @endif
    </a>
    <div class="px-2 pb-2 pt-4">
        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-neon-soft">{{ $product->empresa_nombre ?? 'Overshark' }}</p>
        <a href="{{ route('web.products.show', $product->id) }}" class="mt-1 line-clamp-2 min-h-10 text-base font-bold text-white transition hover:text-neon-soft">{{ $product->name }}</a>
        <div class="mt-2 flex items-baseline gap-2">
            <p class="text-xl font-black">S/ {{ number_format((float) $product->min_price, 2) }}</p>
            @if ($oldPrice > 0)
                <p class="text-sm text-haze line-through">S/ {{ number_format($oldPrice, 2) }}</p>
            @endif
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-1.5">
            @foreach ($displayColors as $color)
                @php
                    $colorName = mb_strtolower($color['name']);
                    $swatchColor = $swatches[$colorName] ?? '#b8b8bd';
                @endphp
                @if ($loop->iteration === 9 && $displayColors->count() > 8)
                    <button type="button" data-product-colors-expand data-collapsed-label="+{{ $displayColors->count() - 8 }}" data-expanded-label="-" class="grid h-5 min-w-5 place-items-center rounded-full border border-edge-strong bg-panel-2 px-1 text-[11px] font-black leading-none text-mist transition hover:border-neon-soft hover:text-white" aria-label="Mostrar colores extra" aria-expanded="false">
                        +{{ $displayColors->count() - 8 }}
                    </button>
                @endif
                <button
                    type="button"
                    data-product-color
                    data-image="{{ $color['image'] }}"
                    @class([
                        'h-5 w-5 rounded-full border border-edge-strong ring-offset-2 ring-offset-panel transition hover:ring-2 hover:ring-neon-soft',
                        'hidden' => $loop->iteration > 8,
                    ])
                    @if ($loop->iteration > 8) data-extra-product-color @endif
                    style="background-color: {{ $swatchColor }}"
                    aria-label="Ver color {{ $color['name'] }}"
                ></button>
            @endforeach
        </div>
    </div>
</article>
