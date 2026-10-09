@extends('layouts.web')

@section('title', 'Arma tu outfit con IA | Overshark')

@php
    $lineLabel = $line === 'mujer' ? 'mujer' : 'hombre';
    $cartCount = collect(session('cart.items', []))->sum('qty');
    $hasPhoto = $state['photo_url'] !== null;
@endphp

@push('head')
    {{--
        El probador procesa la foto en el navegador. Solo se permiten conexiones a la propia tienda:
        así ninguna librería (p. ej. la telemetría de MediaPipe a googleapis.com) envía datos a terceros.
        Con el servidor de desarrollo de Vite activo se omite, porque usa su propio puerto.
    --}}
    @unless (app(\Illuminate\Foundation\Vite::class)->isRunningHot())
        <meta http-equiv="Content-Security-Policy" content="connect-src 'self' blob: data:">
    @endunless
@endpush

@section('header-bar')
    <div class="mx-auto flex h-14 max-w-[1440px] items-center gap-4 px-4 sm:px-6 xl:h-[52px] xl:px-16">
        <a href="{{ route('web.home') }}" class="os-focus block rounded" aria-label="Overshark, ir al inicio">
            <span class="block font-heading text-[21px] font-extrabold leading-none tracking-tight sm:text-[23px]" aria-hidden="true">OVER<span class="text-electric">SHARK</span></span>
        </a>
        <span class="h-6 w-px bg-white/30" aria-hidden="true"></span>
        <span class="text-xs font-semibold uppercase tracking-[0.12em] text-white sm:text-sm">Probador IA</span>
        <button type="button" data-cart-open class="os-focus relative ml-auto grid h-10 w-10 place-items-center rounded-full text-white transition hover:bg-white/10" aria-label="Carrito de compras, {{ $cartCount }} {{ $cartCount === 1 ? 'producto' : 'productos' }}">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 4h2.2l2.3 11.2a1.5 1.5 0 0 0 1.5 1.2h8.3a1.5 1.5 0 0 0 1.5-1.1L20.5 8H6.1"/><circle cx="9.5" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/></svg>
            @if ($cartCount > 0)
                <span class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-electric px-1 text-[11px] font-black leading-none text-white" aria-hidden="true">{{ $cartCount }}</span>
            @endif
        </button>
    </div>
@endsection

