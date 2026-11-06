@extends('layouts.web')

{{--
    Combos de la colección (App\Support\ComboCatalog): sin duplicados por canal de venta, solo con
    prendas disponibles y con la foto de las prendas incluidas. Mismo estilo que la Tienda: cabecera con
    filtros por tipo (?tipo=mix|pack), orden (?orden=unidad|precio|prendas), cómo funciona y tarjetas
    con el precio por prenda destacado. Overshark Girls y Bravos usan su paleta.
--}}

@section('title', 'Combos | '.$brandLabel)
@section('meta_description', 'Combos y packs '.$brandLabel.': lleva más prendas a un precio especial.')

@php
    $girls = $brandLabel === 'Overshark Girls';
    $bravos = mb_strtoupper($brandLabel) === 'BRAVOS';
    $display = $girls ? 'os-josefin' : ($bravos ? 'font-extrabold' : 'os-hh-display');
    $sections = [
        'mix' => ['title' => 'Combina modelos', 'text' => 'Varias prendas distintas en un solo precio.'],
        'pack' => ['title' => 'Packs por modelo', 'text' => 'Tu modelo favorito en varios colores.'],
    ];
    $tipo = in_array(request('tipo'), ['mix', 'pack'], true) ? request('tipo') : '';
    $orden = in_array(request('orden'), ['precio', 'prendas'], true) ? request('orden') : 'unidad';
    $sorts = ['unidad' => 'Mejor precio por prenda', 'precio' => 'Precio total: menor a mayor', 'prendas' => 'Más prendas'];
    $url = fn (array $changes) => route('web.combos.index', array_filter([...request()->only(['marca', 'tipo', 'orden']), ...$changes], fn ($value) => $value !== null && $value !== ''));

    // Los combos con flyer van primero y, por defecto, en el orden en que se cargaron (el del diseño).
    $sorted = match ($orden) {
        'precio' => $combos->sortBy('price'),
        'prendas' => $combos->sortByDesc('quantity'),
        default => $combos->sortBy(fn (array $combo) => $combo['flyer'] ? [0, $combo['id']] : [1, $combo['unit_price']]),
    };
    // Colores de la tarjeta con flyer por marca (diseño "Elige tu combo").
    $promoThemes = [
        'OVERSHARK' => ['card' => 'rounded-[8px] border-[#3fa9f5]/60 bg-[#f0f7ff] text-[#071226]', 'logo' => 'overshark', 'logoClass' => 'h-5 w-[120px]', 'button' => 'bg-[#071226] text-white'],
        'BRAVOS' => ['card' => 'border-[#ffdb00]/60 bg-[#f6f6f4] text-[#050505]', 'logo' => 'bravos', 'logoClass' => 'h-7 w-[88px]', 'button' => 'border-2 border-[#050505] bg-[#ffdb00] text-[#050505]'],
        'GIRLS' => ['card' => 'rounded-[12px] border-[#f07fa6]/60 bg-[#fff1f7] text-[#2a0a1e]', 'logo' => 'girls', 'logoClass' => 'h-7 w-[95px]', 'button' => 'bg-[#f07fa6] text-[#2a0a1e]'],
    ];
    $money = fn (float $amount) => 'S/ '.rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    $grouped = $sorted->groupBy('kind');
    // El combo más conveniente de cada tipo (menor precio por prenda) lleva una etiqueta.
    $best = $combos->groupBy('kind')->map(fn ($items) => $items->sortBy('unit_price')->first()['id'] ?? null);
@endphp

@if ($bravos)
    @section('body-class', 'theme-neon theme-bravos')
@endif

