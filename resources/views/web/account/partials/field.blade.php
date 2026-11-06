{{-- Campo de formulario de cuenta con su error debajo. $name, $label; opcionales: $type, $value, $autocomplete, $required, $hint, $inputmode, $maxlength, $placeholder. --}}
@php
    $type = $type ?? 'text';
    $id = 'f-'.($idSuffix ?? '').$name;
    $isPassword = $type === 'password';
@endphp
<div class="os-ck-field">
    <label for="{{ $id }}" class="font-bold">{{ $label }}</label>
    <div @class(['relative' => $isPassword])>
        <input
            id="{{ $id }}"
            type="{{ $type }}"
            name="{{ $name }}"
            @unless ($isPassword) value="{{ old($name, $value ?? '') }}" @endunless
            @if ($required ?? true) required @endif
            @isset($autocomplete) autocomplete="{{ $autocomplete }}" @endisset
            @isset($inputmode) inputmode="{{ $inputmode }}" @endisset
            @isset($maxlength) maxlength="{{ $maxlength }}" @endisset
            @isset($placeholder) placeholder="{{ $placeholder }}" @endisset
            @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
            @class(['os-ck-input', '!h-11 pr-20' => $isPassword, '!h-11' => ! $isPassword, '!border-[#a3261c]' => $errors->has($name)])
        >
        @if ($isPassword)
            <button type="button" data-toggle-password="#{{ $id }}" class="os-focus absolute right-2 top-1/2 -translate-y-1/2 px-2 py-1 text-[12px] font-bold text-[#2467cf]" aria-label="Mostrar u ocultar la contraseña">Mostrar</button>
        @endif
    </div>
    @isset($hint)<p class="text-[12px] text-[#65717c]">{{ $hint }}</p>@endisset
    @error($name)<p id="{{ $id }}-error" class="text-[12px] font-semibold text-[#a3261c]" role="alert">{{ $message }}</p>@enderror
</div>
