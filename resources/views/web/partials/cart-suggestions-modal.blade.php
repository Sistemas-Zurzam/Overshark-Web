{{-- Modal que se abre al agregar una prenda: confirma y sugiere más (reglas del panel, App\Support\CartSuggestions).
     $added = prenda agregada, $suggestions = tarjetas, $storeLine = línea de la tienda. --}}
<div data-suggest-modal class="fixed inset-0 z-[80] grid place-items-end sm:place-items-center" role="dialog" aria-modal="true" aria-labelledby="suggest-title">
    <div data-suggest-close class="absolute inset-0 bg-[#0b0f14]/60" aria-hidden="true"></div>
    <div @class(['os-hh relative max-h-[92vh] w-full overflow-y-auto bg-white shadow-2xl sm:max-w-[760px]', 'os-hh--girls' => $storeLine === 'mujer'])>
        <div class="flex items-start justify-between gap-4 border-b border-[var(--shop-line)] px-5 py-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[var(--hh-soft)] text-[var(--hh-accent)]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[15px] font-extrabold">Agregado a tu carrito</p>
                    <p class="truncate text-[13px] font-medium text-[#545966]">{{ \App\Support\ProductName::title($added->name) }}</p>
                </div>
            </div>
            <button type="button" data-suggest-close class="os-focus grid h-10 w-10 shrink-0 place-items-center hover:bg-[var(--hh-soft)]" aria-label="Cerrar">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        @if (isset($errors) && $errors->has('cart'))
            <p class="bg-[#fdecec] px-5 py-3 text-[14px] font-bold text-[#a3261c] sm:px-6" role="alert">{{ $errors->first('cart') }}</p>
        @elseif (session('cart_notice'))
            <p class="bg-[#fff6e0] px-5 py-3 text-[14px] font-bold text-[#8a5a00] sm:px-6" role="status">{{ session('cart_notice') }}</p>
        @endif

        <div class="px-5 py-5 sm:px-6">
            <h2 id="suggest-title" class="text-[18px] font-extrabold">{{ $suggestionTitle ?? 'Completa tu look' }}</h2>
            <div class="mt-4 grid grid-cols-2 gap-4 {{ [2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-3'][$suggestions->count()] ?? 'sm:grid-cols-4' }}">
                @foreach ($suggestions as $card)
                    @include('web.partials.suggestion-card', ['card' => $card])
                @endforeach
            </div>
        </div>

        <div class="sticky bottom-0 flex flex-col gap-2 border-t border-[var(--shop-line)] bg-white px-5 py-4 sm:flex-row-reverse sm:px-6">
            <button type="button" data-cart-open data-suggest-close class="os-hh-btn os-focus w-full bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)] sm:w-auto sm:flex-1">Ver carrito</button>
            <button type="button" data-suggest-close class="os-focus w-full py-3 text-[13px] font-extrabold uppercase tracking-[1.2px] hover:text-[var(--hh-accent)] sm:w-auto sm:flex-1">Seguir comprando</button>
        </div>
    </div>
</div>
