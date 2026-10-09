@props(['title', 'titleId'])

<section aria-labelledby="{{ $titleId }}" {{ $attributes->merge(['class' => 'os-panel flex min-w-0 flex-col p-5 sm:p-6']) }}>
    <h2 id="{{ $titleId }}" class="font-heading text-2xl font-black uppercase tracking-tight sm:text-[28px]">{{ $title }}</h2>
    {{ $slot }}
</section>
