@extends('layouts.web')

{{--
    Arma tu outfit (diseño Figma "03 · Arma tu Outfit Hombre · Overshark"): hero con collage,
    constructor del look (Tu look + pestañas por prenda) y "Looks listos para ti".
    Cada look es un combo de modalidad "Outfit" (Admin → Combos): sus prendas son las pestañas,
    el total es el precio del combo y "Agregar look al carrito" lo envía a web.combos.cart.store.
    En la línea mujer (diseño "03 · Arma tu Outfit Mujer") cambian la paleta (.os-hh--girls), los títulos
    (Josefin Sans), el collage y los looks (solo combos de Overshark Girls).
    Lógica: resources/js/outfit-builder.js.
--}}

@section('title', 'Arma tu outfit | Overshark')

@push('head')
    @vite(['resources/js/outfit-builder.js', 'resources/js/outfit-free.js'])
@endpush

@php
    $whatsappUrl = \App\Models\BrandSetting::current()->whatsappUrl() ?: config('services.storefront.chat_url') ?: route('web.contact');
    $firstAvailable = $combos->firstWhere('available', true);
    $girls = session('storefront.line') === 'mujer';
    $collage = $girls ? ['collage-girls-1.webp', 'collage-girls-2.webp', 'collage-girls-3.webp'] : ['collage-1.webp', 'collage-2.webp', 'collage-3.webp'];
@endphp

