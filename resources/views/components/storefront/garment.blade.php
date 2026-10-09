@props([
    'type' => 'polo',
    'color' => '#6f8f6a',
])

@php
    $shadeId = 'garment-shade-'.\Illuminate\Support\Str::random(8);
    $paths = [
        'polo' => [
            'body' => 'M38 22 50 15q10 9 20 0l12 7 20 14-8 17-10-6v58H36V46l-10 6-8-17z',
            'details' => 'M50 15l10 15 10-15M60 30v16',
            'dots' => [[60, 36], [60, 42]],
        ],
        'camisa' => [
            'body' => 'M40 19 52 13l8 11 8-11 12 6 20 10 8 67-12 2-8-50v60H34V48l-8 50-12-2 8-67z',
            'details' => 'M52 13l4 15 4-4 4 4 4-15M60 24v82',
            'dots' => [[60, 40], [60, 56], [60, 72], [60, 88]],
        ],
        'media' => [
            'body' => 'M46 10h26v54l16 18c7 9 2 24-11 25-6 1-11-2-15-7L46 82z',
            'details' => 'M46 20h26M46 26h26',
            'dots' => [],
        ],
        'pantalon' => [
            'body' => 'M36 12h48l7 96H67l-7-62-7 62H29z',
            'details' => 'M36 20h48M60 20v26M42 20q2 12 10 14M78 20q-2 12-10 14',
            'dots' => [[60, 16]],
        ],
    ][$type] ?? null;
@endphp

@if ($paths)
    <svg viewBox="0 0 120 120" {{ $attributes->merge(['class' => 'block']) }} aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id="{{ $shadeId }}" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#fff" stop-opacity=".22"/>
                <stop offset=".55" stop-color="#fff" stop-opacity="0"/>
                <stop offset="1" stop-color="#000" stop-opacity=".35"/>
            </linearGradient>
        </defs>
        <path data-garment-fill d="{{ $paths['body'] }}" fill="{{ $color }}" stroke="#000" stroke-opacity=".35" stroke-width="1.2" stroke-linejoin="round"/>
        <path d="{{ $paths['body'] }}" fill="url(#{{ $shadeId }})"/>
        <path d="{{ $paths['details'] }}" fill="none" stroke="#000" stroke-opacity=".3" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
        @foreach ($paths['dots'] as [$cx, $cy])
            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="1.4" fill="#fff" fill-opacity=".55"/>
        @endforeach
    </svg>
@endif
