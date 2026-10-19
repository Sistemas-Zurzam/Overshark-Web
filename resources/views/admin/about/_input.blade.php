{{-- Campo de texto del editor de Nosotros. Variables: $name (notación con puntos), $label, $value, $area (bool), $hint. --}}
@php
    $field = collect(explode('.', $name))->map(fn ($part, $i) => $i === 0 ? $part : "[{$part}]")->implode('');
    $id = 'f-'.str_replace('.', '-', $name);
@endphp
<label for="{{ $id }}" class="block">
    <span class="mb-1.5 block text-sm font-bold text-slate-700">{{ $label }}@isset($hint)<span class="ml-1 font-normal text-slate-400">{{ $hint }}</span>@endisset</span>
    @if ($area ?? false)
        <textarea id="{{ $id }}" name="{{ $field }}" rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $id }}" type="text" name="{{ $field }}" value="{{ old($name, $value) }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
    @endif
    @error($name)<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
</label>
