@php
    $brand = \App\Models\BrandSetting::current();
    $bubbles = [
        ['x' => '5%', 'y' => '76%', 'size' => '18px', 'duration' => '7s', 'delay' => '0s'],
        ['x' => '7%', 'y' => '87%', 'size' => '10px', 'duration' => '9s', 'delay' => '-2s'],
        ['x' => '9%', 'y' => '70%', 'size' => '7px', 'duration' => '6s', 'delay' => '-4s'],
        ['x' => '91%', 'y' => '32%', 'size' => '8px', 'duration' => '8s', 'delay' => '-1s'],
        ['x' => '94%', 'y' => '40%', 'size' => '13px', 'duration' => '7s', 'delay' => '-3s'],
        ['x' => '90%', 'y' => '47%', 'size' => '8px', 'duration' => '10s', 'delay' => '-5s'],
        ['x' => '92%', 'y' => '56%', 'size' => '22px', 'duration' => '9s', 'delay' => '-2s'],
        ['x' => '88%', 'y' => '64%', 'size' => '9px', 'duration' => '6s', 'delay' => '-1s'],
        ['x' => '92%', 'y' => '72%', 'size' => '15px', 'duration' => '8s', 'delay' => '-6s'],
        ['x' => '89%', 'y' => '82%', 'size' => '26px', 'duration' => '11s', 'delay' => '-3s'],
    ];
    $collections = [
        [
            'key' => 'hombre',
            'name' => 'Hombre',
            'items' => 'Polos · Buzos · Básicos',
            'image' => 'images/colecciones/hombre.jpg',
            'alt' => 'Modelo con polo azul marino y buzo gris de la colección hombre',
        ],
        [
            'key' => 'mujer',
            'name' => 'Mujer',
            'items' => 'Baby tees · Tops · Buzos',
            'image' => 'images/colecciones/mujer.jpg',
            'alt' => 'Modelo con baby tee y buzo rosado de la colección mujer',
        ],
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
    <meta name="theme-color" content="#081a36">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Playfair+Display:ital,wght@1,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="explore-ocean antialiased">
    <div class="explore-ocean__rays" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        @foreach ($bubbles as $bubble)
            <span class="explore-bubble" style="--x: {{ $bubble['x'] }}; --y: {{ $bubble['y'] }}; --size: {{ $bubble['size'] }}; --duration: {{ $bubble['duration'] }}; --delay: {{ $bubble['delay'] }};"></span>
        @endforeach
    </div>

    <main class="relative z-10 mx-auto flex min-h-dvh max-w-5xl flex-col items-center px-4 pb-14 pt-8 sm:px-6 sm:pt-10">
        <p class="text-sm font-black uppercase tracking-[0.5em] text-white drop-shadow sm:text-base">Overshark</p>

        <h1 class="mt-8 text-center font-condensed text-[clamp(2.9rem,8vw,5.25rem)] uppercase leading-[0.95] tracking-wide drop-shadow-[0_4px_18px_rgb(4_16_40_/_55%)] sm:mt-12">
            ¿Qué quieres explorar?
        </h1>
        <p class="mt-3 text-center text-sm font-medium text-white/90 drop-shadow sm:text-base">Básicos para todos los días: baby tees, tops, polos y buzos.</p>

        <div class="mt-9 grid w-full max-w-[880px] gap-6 sm:mt-12 sm:grid-cols-2 sm:gap-8">
            @foreach ($collections as $item)
                <a href="{{ route('web.home', ['linea' => $item['key']]) }}" class="explore-card group" data-collection="{{ $item['key'] }}" aria-label="Explorar colección {{ $item['name'] }}">
                    <div class="aspect-[16/11] overflow-hidden sm:aspect-[414/320]">
                        <img src="{{ asset($item['image']) }}" alt="{{ $item['alt'] }}" class="h-full w-full object-cover object-top transition duration-700 group-hover:scale-105">
                    </div>
                    <div class="flex items-center justify-between gap-4 px-6 py-5">
                        <div>
                            @if ($item['key'] === 'mujer')
                                <p class="font-display text-5xl font-bold italic leading-none text-[#8c1d52] sm:text-[3.4rem]">{{ $item['name'] }}</p>
                                <p class="mt-2 text-[11px] font-bold uppercase tracking-[0.12em] text-[#a8336d]">{{ $item['items'] }}</p>
                            @else
                                <p class="font-condensed text-[3.4rem] uppercase leading-none tracking-wide text-white sm:text-6xl">{{ $item['name'] }}</p>
                                <p class="mt-1 text-[11px] font-bold uppercase tracking-[0.12em] text-[#7cc4ff]">{{ $item['items'] }}</p>
                            @endif
                        </div>
                        <span class="explore-card__arrow" aria-hidden="true">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </main>
</body>
</html>
