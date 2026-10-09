{{-- Fondo del hero. Si existe public/images/home/hero-ocean.webp (o .jpg) se usa esa foto; si no, una ilustración SVG equivalente. --}}
@php
    $photo = collect(['images/home/hero-ocean.webp', 'images/home/hero-ocean.jpg'])
        ->first(fn (string $path) => is_file(public_path($path)));

    mt_srand(1977);
    $particles = collect(range(1, 110))->map(fn () => [
        'x' => mt_rand(0, 1440),
        'y' => mt_rand(0, 560),
        'r' => mt_rand(5, 18) / 10,
        'o' => mt_rand(12, 55) / 100,
    ]);
    $fish = [
        [40, 70, .9], [96, 52, .7], [150, 92, .8], [70, 128, .6], [210, 40, .55], [18, 160, .5],
        [1180, 120, .8], [1240, 88, .65], [1300, 150, .9], [1360, 110, .6], [1215, 190, .5], [1390, 200, .7], [1120, 60, .45],
    ];
    mt_srand();
@endphp

<div {{ $attributes->merge(['class' => 'pointer-events-none absolute inset-0 overflow-hidden']) }} aria-hidden="true">
    @if ($photo)
        @php
            $smallPhoto = preg_replace('/(\.\w+)$/', '-1200$1', $photo);
        @endphp
        <img
            src="{{ asset($photo) }}"
            @if (is_file(public_path($smallPhoto))) srcset="{{ asset($smallPhoto) }} 1200w, {{ asset($photo) }} 2172w" sizes="100vw" @endif
            alt=""
            class="h-[300px] w-full object-cover object-[38.5%_30%] [mask-image:linear-gradient(180deg,#000_70%,transparent)] sm:h-[420px] lg:h-auto lg:object-top"
            fetchpriority="high"
            decoding="async"
        >
    @else
        <svg class="h-full w-full" viewBox="0 0 1440 560" preserveAspectRatio="xMidYMin slice" focusable="false">
            <defs>
                <linearGradient id="ocean-water" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#0b2d63"/>
                    <stop offset=".45" stop-color="#061a3b"/>
                    <stop offset="1" stop-color="#020713"/>
                </linearGradient>
                <radialGradient id="ocean-surface" cx="640" cy="-60" r="760" gradientUnits="userSpaceOnUse">
                    <stop offset="0" stop-color="#5d9cff" stop-opacity=".45"/>
                    <stop offset="1" stop-color="#5d9cff" stop-opacity="0"/>
                </radialGradient>
                <radialGradient id="ocean-skin" cx="550" cy="170" r="620" gradientUnits="userSpaceOnUse">
                    <stop offset="0" stop-color="#0d2c57"/>
                    <stop offset=".6" stop-color="#071a37"/>
                    <stop offset="1" stop-color="#041026" stop-opacity="0"/>
                </radialGradient>
                <radialGradient id="ocean-eye-glow">
                    <stop offset="0" stop-color="#7cc0ff" stop-opacity=".75"/>
                    <stop offset=".35" stop-color="#2f86ff" stop-opacity=".35"/>
                    <stop offset="1" stop-color="#1f6fff" stop-opacity="0"/>
                </radialGradient>
                <radialGradient id="ocean-iris">
                    <stop offset="0" stop-color="#f2fbff"/>
                    <stop offset=".45" stop-color="#7cc4ff"/>
                    <stop offset="1" stop-color="#1b5fd6"/>
                </radialGradient>
                <pattern id="ocean-scales" width="16" height="14" patternUnits="userSpaceOnUse">
                    <circle cx="3" cy="3" r="1.1" fill="#7fb2ff" fill-opacity=".2"/>
                    <circle cx="11" cy="10" r=".8" fill="#7fb2ff" fill-opacity=".14"/>
                </pattern>
                <filter id="ocean-soft" x="-10%" y="-10%" width="120%" height="120%">
                    <feGaussianBlur stdDeviation="10"/>
                </filter>
                <filter id="ocean-ray-blur">
                    <feGaussianBlur stdDeviation="14"/>
                </filter>
                <clipPath id="ocean-head-clip">
                    <path d="M-20 300C-10 110 200 18 550 18s550 112 560 272c10 130-160 230-560 250C150 520-30 430-20 300Z"/>
                </clipPath>
                <symbol id="ocean-fish" viewBox="0 0 40 14">
                    <path d="M0 7C8 1 22 1 30 7 22 13 8 13 0 7Zm30 0 9-6v12Z"/>
                </symbol>
            </defs>

            <rect width="1440" height="560" fill="url(#ocean-water)"/>
            <rect width="1440" height="560" fill="url(#ocean-surface)"/>

            <g filter="url(#ocean-ray-blur)" fill="#bcd8ff" fill-opacity=".07">
                <path d="M600-20h40L520 560H380Z"/>
                <path d="M660-20h30l90 580H650Z"/>
                <path d="M720-20h24l300 580H900Z"/>
                <path d="M560-20h30L180 560H60Z"/>
                <path d="M770-20h20l520 560h-150Z"/>
            </g>

            {{-- Criatura marina --}}
            <g>
                <path d="M-20 300C-10 110 200 18 550 18s550 112 560 272c10 130-160 230-560 250C150 520-30 430-20 300Z" fill="url(#ocean-skin)" filter="url(#ocean-soft)"/>
                <g clip-path="url(#ocean-head-clip)" opacity=".9">
                    <rect width="1440" height="560" fill="url(#ocean-scales)"/>
                    <path d="M130 92C210 22 360 22 410 77M960 97C880 27 730 27 680 82" fill="none" stroke="#3f7fd6" stroke-opacity=".22" stroke-width="10" filter="url(#ocean-soft)"/>
                    <path d="M420 330C480 380 620 380 690 330" fill="none" stroke="#000" stroke-opacity=".35" stroke-width="18" filter="url(#ocean-soft)"/>
                </g>
                @foreach ([[300, 112, -9], [792, 118, 9]] as [$ex, $ey, $tilt])
                    <g class="os-creature-eye" transform="translate({{ $ex }} {{ $ey }}) rotate({{ $tilt }})">
                        <circle r="92" fill="url(#ocean-eye-glow)"/>
                        <ellipse rx="44" ry="24" fill="#020a19" fill-opacity=".85"/>
                        <ellipse rx="22" ry="14" fill="url(#ocean-iris)"/>
                        <ellipse rx="3.6" ry="11" fill="#020b1c"/>
                        <circle cx="-8" cy="-5" r="2.6" fill="#fff" fill-opacity=".9"/>
                    </g>
                @endforeach
            </g>

            <g fill="#071631">
                @foreach ($fish as [$fx, $fy, $scale])
                    <use href="#ocean-fish" x="{{ $fx }}" y="{{ $fy }}" width="{{ 40 * $scale }}" height="{{ 14 * $scale }}"/>
                @endforeach
            </g>

            <g fill="#9cc8ff">
                @foreach ($particles as $particle)
                    <circle cx="{{ $particle['x'] }}" cy="{{ $particle['y'] }}" r="{{ $particle['r'] }}" fill-opacity="{{ $particle['o'] }}"/>
                @endforeach
            </g>

            <g fill="#020812" stroke="#5a96ff" stroke-opacity=".16" stroke-width="2">
                <path d="M-10 560V420l60-40 50 22 40-60 70 30 40-18 60 70 40 10 30 126Z"/>
                <path d="M1450 560V380l-50-30-60 44-40-20-70 70-30-10-60 60-20 66Z"/>
            </g>
        </svg>
    @endif
</div>
