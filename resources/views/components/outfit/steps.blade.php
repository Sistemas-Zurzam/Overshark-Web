{{-- Pasos del probador. outfit-builder.js actualiza data-step-state según el estado real (foto, modelo). --}}
@props(['aiAvailable' => false])

<ol class="mx-auto mt-6 flex max-w-3xl flex-wrap items-center justify-center gap-x-3 gap-y-3 sm:gap-x-4" aria-label="Pasos del probador">
    @foreach ([
        ['number' => 1, 'label' => 'Toma tu foto'],
        ['number' => 2, 'label' => 'Genera tu modelo', 'note' => $aiAvailable ? null : 'En tu dispositivo'],
        ['number' => 3, 'label' => 'Arma tu outfit'],
    ] as $step)
        @unless ($loop->first)
            <li class="hidden h-px w-10 bg-electric/70 sm:block lg:w-16" aria-hidden="true"></li>
        @endunless
        <li class="os-step flex items-center gap-2.5" data-step="{{ $step['number'] }}" data-step-state="pending">
            <span class="os-step-badge grid h-9 w-9 shrink-0 place-items-center rounded-full border-2 text-sm font-black sm:h-10 sm:w-10">
                <svg class="os-step-check h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                <span class="os-step-number">{{ $step['number'] }}</span>
            </span>
            <span class="text-xs font-semibold uppercase tracking-wide sm:text-sm">
                <span class="os-step-label">{{ $step['label'] }}</span>
                @if ($step['note'] ?? null)
                    <span class="block text-[10px] font-medium normal-case tracking-normal text-haze">{{ $step['note'] }}</span>
                @endif
                <span class="sr-only" data-step-status>pendiente</span>
            </span>
        </li>
    @endforeach
</ol>
