@php
    $brand = \App\Models\BrandSetting::current();
    $collections = [
        [
            'key' => 'hombre',
            'name' => 'Hombre',
            'items' => 'Polos · Buzos · Básicos',
            'image' => 'images/explorar/hombre-modelos.png',
            'alt' => 'Modelos con polos de la colección hombre de Overshark',
            'color' => '#2F6FD0',
        ],
        [
            'key' => 'mujer',
            'name' => 'Mujer',
            'items' => 'Baby tees · Tops · Buzos',
            'image' => 'images/explorar/mujer-modelos.jpg',
            'alt' => 'Modelos con básicos de la colección mujer de Overshark',
            'color' => '#D63A78',
        ],
    ];
    $perks = [
        ['label' => 'Compras en vivo', 'color' => '#EF4444'],
        ['label' => 'Asesoría personalizada', 'color' => '#5E9BEA'],
        ['label' => 'Outfit con IA', 'color' => '#2DD4BF'],
        ['label' => 'Envíos a todo el Perú', 'color' => '#FFFFFF'],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Overshark: básicos para todos los días. Elige la colección hombre o mujer.">
    <title>Overshark | ¿Qué quieres explorar?</title>
    @if ($brand->iconUrl())
        <link rel="icon" href="{{ $brand->iconUrl() }}">
    @endif
    <meta name="theme-color" content="#03111E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;800&family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="{{ asset('images/explorar/mar.jpg') }}">
    @vite(['resources/css/app.css', 'resources/css/explore.css', 'resources/js/explore.js'])
</head>
<body class="ex antialiased">
    {{-- Cortina de transición al elegir una colección (la anima explore.js) --}}
    <div class="ex-wipe" aria-hidden="true">
        <div class="ex-wipe__layer ex-wipe__layer--a"></div>
        <div class="ex-wipe__layer ex-wipe__layer--b"><span class="ex-wipe__logo">OVERSHARK</span></div>
    </div>

    <main class="ex-view">
        <div class="ex-sea" aria-hidden="true">
            <div class="ex-sea__img" style="background-image: url('{{ asset('images/explorar/mar.jpg') }}')"></div>
            <div class="ex-sea__rays"><i></i><i></i><i></i><i></i><i></i></div>
            <div class="ex-sea__glow ex-sea__glow--blue"></div>
            <div class="ex-sea__glow ex-sea__glow--pink"></div>
            <canvas class="ex-bubbles"></canvas>
            <div class="ex-sea__vignette"></div>
        </div>

        <div class="ex-selector">
            <a class="ex-brand" href="{{ route('web.explore') }}" aria-label="Overshark, inicio">
                <img src="{{ $brand->logoUrl() ?? asset('images/explorar/logo-overshark.png') }}" alt="Overshark" width="176" height="30">
            </a>

            <header class="ex-head">
                <p class="ex-eyebrow"><span></span>Bienvenido a Overshark<span></span></p>
                <h1 class="ex-title">
                    <span class="ex-title__light js-split">¿Qué quieres</span>
                    <span class="ex-title__serif ex-line-mask"><span class="ex-line-inner">explorar <em>hoy?</em></span></span>
                </h1>
                <p class="ex-sub">Básicos para todos los días: baby tees, tops, polos y buzos. Elige una colección para empezar.</p>
            </header>

            <div class="ex-choices">
                @foreach ($collections as $item)
                    <a
                        href="{{ route('web.home', ['linea' => $item['key']]) }}"
                        class="ex-choice ex-choice--{{ $item['key'] }}"
                        data-collection="{{ $item['key'] }}"
                        data-color="{{ $item['color'] }}"
                        aria-label="Explorar colección {{ $item['name'] }}"
                    >
                        <div class="ex-choice__media">
                            <div class="ex-choice__sea" style="--sea: url('{{ asset('images/explorar/mar.jpg') }}')"></div>
                            <img
                                class="ex-choice__models {{ $item['key'] === 'mujer' ? 'ex-choice__models--photo' : '' }}"
                                src="{{ asset($item['image']) }}"
                                alt="{{ $item['alt'] }}"
                                loading="eager"
                            >
                            <div class="ex-choice__fade"></div>
                        </div>
                        <div class="ex-choice__label">
                            <div>
                                <h2 class="ex-choice__name">{{ $item['key'] === 'hombre' ? 'HOMBRE' : 'Mujer' }}</h2>
                                <p class="ex-choice__cats">{{ $item['items'] }}</p>
                            </div>
                            <span class="ex-pill">
                                Ver colección
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            <ul class="ex-perks" aria-label="En ambas colecciones">
                <li class="ex-perks__title">En ambas colecciones</li>
                @foreach ($perks as $perk)
                    <li><i style="--c: {{ $perk['color'] }}"></i>{{ $perk['label'] }}</li>
                @endforeach
            </ul>
        </div>
    </main>
</body>
</html>
