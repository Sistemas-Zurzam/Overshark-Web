@php
    $defaults = [
        'primary_color' => '#0078D7',
        'secondary_color' => '#111111',
        'accent_color' => '#E8F4FF',
        'background_color' => '#F1F2F4',
        'text_color' => '#111111',
    ];
@endphp
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
    @foreach (['primary_color' => 'Principal', 'secondary_color' => 'Secundario', 'accent_color' => 'Acento', 'background_color' => 'Fondo', 'text_color' => 'Texto'] as $field => $label)
        @php($value = old($field, $values?->{$field} ?? $defaults[$field]))
        <label class="block">
            <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">{{ $label }}</span>
            <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 py-2">
                <input type="color" name="{{ $field }}" value="{{ $value }}" class="h-9 w-10 cursor-pointer rounded-lg border-0 bg-transparent p-0">
                <span class="text-xs font-bold text-slate-500">{{ $value }}</span>
            </div>
        </label>
    @endforeach
</div>
