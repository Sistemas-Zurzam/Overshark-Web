{{--
    Campos del combo (alta en index y edición en edit). Variables: $combo (Combo|null), $productRows,
    $productOptions, $brands, $modalities.
    "Prenda": las filas con la misma prenda son alternativas y el cliente elige una (p. ej. Prenda "Polo"
    = CLASICO o SLIM FIT). Sin prenda, cada fila es un producto fijo del combo.
--}}
@php
    $input = 'w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 h-10 text-sm text-admin-text outline-none';
    $forceOutfit = $forceOutfit ?? false;
    $isOutfit = $forceOutfit || old('outfit', ($combo?->modality ?? '') === 'Outfit');
    $rows = old('products', $productRows ?: [['producto_id' => '', 'grupo' => '', 'cantidad' => 1]]);
@endphp

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <label class="block">
        <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Marca</span>
        <select name="brand_id" required class="{{ $input }}">
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected((int) old('brand_id', $combo?->brand_id ?? $brands->first()?->id) === $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Modalidad</span>
        <input type="text" name="modality" list="combo-modalities" value="{{ old('modality', ($combo?->modality === 'Outfit' ? '' : $combo?->modality) ?? 'Promoción') }}" class="{{ $input }}">
        <datalist id="combo-modalities">
            @foreach ($modalities as $modality)
                <option value="{{ $modality }}"></option>
            @endforeach
        </datalist>
    </label>
    <label class="block md:col-span-2">
        <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Nombre del combo</span>
        <input type="text" name="name" value="{{ old('name', $combo?->name) }}" required class="{{ $input }}" placeholder="Ej. Combito Wafflera">
    </label>
</div>

<div class="grid gap-4 md:grid-cols-3">
    <label class="block">
        <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Precio del combo</span>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-bold text-admin-text-muted">S/</span>
            <input type="number" name="price" value="{{ old('price', $combo?->price) }}" min="0" step="0.01" inputmode="decimal" class="{{ $input }} pl-9.5">
        </div>
    </label>
    <label class="block">
        <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Precio regular <span class="font-medium normal-case text-admin-text-muted">(tachado, opcional)</span></span>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-bold text-admin-text-muted">S/</span>
            <input type="number" name="compare_at_price" value="{{ old('compare_at_price', $combo?->compare_at_price) }}" min="0" step="0.01" inputmode="decimal" class="{{ $input }} pl-9.5">
        </div>
        @error('compare_at_price') <span class="mt-1 block text-xs font-semibold text-admin-danger">{{ $message }}</span> @enderror
    </label>
    <label class="block">
        <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Texto corto <span class="font-medium normal-case text-admin-text-muted">(en la tarjeta)</span></span>
        <input type="text" name="notes" value="{{ old('notes', $combo?->notes) }}" maxlength="255" class="{{ $input }}" placeholder="Ej. Polo + Pantalón">
    </label>
</div>

@if ($forceOutfit)
    <input type="hidden" name="outfit" value="1">
    <p class="rounded-sm border border-admin-accent bg-admin-accent-soft p-4 text-sm text-admin-text">
        Usa la columna <b>Prenda</b> para ofrecer alternativas: por ejemplo, tres filas con Prenda «Polo» (Clásico, Slim Fit y Waffle) y una con Prenda «Pantalón». El cliente elige una opción de cada prenda.
    </p>
@else
<label class="flex items-start gap-3 rounded-sm border border-admin-accent bg-admin-accent-soft p-4 text-sm text-admin-text">
    <input type="hidden" name="outfit" value="0">
    <input type="checkbox" name="outfit" value="1" @checked($isOutfit) class="mt-0.5 size-4 rounded border-admin-border-strong text-admin-accent">
    <span>
        <b class="block text-admin-text">Mostrar en «Arma tu outfit»</b>
        El cliente arma el combo prenda por prenda en esa página. Usa la columna <b>Prenda</b> para ofrecer alternativas: por ejemplo, tres filas con Prenda «Polo» (Clásico, Slim Fit y Waffle) y una con Prenda «Pantalón».
    </span>
</label>
@endif

<fieldset class="rounded-sm border border-admin-border bg-admin-subtle p-4">
    <legend class="px-2 text-sm font-black text-admin-text">Productos y cantidades</legend>
    <div class="mb-2 hidden gap-3 px-1 text-xs font-bold uppercase tracking-wide text-admin-text-muted sm:grid sm:grid-cols-[1fr_170px_110px_auto]">
        <span>Producto</span><span>Prenda <span class="normal-case tracking-normal">(opcional)</span></span><span>Cantidad</span><span class="w-17.5"></span>
    </div>
    <div data-combo-product-rows class="space-y-3">
        @foreach ($rows as $index => $row)
            <div data-combo-product-row class="grid gap-3 sm:grid-cols-[1fr_170px_110px_auto]">
                <label class="block">
                    <span class="sr-only">Producto {{ $index + 1 }}</span>
                    <select name="products[{{ $index }}][producto_id]" class="{{ $input }} bg-admin-surface">
                        <option value="">Selecciona un producto</option>
                        @foreach ($productOptions as $option)
                            <option value="{{ $option->id }}" @selected((string) ($row['producto_id'] ?? '') === (string) $option->id)>
                                {{ $option->name }}{{ $option->empresa_nombre ? ' · '.$option->empresa_nombre : '' }}{{ $option->visibles > 0 ? '' : ' · sin stock/precio' }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="sr-only">Prenda {{ $index + 1 }}</span>
                    <input type="text" name="products[{{ $index }}][grupo]" value="{{ $row['grupo'] ?? '' }}" maxlength="40" list="combo-garments" placeholder="Ej. Polo" class="{{ $input }} bg-admin-surface">
                </label>
                <label class="block">
                    <span class="sr-only">Cantidad {{ $index + 1 }}</span>
                    <input type="number" name="products[{{ $index }}][cantidad]" value="{{ $row['cantidad'] ?? 1 }}" min="1" max="99" class="{{ $input }} bg-admin-surface">
                </label>
                <button type="button" data-combo-product-remove class="border border-admin-border-strong px-4 text-sm font-bold text-admin-text-secondary" aria-label="Quitar producto">Quitar</button>
            </div>
        @endforeach
    </div>
    <datalist id="combo-garments">
        @foreach (['Polo', 'Polera', 'Camisa', 'Pantalón', 'Jean', 'Casaca', 'Baby tee', 'Top'] as $garment)
            <option value="{{ $garment }}"></option>
        @endforeach
    </datalist>
    <button type="button" data-combo-product-add class="mt-4 border border-admin-accent bg-admin-surface px-4 py-2.5 text-sm font-bold text-admin-accent">+ Agregar producto</button>
    <p class="mt-3 text-xs leading-5 text-admin-text-secondary">Los productos «sin stock/precio» se pueden agregar, pero en «Arma tu outfit» el combo saldrá como «Muy pronto» mientras alguna prenda no tenga nada disponible.</p>
</fieldset>

<div class="grid gap-4 md:grid-cols-2">
    <label class="block">
        <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">URL de destino <span class="font-medium normal-case text-admin-text-muted">(opcional)</span></span>
        <input type="text" name="url" value="{{ old('url', $combo && $combo->url !== '/combos/'.$combo->id ? $combo->url : '') }}" class="{{ $input }}" placeholder="Vacío = página del combo">
    </label>
    <label class="block">
        <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Imagen {{ $combo ? '(sube una nueva para reemplazarla)' : 'estándar o personalizada' }}</span>
        <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" class="block w-full rounded-sm border border-admin-border-strong bg-admin-surface p-2 text-sm text-admin-text file:mr-3 file:border-0 file:bg-admin-navy file:px-3 file:py-2 file:text-xs file:font-extrabold file:uppercase file:text-white">
        <span class="mt-1 block text-xs text-admin-text-muted">PNG, JPG, JPEG o WEBP; máximo 8 MB.</span>
    </label>
</div>

<div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:gap-6">
    <label class="flex items-start gap-2.5 text-sm font-bold text-admin-text">
        <input type="hidden" name="is_draft" value="0">
        <input type="checkbox" name="is_draft" value="1" @checked(old('is_draft', $combo?->is_draft ?? false)) class="mt-0.5 size-4 rounded border-admin-border-strong text-admin-accent" data-combo-draft-toggle>
        <span>Guardar como borrador <span class="font-medium text-admin-text-muted">(no se muestra en la tienda)</span></span>
    </label>
    <label class="flex items-start gap-2.5 text-sm font-bold text-admin-text" data-combo-status-field>
        <input type="hidden" name="status" value="0">
        <input type="checkbox" name="status" value="1" @checked(old('status', $combo?->status ?? true)) class="mt-0.5 size-4 rounded border-admin-border-strong text-admin-accent">
        <span>Activo (se muestra en la tienda)</span>
    </label>
</div>
