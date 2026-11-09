@props(['name', 'label', 'placeholder' => '', 'type' => 'text', 'rows' => null, 'attrs' => []])

{{-- Campo del Libro de Reclamaciones (Figma "Entrada · …"): etiqueta, entrada gris y mensaje de error en rojo. --}}
@php
    $has = $errors->has($name);
    $box = 'w-full border bg-[#f5f6f8] p-[14px] text-[14px] leading-[1.55] text-[#252932] outline-none placeholder:text-[#252932]/70 focus:border-[#071226] '
        .($has ? 'border-[#b42318]' : 'border-[#d7dae0]');
@endphp

<div class="flex flex-col gap-[6px]">
    <label for="claim-{{ $name }}" class="text-[13px] font-extrabold leading-[1.55]">{{ $label }}</label>
    @if ($rows)
        <textarea id="claim-{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}" @if ($has) aria-invalid="true" @endif class="{{ $box }} resize-y">{{ old($name) }}</textarea>
    @else
        <input id="claim-{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name) }}" placeholder="{{ $placeholder }}" @if ($has) aria-invalid="true" @endif {{ $attributes->merge($attrs)->class($box) }}>
    @endif
    @error($name)
        <p class="text-[12px] leading-[1.55] text-[#b42318]">{{ $message }}</p>
    @enderror
</div>
