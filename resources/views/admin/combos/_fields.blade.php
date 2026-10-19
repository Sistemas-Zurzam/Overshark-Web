{{--
    Campos del combo (alta en index y edición en edit). Variables: $combo (Combo|null), $productRows,
    $productOptions, $brands, $modalities.
    "Prenda": las filas con la misma prenda son alternativas y el cliente elige una (p. ej. Prenda "Polo"
    = CLASICO o SLIM FIT). Sin prenda, cada fila es un producto fijo del combo.
--}}
@php
    $input = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100';
    $forceOutfit = $forceOutfit ?? false;
    $isOutfit = $forceOutfit || old('outfit', ($combo?->modality ?? '') === 'Outfit');
    $rows = old('products', $productRows ?: [['producto_id' => '', 'grupo' => '', 'cantidad' => 1]]);
@endphp

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <label class="block">
        <span class="mb-2 block text-sm font-bold text-slate-700">Marca</span>
        <select name="brand" class="{{ $input }}">
            <option value="">Selecciona una marca</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand }}" @selected(mb_strtoupper((string) old('brand', $combo?->brand ?? 'OVERSHARK')) === mb_strtoupper($brand))>{{ $brand }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="mb-2 block text-sm font-bold text-slate-700">Modalidad</span>
        <input type="text" name="modality" list="combo-modalities" value="{{ old('modality', ($combo?->modality === 'Outfit' ? '' : $combo?->modality) ?? 'Promoción') }}" class="{{ $input }}">
        <datalist id="combo-modalities">
            @foreach ($modalities as $modality)
                <option value="{{ $modality }}"></option>
            @endforeach
        </datalist>
    </label>
    <label class="block md:col-span-2">
        <span class="mb-2 block text-sm font-bold text-slate-700">Nombre del combo</span>
        <input type="text" name="name" value="{{ old('name', $combo?->name) }}" required class="{{ $input }}" placeholder="Ej. Combito Wafflera">
    </label>
</div>

<div class="grid gap-4 md:grid-cols-3">
    <label class="block">
        <span class="mb-2 block text-sm font-bold text-slate-700">Precio total</span>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-sm font-bold text-slate-400">S/</span>
            <input type="number" name="price" value="{{ old('price', $combo?->price) }}" min="0" step="0.01" inputmode="decimal" class="{{ $input }} pl-11">
        </div>
    </label>
    <label class="block md:col-span-2">
        <span class="mb-2 block text-sm font-bold text-slate-700">Texto corto <span class="font-normal text-slate-400">(se muestra en la tarjeta del combo)</span></span>
        <input type="text" name="notes" value="{{ old('notes', $combo?->notes) }}" maxlength="255" class="{{ $input }}" placeholder="Ej. Polo + Pantalón">
    </label>
</div>

@if ($forceOutfit)
    <input type="hidden" name="outfit" value="1">
    <p class="rounded-2xl border border-cyan-200 bg-cyan-50/60 p-4 text-sm text-slate-700">
        Usa la columna <b>Prenda</b> para ofrecer alternativas: por ejemplo, tres filas con Prenda «Polo» (Clásico, Slim Fit y Waffle) y una con Prenda «Pantalón». El cliente elige una opción de cada prenda.
    </p>
@else
<label class="flex items-start gap-3 rounded-2xl border border-cyan-200 bg-cyan-50/60 p-4 text-sm text-slate-700">
    <input type="hidden" name="outfit" value="0">
    <input type="checkbox" name="outfit" value="1" @checked($isOutfit) class="mt-0.5 h-5 w-5 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
    <span>
        <b class="block text-slate-950">Mostrar en «Arma tu outfit»</b>
        El cliente arma el combo prenda por prenda en esa página. Usa la columna <b>Prenda</b> para ofrecer alternativas: por ejemplo, tres filas con Prenda «Polo» (Clásico, Slim Fit y Waffle) y una con Prenda «Pantalón».
    </span>
</label>
@endif

<fieldset class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
    <legend class="px-2 text-sm font-black text-slate-950">Productos y cantidades</legend>
    <div class="mb-2 hidden gap-3 px-1 text-xs font-bold uppercase tracking-wide text-slate-400 sm:grid sm:grid-cols-[1fr_170px_110px_auto]">
        <span>Producto</span><span>Prenda <span class="normal-case tracking-normal">(opcional)</span></span><span>Cantidad</span><span class="w-[70px]"></span>
    </div>
    <div data-combo-product-rows class="space-y-3">
        @foreach ($rows as $index => $row)
            <div data-combo-product-row class="grid gap-3 sm:grid-cols-[1fr_170px_110px_auto]">
                <label class="block">
                    <span class="sr-only">Producto {{ $index + 1 }}</span>
                    <select name="products[{{ $index }}][producto_id]" class="{{ $input }} bg-white">
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
                    <input type="text" name="products[{{ $index }}][grupo]" value="{{ $row['grupo'] ?? '' }}" maxlength="40" list="combo-garments" placeholder="Ej. Polo" class="{{ $input }} bg-white">
                </label>
                <label class="block">
                    <span class="sr-only">Cantidad {{ $index + 1 }}</span>
                    <input type="number" name="products[{{ $index }}][cantidad]" value="{{ $row['cantidad'] ?? 1 }}" min="1" max="99" class="{{ $input }} bg-white">
                </label>
                <button type="button" data-combo-product-remove class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-500 transition hover:border-red-300 hover:text-red-600" aria-label="Quitar producto">Quitar</button>
            </div>
        @endforeach
    </div>
    <datalist id="combo-garments">
        @foreach (['Polo', 'Polera', 'Camisa', 'Pantalón', 'Jean', 'Casaca', 'Baby tee', 'Top'] as $garment)
            <option value="{{ $garment }}"></option>
        @endforeach
    </datalist>
    <button type="button" data-combo-product-add class="mt-4 rounded-xl border border-cyan-200 bg-white px-4 py-2.5 text-sm font-bold text-cyan-700 transition hover:bg-cyan-50">+ Agregar producto</button>
    <p class="mt-3 text-xs leading-5 text-slate-500">Los productos «sin stock/precio» se pueden agregar, pero en «Arma tu outfit» el combo saldrá como «Muy pronto» mientras alguna prenda no tenga nada disponible.</p>
</fieldset>

<div class="grid gap-4 md:grid-cols-2">
    <label class="block">
        <span class="mb-2 block text-sm font-bold text-slate-700">URL de destino <span class="font-normal text-slate-400">(opcional)</span></span>
        <input type="text" name="url" value="{{ old('url', $combo && $combo->url !== '/combos/'.$combo->id ? $combo->url : '') }}" class="{{ $input }}" placeholder="Vacío = página del combo">
    </label>
    <label class="block">
        <span class="mb-2 block text-sm font-bold text-slate-700">Imagen {{ $combo ? '(sube una nueva para reemplazarla)' : 'estándar o personalizada' }}</span>
        <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
        <span class="mt-2 block text-xs text-slate-400">PNG, JPG, JPEG o WEBP; máximo 8 MB.</span>
    </label>
</div>

<label class="flex items-start gap-3 text-sm font-bold text-slate-700">
    <input type="hidden" name="status" value="0">
    <input type="checkbox" name="status" value="1" @checked(old('status', $combo?->status ?? true)) class="mt-0.5 h-5 w-5 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
    <span>Activo (se muestra en la tienda)</span>
</label>
