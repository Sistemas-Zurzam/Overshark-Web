{{--
    Banner del probador virtual: lleva a /arma-tu-outfit. Si la generación con IA no está configurada, lo indica.
    Si existe public/images/home/probador-virtual.webp se usa como ilustración; si no, se dibuja la versión SVG.
--}}
@props(['aiAvailable' => false])

@php
    $photo = is_file(public_path('images/home/probador-virtual.webp')) ? 'images/home/probador-virtual.webp' : null;
    $photoSmall = $photo && is_file(public_path('images/home/probador-virtual-1200.webp')) ? 'images/home/probador-virtual-1200.webp' : null;
@endphp

<section id="probador-virtual" aria-labelledby="probador-virtual-title" {{ $attributes->merge(['class' => 'os-fitting relative overflow-hidden rounded-2xl']) }}>
    @if ($photo)
        {{-- Hasta 1279px: la figura y las prendas encabezan la tarjeta. --}}
        <img
            src="{{ asset($photoSmall ?? $photo) }}"
            alt=""
            width="1200"
            height="400"
            loading="lazy"
            decoding="async"
            class="block aspect-[3/2] w-full object-cover object-right [mask-image:linear-gradient(180deg,#000_75%,transparent)] sm:aspect-[2/1] lg:aspect-[3/1] xl:hidden"
        >
        {{-- Desde 1280px: ocupa la altura de la tarjeta, alineada a la derecha y fundida bajo el texto. --}}
        <img
            src="{{ asset($photo) }}"
            @if ($photoSmall) srcset="{{ asset($photoSmall) }} 1200w, {{ asset($photo) }} 2172w" sizes="(min-width: 1280px) 760px, 100vw" @endif
            alt=""
            width="2172"
            height="724"
            decoding="async"
            class="pointer-events-none absolute inset-y-0 right-0 hidden h-full w-auto max-w-none [mask-image:linear-gradient(90deg,transparent,#000_30%)] xl:block"
        >
    @else
        <div class="pointer-events-none absolute inset-0 os-fitting-waves" aria-hidden="true"></div>
    @endif

    <div @class([
        'relative grid items-center gap-6 p-5 sm:p-7 md:grid-cols-[auto_minmax(0,1fr)] lg:gap-10 lg:px-10 lg:py-7',
        'lg:grid-cols-[auto_minmax(0,1fr)_auto]' => ! $photo,
        'xl:min-h-[250px] xl:grid-cols-[auto_minmax(0,600px)_1fr]' => $photo,
    ])>
        <svg class="h-20 w-20 shrink-0 text-electric-soft sm:h-24 sm:w-24" viewBox="0 0 96 96" fill="none" aria-hidden="true" focusable="false">
            <path d="M6 26V12a6 6 0 0 1 6-6h14M70 6h14a6 6 0 0 1 6 6v14M90 70v14a6 6 0 0 1-6 6H70M26 90H12a6 6 0 0 1-6-6V70" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" class="os-glow-stroke"/>
            <path d="M30 40a4 4 0 0 1 4-4h6l3-5h10l3 5h6a4 4 0 0 1 4 4v20a4 4 0 0 1-4 4H34a4 4 0 0 1-4-4Z" stroke="#fff" stroke-width="3" stroke-linejoin="round"/>
            <circle cx="48" cy="49" r="7.5" stroke="#fff" stroke-width="3"/>
            <path d="M68 20l2 5 5 2-5 2-2 5-2-5-5-2 5-2Z" fill="#fff"/>
        </svg>

        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.32em] text-mist sm:text-sm">Probador virtual</p>
            <h2 id="probador-virtual-title" class="mt-2 font-heading text-[26px] font-black uppercase leading-[1.05] tracking-tight sm:text-4xl xl:text-[44px]">
                <span class="text-electric">Arma tu outfit</span> con IA
            </h2>
            <p class="mt-2 max-w-xl text-sm font-medium text-white/85 sm:text-base">Sube una foto, crea tu modelo y pruébate toda la colección.</p>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <a href="{{ route('web.outfit.show') }}" class="os-btn-solid os-focus h-12 px-6 sm:px-14" @unless ($aiAvailable) aria-describedby="probador-virtual-status" @endunless>
                    Crear mi modelo
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg>
                </a>
                @unless ($aiAvailable)
                    <p id="probador-virtual-status" class="max-w-xs text-xs font-medium leading-5 text-mist">
                        Arma tu outfit con el catálogo. La generación del modelo con IA aún no está activa.
                    </p>
                @endunless
            </div>
        </div>

        @unless ($photo)
        <div class="hidden items-center gap-0 lg:flex" aria-hidden="true">
            <svg class="h-[184px] w-[300px]" viewBox="0 0 300 184" fill="none" focusable="false">
                <defs>
                    <radialGradient id="fitting-body-glow" cx="120" cy="110" r="120" gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-color="#2f86ff" stop-opacity=".45"/>
                        <stop offset="1" stop-color="#2f86ff" stop-opacity="0"/>
                    </radialGradient>
                    <clipPath id="fitting-body-clip">
                        <path d="M120 22c17 0 28 13 28 31 0 13-6 24-13 29v12c26 6 52 16 64 30 8 9 11 24 12 60H29c1-36 4-51 12-60 12-14 38-24 64-30V82c-7-5-13-16-13-29 0-18 11-31 28-31Z"/>
                    </clipPath>
                </defs>
                <circle cx="120" cy="110" r="120" fill="url(#fitting-body-glow)"/>
                <path d="M120 22c17 0 28 13 28 31 0 13-6 24-13 29v12c26 6 52 16 64 30 8 9 11 24 12 60H29c1-36 4-51 12-60 12-14 38-24 64-30V82c-7-5-13-16-13-29 0-18 11-31 28-31Z" fill="#0d3a8a" fill-opacity=".35" stroke="#7cc0ff" stroke-width="1.6" class="os-glow-stroke"/>
                <g clip-path="url(#fitting-body-clip)" stroke="#7cc0ff" stroke-opacity=".45" stroke-width=".8">
                    @foreach (range(30, 180, 10) as $y)
                        <path d="M20 {{ $y }}q100 {{ 6 + ($y % 30) / 5 }} 200 0"/>
                    @endforeach
                    @foreach (range(36, 204, 14) as $x)
                        <path d="M{{ $x }} 0q{{ (120 - $x) / 6 }} 92 0 184"/>
                    @endforeach
                </g>
                <path d="M86 12V4h8M146 4h8v8M154 92v8h-8M94 100h-8v-8" stroke="#fff" stroke-opacity=".75" stroke-width="2" stroke-linecap="round"/>
                <path d="M222 92h30M252 28v128M252 28h48M252 92h48M252 156h48" stroke="#7cc0ff" stroke-opacity=".8" stroke-width="1.5"/>
                <circle cx="222" cy="92" r="3" fill="#7cc0ff"/>
            </svg>
            <ul class="flex flex-col gap-2">
                @foreach ([['polo', '#6f8f6a'], ['camisa', '#b9c6d8'], ['pantalon', '#c8b08a']] as [$garment, $color])
                    <li class="grid h-14 w-[60px] place-items-center rounded-md border border-electric-soft/60 bg-ink/70 shadow-[0_0_14px_rgb(47_134_255/35%)]">
                        <x-storefront.garment :type="$garment" :color="$color" class="h-11 w-11"/>
                    </li>
                @endforeach
            </ul>
        </div>
        @endunless
    </div>
</section>
