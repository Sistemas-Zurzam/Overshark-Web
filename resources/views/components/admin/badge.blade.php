@props(['tone' => 'neutral', 'label'])
@php($tones = [
    'success' => ['bg' => 'bg-admin-success-soft', 'text' => 'text-admin-success', 'dot' => 'bg-admin-success'],
    'warning' => ['bg' => 'bg-admin-warning-soft', 'text' => 'text-admin-warning', 'dot' => 'bg-admin-warning'],
    'danger' => ['bg' => 'bg-admin-danger-soft', 'text' => 'text-admin-danger', 'dot' => 'bg-admin-danger'],
    'info' => ['bg' => 'bg-admin-accent-soft', 'text' => 'text-admin-accent', 'dot' => 'bg-admin-accent'],
    'neutral' => ['bg' => 'bg-admin-hover', 'text' => 'text-admin-text-secondary', 'dot' => 'bg-admin-text-secondary'],
])
@php($t = $tones[$tone] ?? $tones['neutral'])
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-sm px-2 py-1 {$t['bg']}"]) }}>
    <span class="size-1.5 shrink-0 rounded-full {{ $t['dot'] }}"></span>
    <span class="text-[11px] font-extrabold uppercase tracking-[0.5px] {{ $t['text'] }}">{{ $label }}</span>
</span>