@section('content')
    <div @class(['os-hh', 'os-hh--girls' => $girls]) data-outfit-builder data-combos='@json($combos)'>
        {{-- Celular (Figma "03 · Outfit / Mobile / Overshark"): cabecera blanca con el título y el constructor justo debajo. --}}
        <header class="os-m flex flex-col items-start gap-3 px-6 pt-9 md:hidden">
            <h1 class="text-[32px] font-extrabold uppercase leading-[1.35]">Arma tu outfit</h1>
            <p class="text-[14px] leading-[1.35]">Elige una prenda de arriba y una de abajo; súmale un extra si quieres.</p>
        </header>

        {{-- Hero --}}
        <section class="hidden bg-[var(--shop-deep)] text-white md:grid lg:grid-cols-[minmax(0,760fr)_minmax(0,680fr)]" aria-labelledby="ob-title">
            <div class="flex flex-col items-start justify-center gap-5 px-4 py-12 sm:px-8 lg:min-h-[520px] lg:py-10 lg:pl-[max(2rem,calc(50vw-620px))] lg:pr-14">
                <p class="text-[13px] font-extrabold uppercase tracking-[3px] text-[var(--hh-bright)]">Estilista online · Gratis</p>
                <h1 id="ob-title" @class([
                    'os-hh-display text-[64px] leading-[62px] sm:text-[104px] sm:leading-[98px] xl:text-[128px] xl:leading-[118px]' => ! $girls,
                    'os-josefin text-[56px] uppercase leading-[60px] sm:text-[90px] sm:leading-[97px]' => $girls,
                ])>¡Arma tu<br>outfit!</h1>
                <p class="max-w-[560px] text-[16px] font-medium leading-[26px] text-[var(--shop-intro)] sm:text-[18px] sm:leading-[28px]">Combina tus básicos favoritos, mira cómo queda el look completo y llévatelo con precio de combo.</p>
                <ol class="flex flex-wrap gap-x-7 gap-y-2 pt-2">
                    @foreach (['Elige tu top', 'Súmale un bottom', 'Agrégalo al carrito'] as $step)
                        <li class="flex items-center gap-2.5">
                            <span @class(['leading-none text-[var(--hh-bright)]', 'os-hh-display text-[36px]' => ! $girls, 'os-josefin text-[25px]' => $girls])>{{ sprintf('%02d', $loop->iteration) }}</span>
                            <span class="text-[14px] font-bold">{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
            <div class="grid h-[300px] grid-cols-3 gap-1 sm:h-[420px] lg:h-auto lg:min-h-[520px]" aria-hidden="true">
                @foreach ($collage as $photo)
                    <img src="{{ asset('images/outfit/'.$photo) }}" alt="" class="h-full w-full object-cover" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                @endforeach
            </div>
        </section>

        {{-- Constructor libre: la clienta elige las prendas (de Overshark o Bravos) tocando cada cuadro --}}
        <section id="constructor-libre" class="os-hh-wrap scroll-mt-[calc(var(--header-h)+16px)] pb-9 pt-6 max-md:!px-6 md:pb-16 md:pt-16 lg:pb-[88px] lg:pt-[88px]" aria-labelledby="fl-title"
            data-free-look data-catalog='@json($catalog)' data-categories='@json($categories)' data-slots='@json(['Top', 'Bottom', 'Extra'])' data-min="2">
            <header class="os-hh-head">
                <div>
                    <p class="os-hh-eyebrow">Tú eliges</p>
                    <h2 id="fl-title" @class(['os-hh-title', 'os-josefin' => $girls])>Construye tu look</h2>
                    <p class="os-hh-sub">Toca cada cuadro y elige la prenda, el color y la talla. Puedes cambiarla cuando quieras.</p>
                </div>
            </header>

            @if ($catalog === [])
                <div class="mt-8 grid min-h-[240px] place-items-center border border-dashed border-[var(--shop-check-line)] bg-white px-6 py-12 text-center">
                    <div class="flex flex-col items-center gap-3">
                        <p class="max-w-[420px] text-[18px] font-extrabold">Estamos reponiendo las prendas.</p>
                        <a href="{{ route('web.products.search') }}" class="os-hh-btn os-focus mt-2 bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)]">Ver la tienda →</a>
                    </div>
                </div>
            @else
                <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_380px]">
                    <ul class="grid grid-cols-3 gap-2 sm:gap-4" data-fl-slots></ul>

                    <form method="POST" action="{{ route('web.cart.look') }}" class="flex flex-col gap-3 self-start bg-[var(--hh-soft)] p-5 sm:p-6" data-fl-form>
                        @csrf
                        <div data-fl-inputs></div>
                        <h3 @class(['leading-none', 'os-hh-display text-[32px]' => ! $girls, 'os-josefin text-[23px] uppercase' => $girls])>Tu look</h3>
                        @if ($errors->has('items'))
                            <p class="bg-white px-3 py-2 text-[13px] font-bold text-[#c0392b]" role="alert">{{ $errors->first('items') }}</p>
                        @endif
                        <dl class="flex flex-col gap-2 pt-2">
                            <div class="flex justify-between text-[14px]">
                                <dt class="font-medium text-[#545966]" data-fl-count>Subtotal (0 prendas)</dt>
                            </div>
                            <div class="flex items-baseline justify-between font-extrabold">
                                <dt class="text-[18px]">Total del look</dt>
                                <dd class="text-[24px]" data-fl-total>S/ 0.00</dd>
                            </div>
                        </dl>
                        <button type="submit" class="os-hh-btn os-focus w-full bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)] disabled:cursor-not-allowed disabled:opacity-40" data-fl-submit disabled>Agregar look al carrito</button>
                        <p class="text-center text-[13px] font-bold text-[var(--hh-accent)]" data-fl-hint aria-live="polite"></p>
                        <p class="text-center text-[12px] text-[#545966]">Precio de cada prenda. Los combos y promociones se aplican en sus propias secciones.</p>
                    </form>
                </div>

                {{-- Modal de prendas --}}
                <div class="fixed inset-0 z-[90] hidden" data-fl-modal role="dialog" aria-modal="true" aria-labelledby="fl-modal-title">
                    <div class="absolute inset-0 bg-black/60"></div>
                    <div class="absolute inset-x-0 bottom-0 flex max-h-[92vh] flex-col bg-white sm:inset-y-8 sm:mx-auto sm:max-h-none sm:w-[min(960px,calc(100%-32px))]">
                        <div class="flex items-center justify-between gap-4 border-b border-[var(--shop-line)] px-5 py-4 sm:px-6">
                            <h3 id="fl-modal-title" class="text-[20px] font-extrabold" data-fl-modal-title>Elige tu prenda</h3>
                            <button type="button" class="os-focus grid h-10 w-10 place-items-center text-[26px] leading-none" data-fl-close aria-label="Cerrar">×</button>
                        </div>
                        <div class="flex flex-col gap-2 border-b border-[var(--shop-line)] px-5 py-3 sm:px-6">
                            <div class="flex flex-wrap gap-2" data-fl-brands></div>
                            <div class="flex flex-wrap gap-2" data-fl-categories></div>
                        </div>
                        <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                            <p class="hidden py-10 text-center text-[15px] font-bold text-[#545966]" data-fl-empty>No hay prendas con ese filtro.</p>
                            <ul class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3" data-fl-models></ul>
                        </div>
                        <div class="border-t border-[var(--shop-line)] px-5 py-4 sm:px-6">
                            <button type="button" class="os-hh-btn os-focus w-full bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)] disabled:cursor-not-allowed disabled:opacity-40" data-fl-confirm disabled>Añadir al look</button>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        @if ($combos->isEmpty())
            {{-- Sin looks armados por el equipo: el constructor libre de arriba ya permite armar uno. --}}

            @if ($girls)
                <section class="os-hh-wrap pb-16 lg:pb-[88px]" aria-labelledby="ob-looks">
                    <header class="os-hh-head">
                        <div>
                            <p class="os-hh-eyebrow">Inspírate</p>
                            <h2 id="ob-looks" class="os-hh-title os-josefin">Looks listos para ti</h2>
                            <p class="os-hh-sub">Combinaciones armadas por nuestro equipo · muy pronto podrás agregarlas en un clic</p>
                        </div>
                    </header>
                    <ul class="mt-8 grid grid-cols-2 gap-x-4 gap-y-8 lg:grid-cols-4">
                        @foreach ([
                            ['Look rosa total', 'Polera + comfort pants'],
                            ['City girl', 'Baby tee + jean ancho'],
                            ['Soft pink', 'Baby tee + jogger rosa'],
                            ['Clean girl', 'Top cropped + jogger gris'],
                        ] as [$lookName, $lookText])
                            <li class="flex flex-col gap-2.5">
                                <span class="relative block aspect-[298/400] overflow-hidden bg-[var(--hh-soft)]">
                                    <img src="{{ asset('images/outfit/look-girls-'.$loop->iteration.'.webp') }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                    <span class="absolute left-0 top-0 bg-[var(--hh-accent)] px-3 py-2 text-[11px] font-extrabold uppercase tracking-[1px] text-white">Próximamente</span>
                                </span>
                                <span class="os-josefin text-[19px] !font-bold uppercase leading-none">{{ $lookName }}</span>
                                <span class="text-[14px] font-medium text-[#545966]">{{ $lookText }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @else
            {{-- Constructor de look --}}
            <section id="constructor" class="os-hh-wrap scroll-mt-[calc(var(--header-h)+16px)] pb-16 pt-16 lg:pb-[88px] lg:pt-[88px]" aria-labelledby="ob-build">
                <header class="os-hh-head">
                    <div>
                        <p class="os-hh-eyebrow" data-ob-progress>Paso 1 de {{ count($firstAvailable['steps'] ?? []) ?: 1 }}</p>
                        <h2 id="ob-build" @class(['os-hh-title', 'os-josefin' => $girls])>Arma un look listo</h2>
                        <p class="os-hh-sub">Elige una prenda por paso a precio de combo. Puedes cambiarla cuando quieras.</p>
                    </div>
                    <button type="button" class="os-hh-link os-focus" data-ob-reset>Reiniciar look ↺</button>
                </header>

                <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[440px_minmax(0,1fr)]">
                    {{-- Vista previa del look --}}
                    <form method="POST" action="{{ $firstAvailable['cart_url'] ?? '#' }}" class="order-2 flex scroll-mt-[calc(var(--header-h)+12px)] flex-col gap-3 self-start bg-[var(--hh-soft)] p-5 sm:p-6 lg:order-none" data-ob-form>
                        @csrf
                        <div data-ob-inputs></div>
                        <div class="flex items-end justify-between gap-3">
                            <h3 @class(['leading-none', 'os-hh-display text-[32px]' => ! $girls, 'os-josefin text-[23px] uppercase' => $girls])>Tu look</h3>
                            <a href="#looks" class="os-focus text-[13px] font-extrabold text-[var(--hh-accent)] hover:underline">Cambiar look</a>
                        </div>
                        <p class="-mt-1 text-[13px] font-bold text-[#545966]" data-ob-combo>{{ $firstAvailable['name'] ?? '' }}</p>
                        @if ($errors->any())
                            <p class="bg-white px-3 py-2 text-[13px] font-bold text-[#c0392b]" role="alert">{{ $errors->first() }}</p>
                        @endif
                        <ul class="flex flex-col gap-3" data-ob-slots></ul>

                        <dl class="flex flex-col gap-2 pt-3">
                            <div class="flex justify-between text-[14px]">
                                <dt class="font-medium text-[#545966]" data-ob-subtotal-label>Subtotal (0 prendas)</dt>
                                <dd class="font-bold" data-ob-subtotal>S/ 0.00</dd>
                            </div>
                            <div class="hidden justify-between text-[14px] text-[var(--hh-accent)]" data-ob-saving-row>
                                <dt class="font-medium">Ahorro con combo</dt>
                                <dd class="font-bold" data-ob-saving></dd>
                            </div>
                            <div class="flex items-baseline justify-between font-extrabold">
                                <dt class="text-[18px]">Total del look</dt>
                                <dd class="text-[24px]" data-ob-total>S/ {{ number_format($firstAvailable['price'] ?? 0, 2) }}</dd>
                            </div>
                        </dl>
                        <button type="submit" class="os-hh-btn os-focus w-full bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)] disabled:cursor-not-allowed disabled:opacity-40" data-ob-submit disabled>Agregar look al carrito</button>
                        <p class="text-center text-[13px] font-bold text-[var(--hh-accent)]" data-ob-hint aria-live="polite"></p>
                    </form>

                    {{-- Selector de prendas --}}
                    <div class="order-1 flex min-w-0 flex-col gap-5 lg:order-none">
                        <div class="flex" role="tablist" aria-label="Prendas del look" data-ob-tabs></div>
                        <div class="flex flex-wrap gap-2" data-ob-chips></div>
                        <ul class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-3" data-ob-options aria-live="polite"></ul>
                    </div>
                </div>
            </section>

            {{-- Looks listos --}}
            <section id="looks" class="os-hh-wrap scroll-mt-[calc(var(--header-h)+16px)] pb-16 lg:pb-[88px]" aria-labelledby="ob-looks">
                <header class="os-hh-head">
                    <div>
                        <p class="os-hh-eyebrow">Inspírate</p>
                        <h2 id="ob-looks" @class(['os-hh-title', 'os-josefin' => $girls])>Looks listos para ti</h2>
                        <p class="os-hh-sub">Combinaciones armadas por nuestro equipo · elige color y talla de cada prenda</p>
                    </div>
                </header>
                <ul class="mt-8 grid grid-cols-2 gap-x-4 gap-y-8 lg:grid-cols-4">
                    @foreach ($combos as $combo)
                        <li>
                            <button type="button" class="os-focus group flex w-full flex-col gap-2.5 text-left disabled:cursor-not-allowed" data-ob-pick="{{ $combo['id'] }}" @disabled(! $combo['available'])>
                                <span class="relative block aspect-[298/400] w-full overflow-hidden bg-[var(--hh-soft)]">
                                    @if ($combo['image'])
                                        <img src="{{ $combo['image'] }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                    @else
                                        <span class="grid h-full grid-cols-{{ max(1, min(3, count($combo['photos']))) }} gap-0.5">
                                            @foreach (array_slice($combo['photos'], 0, 3) as $photo)
                                                <img src="{{ $photo }}" alt="" class="h-full w-full bg-white object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                                            @endforeach
                                        </span>
                                    @endif
                                    <span @class(['absolute left-0 top-0 px-3 py-2 text-[11px] font-extrabold uppercase tracking-[1px] text-white', 'bg-[var(--hh-accent)]' => $combo['available'], 'bg-[#545966]' => ! $combo['available']])>
                                        {{ $combo['available'] ? count($combo['steps']).' prendas' : 'Muy pronto' }}
                                    </span>
                                </span>
                                <span @class(['leading-none', 'os-hh-display text-[24px] sm:text-[28px]' => ! $girls, 'os-josefin text-[19px] !font-bold uppercase' => $girls])>{{ $combo['name'] }}</span>
                                <span class="text-[14px] font-medium text-[#545966]">{{ $combo['tagline'] }}</span>
                                @if ($combo['detail'] !== $combo['tagline'])
                                    <span class="-mt-1.5 text-[13px] font-medium text-[#888]">{{ $combo['detail'] }}</span>
                                @endif
                                <span class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 font-extrabold">
                                    <span class="text-[16px]">S/ {{ number_format($combo['price'], 2) }}</span>
                                    <span class="text-[13px] uppercase text-[var(--hh-accent)] group-hover:underline group-disabled:text-[#888] group-disabled:no-underline">
                                        {{ $combo['available'] ? 'Armar este look →' : implode(', ', $combo['missing']).' en camino' }}
                                    </span>
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Banner ayuda --}}
        <section class="bg-[var(--shop-deep)] py-12 text-white lg:py-14" aria-labelledby="ob-help">
            <div class="os-hh-wrap flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-center">
                <div class="flex flex-col gap-2">
                    <h2 id="ob-help" @class(['os-hh-display text-[40px] leading-[40px] sm:text-[56px] sm:leading-[56px]' => ! $girls, 'os-josefin text-[30px] uppercase leading-[34px] sm:text-[39px] sm:leading-[42px]' => $girls])>¿No sabes qué elegir?</h2>
                    <p class="text-[16px] font-medium text-[var(--shop-deep-muted)]">Escríbenos por WhatsApp con tu talla y estilo, y te armamos el look.</p>
                </div>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="os-hh-btn os-focus shrink-0 bg-[var(--hh-bright)] text-[var(--hh-on-bright)] hover:bg-[var(--hh-bright-hover)]">Hablar con un asesor →</a>
            </div>
        </section>
    </div>
@endsection