@section('content')
    <div @class(['os-hh', 'os-hh--girls' => $girls, 'os-hh--bravos' => $bravos])>
        <header class="pb-10 pt-10 text-white [background:var(--shop-head)] lg:pt-12">
            <div class="os-hh-wrap flex flex-col items-start gap-3.5">
                <nav aria-label="Ruta" class="text-[13px] font-semibold text-[var(--shop-crumb)]">
                    <a href="{{ route('web.home') }}" class="hover:text-white">Inicio</a>
                    <span class="mx-1.5" aria-hidden="true">›</span>
                    <span class="text-white">Combos {{ $brandLabel }}</span>
                </nav>
                <div class="flex flex-wrap items-baseline gap-x-5 gap-y-1">
                    <h1 @class([$display, 'uppercase leading-[0.95]', 'text-[64px] sm:text-[96px]' => ! $girls && ! $bravos, 'text-[52px] sm:text-[84px]' => $girls, 'text-[44px] sm:text-[64px]' => $bravos])>Combos</h1>
                    <p class="text-[16px] font-bold text-[var(--hh-bright)]">{{ $combos->count() }} {{ $combos->count() === 1 ? 'combo' : 'combos' }}</p>
                </div>
                <p class="max-w-[640px] text-[16px] font-medium text-[var(--shop-intro)]">Lleva más prendas pagando menos. Eliges el color y la talla de cada prenda en el siguiente paso.</p>
                @if ($grouped->count() > 1 || $tipo !== '')
                    <ul class="-mx-4 flex max-w-[calc(100%+2rem)] gap-2.5 overflow-x-auto px-4 pt-3.5 [scrollbar-width:none] sm:mx-0 sm:max-w-full sm:flex-wrap sm:px-0">
                        <li><a href="{{ $url(['tipo' => null]) }}" @class(['os-shop-chip os-focus', 'is-active' => $tipo === '']) @if ($tipo === '') aria-current="page" @endif>Todos <span class="ml-1 opacity-70">({{ $combos->count() }})</span></a></li>
                        @foreach ($sections as $kind => $section)
                            @if ($combos->where('kind', $kind)->isNotEmpty())
                                <li><a href="{{ $url(['tipo' => $kind]) }}" @class(['os-shop-chip os-focus', 'is-active' => $tipo === $kind]) @if ($tipo === $kind) aria-current="page" @endif>{{ $section['title'] }} <span class="ml-1 opacity-70">({{ $combos->where('kind', $kind)->count() }})</span></a></li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </div>
        </header>

        {{-- Cómo funciona --}}
        <section class="border-b border-[var(--shop-line)] bg-[var(--hh-soft)]" aria-label="Cómo funciona">
            <ol class="os-hh-wrap grid gap-4 py-6 sm:grid-cols-3 sm:gap-8">
                @foreach (['Elige tu combo' => 'Combina modelos o lleva tu favorito en varios colores.', 'Escoge talla y color' => 'De cada prenda, entre lo que hay en stock.', 'Recíbelo en casa' => 'Envío desde S/ 15 a todo el Perú. Pago contraentrega o Yape / Plin.'] as $step => $text)
                    <li class="flex items-start gap-3">
                        <span @class(['shrink-0 leading-none text-[var(--hh-accent)]', 'os-hh-display text-[36px]' => ! $girls && ! $bravos, 'os-josefin text-[28px]' => $girls, 'text-[28px] font-extrabold' => $bravos])>{{ sprintf('%02d', $loop->iteration) }}</span>
                        <span class="flex flex-col gap-0.5">
                            <span class="text-[15px] font-extrabold">{{ $step }}</span>
                            <span class="text-[13px] font-medium text-[#545966]">{{ $text }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        </section>

        <div class="os-hh-wrap pb-16 pt-8 lg:pb-[88px] lg:pt-12">
            @if ($combos->isEmpty())
                <div class="flex flex-col items-start gap-4 bg-[var(--hh-soft)] p-8">
                    <p class="text-[20px] font-extrabold">Muy pronto publicaremos nuevos combos.</p>
                    <p class="text-[14px] font-medium text-[#545966]">Mientras tanto, mira todas las prendas de la tienda.</p>
                    <a href="{{ route('web.products.search') }}" class="os-hh-btn os-focus bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)]">Ver la tienda →</a>
                </div>
            @else
                <div class="flex flex-col gap-3 border-b-2 border-[#0b0f14] pb-3.5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[14px] font-bold">{{ $tipo !== '' ? $grouped->get($tipo, collect())->count() : $combos->count() }} combos disponibles</p>
                    <form action="{{ route('web.combos.index') }}" method="GET">
                        @foreach (request()->only(['marca', 'tipo']) as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach
                        <label class="flex h-10 items-center gap-1 border border-[var(--shop-line)] pl-3.5 text-[13px] font-bold">
                            <span class="shrink-0">Ordenar por:</span>
                            <select name="orden" class="os-shop-sort os-focus" data-auto-submit style="background-image: url('{{ asset('images/home-hombre/chevron-abajo.svg') }}')">
                                @foreach ($sorts as $value => $label)
                                    <option value="{{ $value }}" @selected($orden === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <noscript><button type="submit" class="mt-2 text-[13px] font-bold underline">Ordenar</button></noscript>
                    </form>
                </div>

                @foreach ($sections as $kind => $section)
                    @continue (! $grouped->has($kind) || ($tipo !== '' && $tipo !== $kind))
                    <section id="combos-{{ $kind }}" class="mt-10 scroll-mt-[calc(var(--header-h)+1rem)]" aria-labelledby="combos-{{ $kind }}-title">
                        <div class="flex flex-col gap-1">
                            <h2 id="combos-{{ $kind }}-title" @class(['uppercase leading-none', 'os-hh-display text-[36px] sm:text-[44px]' => ! $girls && ! $bravos, 'os-josefin text-[28px] sm:text-[32px]' => $girls, 'text-[26px] font-extrabold sm:text-[30px]' => $bravos])>{{ $section['title'] }}</h2>
                            <p class="text-[14px] font-medium text-[#545966]">{{ $section['text'] }}</p>
                        </div>

                        <ul @class(['mt-6 grid gap-x-3 gap-y-6 sm:gap-x-5 sm:gap-y-8', 'grid-cols-2 lg:grid-cols-3 xl:grid-cols-4' => ! $grouped[$kind]->contains('flyer', '!=', null), 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3' => $grouped[$kind]->contains('flyer', '!=', null)])>
                            @foreach ($grouped[$kind] as $combo)
                                @if ($combo['flyer'])
                                    @php($theme = $promoThemes[$combo['brand']] ?? $promoThemes['OVERSHARK'])
                                    <li>
                                        {{-- Tarjeta con flyer (diseño "Elige tu combo"): flyer completo, marca, "N × S/ precio" y botón. --}}
                                        <a href="{{ $combo['url'] }}" class="os-focus group flex h-full flex-col gap-4 border px-4 pb-5 pt-4 transition hover:shadow-[0_8px_24px_rgb(0_0_0/12%)] {{ $theme['card'] }}">
                                            <img src="{{ $combo['flyer'] }}" alt="Flyer del combo {{ $combo['title'] }}" class="h-auto w-full rounded-[10px]" loading="lazy" decoding="async">
                                            <img src="{{ asset('images/combos/logo-'.$theme['logo'].'.svg') }}" alt="" class="shrink-0 {{ $theme['logoClass'] }}" width="120" height="28">
                                            <h3 class="text-[21px] font-extrabold leading-[1.2]">{{ $combo['title'] }}</h3>
                                            <p class="text-[30px] font-extrabold leading-none">{{ $combo['quantity'] }} × {{ $money($combo['price']) }}</p>
                                            <p class="text-[14px]">Elige color y talla de cada prenda.</p>
                                            <span class="mt-auto flex items-center justify-center py-4 text-[14px] font-extrabold tracking-[.28px] transition group-hover:opacity-90 {{ $theme['button'] }}">ELEGIR ESTE COMBO</span>
                                        </a>
                                    </li>
                                    @continue
                                @endif
                                <li>
                                    <a href="{{ $combo['url'] }}" class="os-focus group flex h-full flex-col border border-[var(--shop-line)] bg-white transition hover:border-[#0b0f14]">
                                        <div class="relative grid aspect-[4/3] overflow-hidden bg-[var(--hh-soft)]" style="grid-template-columns: repeat({{ max(1, count($combo['images'])) }}, minmax(0, 1fr))">
                                            @forelse ($combo['images'] as $image)
                                                <img src="{{ $image }}" alt="" class="h-full w-full object-cover object-top transition duration-500 group-hover:scale-105" loading="lazy" decoding="async">
                                            @empty
                                                <span class="grid place-items-center text-[48px] font-extrabold text-[#0b0f14]/15">{{ $combo['quantity'] }}x</span>
                                            @endforelse
                                            <span class="absolute left-0 top-0 bg-[#0b0f14] px-2.5 py-1.5 text-[10px] font-extrabold uppercase tracking-[1px] text-white sm:px-3 sm:py-2 sm:text-[11px]">{{ $combo['quantity'] }} prendas</span>
                                            @if ($best[$kind] === $combo['id'])
                                                <span class="absolute bottom-0 left-0 bg-[var(--hh-bright)] px-2.5 py-1.5 text-[10px] font-extrabold uppercase tracking-[1px] text-[var(--hh-on-bright)] sm:px-3 sm:py-2 sm:text-[11px]">Más conveniente</span>
                                            @endif
                                        </div>
                                        <div class="flex flex-1 flex-col gap-1.5 p-3 sm:p-5">
                                            <h3 class="text-[15px] font-extrabold leading-tight sm:text-[17px]">{{ $combo['title'] }}</h3>
                                            <p class="line-clamp-2 text-[12px] font-medium leading-[18px] text-[#545966] sm:line-clamp-none sm:text-[13px] sm:leading-5">{{ $combo['detail'] }}</p>
                                            <div class="mt-auto flex items-end justify-between gap-3 pt-3 sm:pt-4">
                                                <p class="flex flex-col">
                                                    <span class="text-[18px] font-extrabold leading-none sm:text-[22px]">S/ {{ number_format($combo['price'], 2) }}</span>
                                                    <span class="mt-1 text-[12px] font-extrabold text-[var(--hh-accent)]">S/ {{ number_format($combo['unit_price'], 2) }} c/u</span>
                                                </p>
                                                <span class="hidden bg-[#0b0f14] px-4 py-2.5 text-[12px] font-extrabold uppercase tracking-[1px] text-white transition group-hover:bg-[var(--hh-accent)] sm:inline-block">Elegir →</span>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            @endif
        </div>

        {{-- Ayuda --}}
        @if ($whatsappUrl = \App\Models\BrandSetting::current()->whatsappUrl())
            <section class="bg-[var(--shop-deep)] py-10 text-white" aria-labelledby="combos-help">
                <div class="os-hh-wrap flex flex-col items-start justify-between gap-5 lg:flex-row lg:items-center">
                    <div class="flex flex-col gap-1.5">
                        <h2 id="combos-help" class="text-[22px] font-extrabold sm:text-[26px]">¿No sabes qué combo elegir?</h2>
                        <p class="text-[15px] font-medium text-[var(--shop-deep-muted)]">Cuéntanos tu talla y estilo por WhatsApp y te recomendamos uno.</p>
                    </div>
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="os-hh-btn os-focus shrink-0 bg-[var(--hh-bright)] text-[var(--hh-on-bright)] hover:bg-[var(--hh-bright-hover)]">Hablar con un asesor →</a>
                </div>
            </section>
        @endif
    </div>
@endsection