@section('content')
    <div class="os-ocean relative isolate -mt-14 overflow-hidden pb-12 pt-14 xl:-mt-[52px] xl:pt-[52px]" data-outfit-builder>
        {{-- Fondo del alto de la pantalla; se funde hacia abajo con el océano oscuro para que los paneles se lean bien. --}}
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[100svh] min-h-[640px] max-h-[1100px] [mask-image:linear-gradient(180deg,#000_70%,transparent)]" aria-hidden="true">
            <img
                src="{{ asset('images/outfit/fondo-probador.webp') }}"
                srcset="{{ asset('images/outfit/fondo-probador-1000.webp') }} 1000w, {{ asset('images/outfit/fondo-probador.webp') }} 1672w"
                sizes="100vw"
                alt=""
                width="1672"
                height="941"
                class="h-full w-full object-cover object-[70%_top]"
                fetchpriority="high"
                decoding="async"
            >
            <div class="absolute inset-0 bg-[linear-gradient(180deg,rgb(2_7_19/15%)_0%,rgb(2_7_19/35%)_45%,rgb(2_7_19/70%)_100%)]"></div>
        </div>

        <div class="mx-auto max-w-[1640px] px-4 sm:px-6 xl:px-10">
            <div class="relative">
                <a href="{{ route('web.home', ['linea' => $line]) }}" class="os-focus mt-4 inline-flex items-center gap-2 rounded text-xs font-semibold uppercase tracking-wide text-white transition hover:text-electric-soft sm:text-sm xl:absolute xl:left-0 xl:top-0 xl:mt-5">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 12H4m6-6-6 6 6 6"/></svg>
                    Volver a {{ $lineLabel }}
                </a>

                <header class="mt-2 text-center xl:mt-0 xl:pt-4">
                    <h1 class="font-heading text-[30px] font-black uppercase leading-[1.05] tracking-tight sm:text-5xl xl:text-[52px]">
                        <span class="text-electric">Arma tu outfit</span> con IA
                    </h1>
                    <p class="mt-1.5 text-base font-medium text-white/90 sm:text-lg">Crea tu modelo. Combina tus prendas. Encuentra tu estilo.</p>
                    <x-outfit.steps :ai-available="$aiAvailable"/>
                </header>
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-2 xl:h-[clamp(620px,calc(100svh-262px),800px)] xl:grid-cols-[minmax(0,0.78fr)_minmax(0,1.28fr)_minmax(0,1.04fr)] xl:gap-4">
                {{-- Panel 1: foto --}}
                <x-outfit.panel title="Tu foto" title-id="outfit-photo-title" class="xl:min-h-0">
                    <p class="mt-1 text-sm leading-5 text-mist">Usa una foto clara y bien iluminada de una sola persona, con el rostro y la parte superior del cuerpo.</p>

                    <div class="os-panel-inset mt-4 flex min-h-0 flex-1 flex-col overflow-y-auto p-4 [scrollbar-color:#2a4a8c_transparent] [scrollbar-width:thin]">
                        <div class="relative mx-auto aspect-square w-full max-w-[300px] overflow-hidden rounded-lg bg-panel xl:aspect-auto xl:min-h-[200px] xl:flex-1">
                            <img data-photo-preview src="{{ $state['photo_url'] ?? '' }}" alt="Tu foto para el probador" @class(['h-full w-full object-cover object-top', 'hidden' => ! $hasPhoto])>
                            <div data-photo-empty @class(['absolute inset-0 grid place-items-center p-6 text-center', 'hidden' => $hasPhoto])>
                                <div>
                                    <svg class="mx-auto h-12 w-12 text-electric-soft" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4.5 21c.6-4.4 3.4-7 7.5-7s6.9 2.6 7.5 7"/></svg>
                                    <p class="mt-3 text-sm font-semibold text-mist">Aún no has agregado una foto.</p>
                                </div>
                            </div>
                            <svg class="pointer-events-none absolute inset-3 h-[calc(100%-1.5rem)] w-[calc(100%-1.5rem)] text-white/85" viewBox="0 0 100 100" preserveAspectRatio="none" fill="none" aria-hidden="true">
                                <path d="M1 12V1h11M88 1h11v11M99 88v11H88M12 99H1V88" stroke="currentColor" stroke-width="1.4" vector-effect="non-scaling-stroke"/>
                            </svg>
                        </div>

                        <div class="mx-auto mt-4 flex w-full max-w-[300px] flex-col gap-3">
                            <button type="button" data-photo-camera class="os-btn-ghost os-focus h-11 w-full gap-3 px-4 text-sm">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 8.5A1.5 1.5 0 0 1 5.5 7h2.2l1.4-2h5.8l1.4 2h2.2A1.5 1.5 0 0 1 20 8.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5Z"/><circle cx="12" cy="12.5" r="3.2"/></svg>
                                <span data-photo-camera-label>{{ $hasPhoto ? 'Tomar otra foto' : 'Tomar foto' }}</span>
                            </button>
                            <label class="os-btn-solid os-focus-within h-11 w-full cursor-pointer gap-3 px-4 text-sm">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V4m-5 5 5-5 5 5M5 20h14"/></svg>
                                <span data-photo-upload-label>{{ $hasPhoto ? 'Reemplazar foto' : 'Subir foto' }}</span>
                                <input type="file" data-photo-input accept="image/jpeg,image/png,image/webp" class="sr-only">
                            </label>
                            {{-- Giro 360°: video corto girando sobre sí mismo (se procesa solo en el navegador). --}}
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" data-turn-record class="os-btn-ghost os-focus h-10 gap-2 px-2 text-[11px]">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 12a8 8 0 0 1 14-5.3M20 12a8 8 0 0 1-14 5.3"/><path d="M18 3v4h-4M6 21v-4h4"/></svg>
                                    Grabar giro 360°
                                </button>
                                <label class="os-btn-ghost os-focus-within h-10 cursor-pointer gap-2 px-2 text-[11px]">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3"/></svg>
                                    Subir video
                                    <input type="file" data-turn-input accept="video/mp4,video/webm,video/quicktime" class="sr-only">
                                </label>
                            </div>
                            <button type="button" data-photo-delete @class(['os-focus rounded-md py-1 text-xs font-bold uppercase tracking-wide text-haze transition hover:text-red-300', 'hidden' => ! $hasPhoto])>
                                Eliminar foto
                            </button>
                        </div>

                        <p data-photo-status class="mx-auto mt-2 min-h-4 max-w-[300px] text-center text-xs font-semibold text-electric-soft" role="status" aria-live="polite"></p>

                        <p class="mx-auto mt-auto flex max-w-[300px] gap-2 pt-2 text-[11px] leading-4 text-mist">
                            <svg class="mt-px h-4 w-4 shrink-0 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 10V7a5 5 0 0 1 10 0v3h1a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-9a1 1 0 0 1 1-1Zm2 0h6V7a3 3 0 0 0-6 0Z"/></svg>
                            <span>
                                Tu foto se guarda en privado, solo la ves en esta sesión y se borra a las {{ $retentionHours }} horas o al pulsar «Eliminar foto».
                                @if ($aiAvailable)
                                    Solo se enviará a {{ $aiProvider }} si lo aceptas al generar tu modelo.
                                @else
                                    No se envía a ningún servicio externo.
                                @endif
                            </span>
                        </p>
                    </div>
                </x-outfit.panel>

                {{-- Panel 2: modelo. El panel completo es el escenario. --}}
                <x-outfit.panel title="Tu modelo IA" title-id="outfit-model-title" class="relative isolate overflow-hidden xl:min-h-0">
                    <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
                        <img src="{{ asset('images/outfit/fondo-probador-1000.webp') }}" alt="" class="h-full w-full object-cover object-[50%_85%] opacity-80" loading="lazy" decoding="async">
                        <div class="absolute inset-0 bg-[radial-gradient(ellipse_55%_45%_at_50%_40%,rgb(38_117_255/22%),transparent_70%),linear-gradient(180deg,rgb(3_10_26/85%)_0%,rgb(3_10_26/25%)_30%,rgb(3_10_26/30%)_70%,rgb(3_10_26/92%)_100%)]"></div>
                    </div>

                    <p data-model-description class="mt-1 max-w-sm text-sm leading-5 text-mist">Genera tu modelo a partir de tu foto para probarte las prendas.</p>

                    <div class="relative flex min-h-[420px] flex-1 items-end justify-center xl:min-h-0">
                        <canvas data-fitting-canvas class="os-stage-canvas relative z-10 mb-12 hidden h-[88%] max-h-[560px] w-auto max-w-full" role="img" aria-label="Tu foto con el outfit elegido (simulación aproximada)"></canvas>
                        <img data-model-image src="{{ $state['model']['image_url'] ?? '' }}" alt="Tu modelo generado con IA" @class(['relative z-10 mb-14 h-[86%] max-h-[520px] w-auto object-contain', 'hidden' => ! $state['model']['image_url']])>

                        @if ($line === 'hombre')
                            <img data-model-example src="{{ asset('images/home/hero-model-hombre.webp') }}" width="300" height="510" alt="Modelo de ejemplo con polo verde" @class(['relative z-10 mb-12 h-[88%] max-h-[540px] w-auto object-contain [mask-image:linear-gradient(180deg,#000_88%,transparent)]', 'hidden' => (bool) $state['model']['image_url']])>
                        @else
                            <svg data-model-example @class(['relative z-10 mb-16 h-[70%] max-h-[360px] w-auto text-electric-soft/45', 'hidden' => (bool) $state['model']['image_url']]) viewBox="0 0 120 300" fill="none" aria-hidden="true">
                                <path d="M60 18c11 0 19 9 19 21 0 9-4 16-9 20v9c18 4 30 12 32 26l5 70c1 8-11 9-12 1l-6-58-2 186H70l-4-110h-12l-4 110H33l-2-186-6 58c-1 8-13 7-12-1l5-70c2-14 14-22 32-26v-9c-5-4-9-11-9-20 0-12 8-21 19-21Z" stroke="currentColor" stroke-width="1.5" stroke-dasharray="4 5"/>
                            </svg>
                        @endif

                        <div class="os-platform absolute bottom-4 left-1/2 h-[70px] w-[76%] max-w-[460px] -translate-x-1/2" aria-hidden="true"></div>

                        <p data-model-example-label @class(['absolute left-0 top-3 z-20 rounded-md border border-white/25 bg-ink/70 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wide text-mist', 'hidden' => (bool) $state['model']['image_url']])>
                            Modelo de ejemplo
                        </p>

                        <p data-model-badge class="absolute right-0 top-20 z-20 hidden items-center gap-2 rounded-md border border-emerald-400/70 bg-emerald-500/15 px-3 py-1.5 text-xs font-bold uppercase text-emerald-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                            Outfit actualizado
                        </p>

                        {{-- Vista 3D: la foto del cliente con las prendas, en relieve (ver photo-3d.js). --}}
                        <div data-viewer-360 class="absolute inset-0 z-30 hidden overflow-hidden rounded-xl bg-[radial-gradient(ellipse_60%_45%_at_50%_35%,rgb(38_117_255/24%),transparent_70%),linear-gradient(180deg,#06183a,#030c1e)]" aria-live="polite">
                            <div data-viewer-360-stage class="h-full w-full"></div>
                            <p data-viewer-360-status class="absolute inset-x-0 top-1/2 hidden -translate-y-1/2 text-center text-sm font-semibold text-mist">Cargando vista 3D…</p>
                            <p data-viewer-360-note class="absolute bottom-3 left-3 max-w-[45%] rounded-md border border-white/25 bg-ink/75 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wide text-mist">3D aproximado</p>
                            <div class="absolute bottom-3 right-3 flex items-center gap-1 rounded-full border border-electric/50 bg-ink/85 p-1">
                                <button type="button" data-viewer-360-left class="os-focus grid h-9 w-9 place-items-center rounded-full text-white hover:bg-white/10" aria-label="Girar a la izquierda">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                                </button>
                                <button type="button" data-viewer-360-auto class="os-focus h-9 rounded-full px-3 text-xs font-bold uppercase text-white hover:bg-white/10" aria-pressed="true">Pausar giro</button>
                                <button type="button" data-viewer-360-right class="os-focus grid h-9 w-9 place-items-center rounded-full text-white hover:bg-white/10" aria-label="Girar a la derecha">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                                </button>
                            </div>
                        </div>

                        <button type="button" data-viewer-360-toggle aria-pressed="false" class="os-focus absolute right-0 top-3 z-40 flex flex-col items-center gap-1 rounded-lg border border-electric/70 bg-ink/85 px-3 py-2 text-[10px] font-bold uppercase leading-tight text-white shadow-[0_0_16px_rgb(38_117_255/35%)] transition hover:bg-electric/20">
                            <svg class="h-7 w-7 text-electric-soft" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><ellipse cx="16" cy="17" rx="12" ry="5"/><path d="M8 9l3-3 3 3M11 6v5"/><path d="M24 25l-3 3-3-3M21 28v-5"/></svg>
                            <span data-viewer-360-label>Ver en 3D</span>
                        </button>
                    </div>

                    <div class="relative z-10">
                        <div data-model-message class="rounded-lg border border-edge bg-ink/75 px-4 py-2.5 text-sm leading-5 text-mist" role="status" aria-live="polite"></div>

                        @if ($aiAvailable)
                            <label class="mt-3 flex items-start gap-3 text-xs leading-5 text-mist">
                                <input type="checkbox" data-model-consent class="mt-0.5 h-4 w-4 shrink-0 rounded border-edge-strong bg-panel text-electric focus:ring-electric">
                                <span>Acepto que mi foto y las prendas elegidas se envíen a {{ $aiProvider }} para probarme el outfit. Según FASHN, no guarda la imagen completa en su historial y el resultado deja de estar disponible en sus servidores a los 60 minutos. Aquí se guarda en privado y se borra a las {{ $retentionHours }} horas.</span>
                            </label>
                        @endif

                        <button type="button" data-model-generate @class(['os-btn-solid os-focus mt-3 h-11 w-full px-6 text-sm', 'hidden' => ! $aiAvailable]) disabled>
                            <span data-model-generate-label>Generar mi modelo</span>
                        </button>

                        <div data-selection-preview class="mt-3 hidden">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-haze" data-selection-preview-title>Prendas seleccionadas</p>
                            <ul class="mt-1.5 flex flex-wrap gap-2" data-selection-preview-list></ul>
                        </div>
                    </div>
                </x-outfit.panel>

                {{-- Panel 3: productos --}}
                <x-outfit.panel :title="'Productos para '.$lineLabel" title-id="outfit-products-title" class="lg:col-span-2 xl:col-span-1 xl:min-h-0">
                    <p class="mt-1 text-sm leading-5 text-mist">Explora toda la colección y pruébala en tu modelo.</p>

                    <div class="-mx-1 mt-3 flex gap-1.5 overflow-x-auto px-1 pb-1 [scrollbar-color:#2a4a8c_transparent] [scrollbar-width:thin]" role="group" aria-label="Filtrar por categoría">
                        <button type="button" data-filter="all" aria-pressed="true" class="os-filter os-focus">Todos</button>
                        @foreach ($categories as $slug => $label)
                            <button type="button" data-filter="{{ $slug }}" aria-pressed="false" class="os-filter os-focus">{{ $label }}</button>
                        @endforeach
                    </div>

                    @if ($products->isEmpty())
                        <div class="mt-4 grid min-h-60 flex-1 place-items-center rounded-xl border border-dashed border-edge px-5 text-center">
                            <div>
                                <p class="text-base font-black text-mist">Sin prendas con stock</p>
                                <p class="mt-1 text-sm text-haze">Cuando el inventario de ZAZU tenga stock, las prendas aparecerán aquí.</p>
                            </div>
                        </div>
                    @else
                        <ul class="mt-3 grid auto-rows-max grid-cols-2 gap-2.5 [scrollbar-color:#2a4a8c_transparent] max-h-[560px] overflow-y-auto pr-1 sm:grid-cols-3 lg:grid-cols-4 xl:max-h-none xl:min-h-0 xl:flex-1 xl:grid-cols-3" data-product-grid aria-label="Prendas disponibles">
                            @foreach ($products as $product)
                                <li data-product-card data-product-id="{{ $product['id'] }}" data-category="{{ $product['category'] }}" class="os-product-card flex flex-col rounded-lg">
                                    <div class="os-product-media relative aspect-[10/9] overflow-hidden rounded-t-[7px]">
                                        @if ($product['image'])
                                            <img src="{{ $product['image'] }}" alt="" class="h-full w-full object-cover" loading="lazy" decoding="async" width="300" height="270">
                                        @else
                                            <x-storefront.garment :type="$product['garment']" :color="$product['swatch']" class="absolute inset-0 m-auto h-[80%] w-auto drop-shadow-[0_10px_14px_rgb(0_0_0/60%)]"/>
                                        @endif
                                        @if ($product['colors'] > 1)
                                            <span class="absolute right-1.5 top-1.5 rounded bg-ink/80 px-1.5 py-0.5 text-[10px] font-semibold text-mist">{{ $product['colors'] }} colores</span>
                                        @endif
                                        <span data-product-selected-badge class="absolute inset-x-0 bottom-0 items-center justify-center gap-1.5 bg-electric py-1 text-[11px] font-bold uppercase text-white">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                                            <span data-product-selected-text>Seleccionada</span>
                                        </span>
                                    </div>
                                    <div class="flex flex-1 items-end justify-between gap-2 p-2">
                                        <div class="min-w-0">
                                            <p class="line-clamp-2 text-xs font-semibold leading-4 text-white">{{ $product['name'] }}</p>
                                            @if ($product['price'] !== null)
                                                <p class="mt-0.5 text-xs font-black text-white">S/ {{ number_format($product['price'], 2) }}</p>
                                            @else
                                                <p class="mt-0.5 text-[10px] font-semibold leading-3 text-haze">Precio por<br>confirmar</p>
                                            @endif
                                        </div>
                                        <button type="button" data-product-choose class="os-focus grid h-8 w-8 shrink-0 place-items-center rounded-md border border-electric-soft/70 text-white transition hover:bg-electric/30" aria-label="Elegir {{ $product['name'] }}">
                                            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M9 3 4 5.5 2.5 10l3 1.2V21h13v-9.8l3-1.2L20 5.5 15 3a3 3 0 0 1-6 0Z"/></svg>
                                        </button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <p data-filter-empty class="mt-4 hidden text-center text-sm text-haze">No hay prendas en esta categoría.</p>
                    @endif

                    <div class="mt-3 border-t border-edge pt-3">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-haze">Tu outfit</p>
                        <ul class="mt-2 max-h-36 space-y-1.5 overflow-y-auto [scrollbar-color:#2a4a8c_transparent]" data-selection-list aria-live="polite"></ul>
                        <p data-selection-empty class="mt-1 text-xs text-haze">Elige una prenda superior, una inferior y hasta 3 complementos.</p>
                        <p data-selection-error class="mt-2 hidden text-sm font-semibold text-red-300" role="alert"></p>
                    </div>

                    @if ($aiAvailable)
                        <button type="button" data-outfit-try class="os-btn-ghost os-focus mt-3 h-11 w-full text-sm" disabled>Probar outfit</button>
                    @endif
                    <button type="button" data-outfit-finish class="os-btn-solid os-focus mt-3 h-12 w-full shrink-0 text-sm" disabled>
                        Finalizar outfit
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg>
                    </button>
                </x-outfit.panel>
            </div>
        </div>
    </div>

    {{-- Diálogo de grabación del giro 360° --}}
    <dialog data-turn-dialog class="os-dialog" aria-labelledby="outfit-turn-title">
        <div class="p-5 sm:p-6">
            <h2 id="outfit-turn-title" class="font-heading text-xl font-black uppercase">Graba tu giro 360°</h2>
            <p class="mt-1 text-sm leading-5 text-mist">Aléjate hasta que se vea todo tu cuerpo. Al empezar tendrás 3 segundos; luego gira despacio una vuelta completa en unos 8 segundos.</p>
            <div class="relative mt-4 aspect-[3/4] w-full overflow-hidden rounded-lg bg-black">
                <video data-turn-video class="h-full w-full object-cover" playsinline muted></video>
                <p data-turn-countdown class="absolute inset-0 hidden place-items-center text-7xl font-black text-white drop-shadow-[0_2px_8px_rgb(0_0_0/80%)]"></p>
                <div class="absolute inset-x-0 bottom-0 h-1.5 bg-white/15"><div data-turn-bar class="h-full w-0 bg-electric transition-[width]"></div></div>
            </div>
            <p data-turn-status class="mt-3 min-h-5 text-sm text-mist" role="status" aria-live="polite"></p>
            <div class="mt-4 flex flex-wrap justify-end gap-3">
                <button type="button" data-turn-cancel class="os-focus rounded-md px-4 py-2.5 text-sm font-bold uppercase text-mist hover:text-white">Cancelar</button>
                <button type="button" data-turn-start class="os-btn-solid os-focus h-11 px-5 text-sm">Empezar</button>
            </div>
        </div>
    </dialog>

    {{-- Diálogo de cámara --}}
    <dialog data-camera-dialog class="os-dialog" aria-labelledby="outfit-camera-title">
        <div class="p-5 sm:p-6">
            <h2 id="outfit-camera-title" class="font-heading text-xl font-black uppercase">Tomar foto</h2>
            <div class="relative mt-4 aspect-[3/4] w-full overflow-hidden rounded-lg bg-black sm:aspect-[4/3]">
                <video data-camera-video class="h-full w-full object-cover" playsinline muted></video>
                <img data-camera-capture alt="Foto capturada" class="absolute inset-0 hidden h-full w-full object-cover">
            </div>
            <p data-camera-status class="mt-3 min-h-5 text-sm text-mist" role="status" aria-live="polite"></p>
            <div class="mt-4 flex flex-wrap justify-end gap-3">
                <button type="button" data-camera-cancel class="os-focus rounded-md px-4 py-2.5 text-sm font-bold uppercase text-mist hover:text-white">Cancelar</button>
                <button type="button" data-camera-retake class="os-btn-ghost os-focus hidden h-11 px-5 text-sm">Repetir</button>
                <button type="button" data-camera-shoot class="os-btn-solid os-focus h-11 px-5 text-sm">Capturar</button>
                <button type="button" data-camera-confirm class="os-btn-solid os-focus hidden h-11 px-5 text-sm">Usar esta foto</button>
            </div>
        </div>
    </dialog>

    {{-- Diálogo de talla y color --}}
    <dialog data-variant-dialog class="os-dialog" aria-labelledby="outfit-variant-title">
        <form method="dialog" class="p-5 sm:p-6" data-variant-form>
            <h2 id="outfit-variant-title" class="font-heading text-lg font-black uppercase leading-tight" data-variant-title></h2>
            <p class="mt-1 text-sm font-black text-electric-soft" data-variant-price></p>
            <fieldset class="mt-4">
                <legend class="text-xs font-bold uppercase tracking-[0.14em] text-haze">Color</legend>
                <div class="mt-2 flex flex-wrap gap-2" data-variant-colors></div>
            </fieldset>
            <fieldset class="mt-4">
                <legend class="text-xs font-bold uppercase tracking-[0.14em] text-haze">Talla</legend>
                <div class="mt-2 flex flex-wrap gap-2" data-variant-sizes></div>
            </fieldset>
            <p class="mt-3 hidden text-xs text-amber-300" data-variant-replace role="status"></p>
            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="button" data-variant-remove class="os-focus mr-auto hidden rounded-md px-3 py-2.5 text-sm font-bold uppercase text-red-300 hover:text-red-200">Quitar</button>
                <button type="button" data-variant-cancel class="os-focus rounded-md px-4 py-2.5 text-sm font-bold uppercase text-mist hover:text-white">Cancelar</button>
                <button type="submit" data-variant-confirm class="os-btn-solid os-focus h-11 px-5 text-sm">Seleccionar</button>
            </div>
        </form>
    </dialog>

    {{-- Diálogo de resumen --}}
    <dialog data-summary-dialog class="os-dialog" aria-labelledby="outfit-summary-title">
        <div class="p-5 sm:p-6">
            <h2 id="outfit-summary-title" class="font-heading text-xl font-black uppercase">Resumen de tu outfit</h2>
            <p class="mt-1 text-xs text-haze">Precios y stock verificados ahora con el catálogo. No se realiza ningún cobro.</p>
            <ul class="mt-4 divide-y divide-edge" data-summary-lines></ul>
            <div class="mt-4 flex items-center justify-between border-t border-edge pt-4 text-base font-black">
                <span>Total</span>
                <span data-summary-total>S/ 0.00</span>
            </div>
            <p data-summary-message class="mt-3 min-h-5 text-sm font-semibold" role="status" aria-live="polite"></p>
            <div class="mt-5 flex flex-wrap justify-end gap-3">
                <button type="button" data-summary-close class="os-focus rounded-md px-4 py-2.5 text-sm font-bold uppercase text-mist hover:text-white">Seguir editando</button>
                <button type="button" data-summary-confirm class="os-btn-solid os-focus h-11 px-5 text-sm" disabled>Agregar al carrito</button>
            </div>
        </div>
    </dialog>

    @php
        $builderData = [
            'products' => $products,
            'state' => $state,
            'aiAvailable' => $aiAvailable,
            'maxPhotoBytes' => 10 * 1024 * 1024,
            'routes' => [
                'photoStore' => route('web.outfit.photo.store'),
                'photoDestroy' => route('web.outfit.photo.destroy'),
                'modelGenerate' => route('web.outfit.model.generate'),
                'modelStatus' => route('web.outfit.model.status'),
                'tryOn' => route('web.outfit.try-on'),
                'summary' => route('web.outfit.summary'),
                'cart' => route('web.outfit.cart'),
            ],
        ];
    @endphp
    {{-- Ilustraciones que el probador usa cuando una prenda aún no tiene imagen recortada. --}}
    @foreach (['polo', 'camisa', 'pantalon'] as $garmentType)
        <template data-garment-template="{{ $garmentType }}"><x-storefront.garment :type="$garmentType" color="#888888"/></template>
    @endforeach

    <script type="application/json" id="outfit-builder-data">@json($builderData)</script>
@endsection

@section('scripts')
    @vite('resources/js/outfit-builder.js')
@endsection
