{{-- Formulario de una regla de "Te puede interesar". $rule = regla a editar (o null), $productNames, $brandNames. --}}
@php
    $field = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100';
    $type = old('trigger_type', $rule->trigger_type ?? 'producto');
    $chosen = collect(old('products', $rule->products ?? []));
@endphp
<form action="{{ $rule ? route('admin.sugerencias.update', $rule) : route('admin.sugerencias.store') }}" method="POST" data-suggestion-form class="grid gap-5 lg:grid-cols-2">
    @csrf
    @if ($rule) @method('PUT') @endif

    <label class="block lg:col-span-2">
        <span class="mb-2 block text-sm font-bold text-slate-700">Nombre de la regla (solo lo ves tú)</span>
        <input type="text" name="name" value="{{ old('name', $rule->name ?? '') }}" required class="{{ $field }}" placeholder="Ej. Con la Polera Neru, completar con Baby Tee">
        @error('name') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="mb-2 block text-sm font-bold text-slate-700">Se activa cuando agregan…</span>
        <select name="trigger_type" data-trigger-type class="{{ $field }}">
            <option value="producto" @selected($type === 'producto')>Una prenda concreta</option>
            <option value="marca" @selected($type === 'marca')>Cualquier prenda de una marca</option>
        </select>
    </label>

    <div>
        <label data-trigger-product class="block">
            <span class="mb-2 block text-sm font-bold text-slate-700">Prenda</span>
            <select name="trigger_product" class="{{ $field }}">
                <option value="">Elige una prenda</option>
                @foreach ($productNames as $name)
                    <option value="{{ $name }}" @selected(old('trigger_product', ($rule->trigger_type ?? '') === 'producto' ? $rule->trigger_value : '') === $name)>{{ $name }}</option>
                @endforeach
            </select>
            @error('trigger_product') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
        </label>
        <label data-trigger-brand class="block">
            <span class="mb-2 block text-sm font-bold text-slate-700">Marca</span>
            <select name="trigger_brand" class="{{ $field }}">
                <option value="">Elige una marca</option>
                @foreach ($brandNames as $brandName)
                    <option value="{{ $brandName }}" @selected(mb_strtoupper(old('trigger_brand', ($rule->trigger_type ?? '') === 'marca' ? $rule->trigger_value : '')) === mb_strtoupper($brandName))>{{ $brandName }}</option>
                @endforeach
            </select>
            @error('trigger_brand') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
        </label>
    </div>

    <div class="lg:col-span-2">
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
            <span class="text-sm font-bold text-slate-700">Prendas que se sugieren (hasta 12; se muestran las 4 primeras con stock)</span>
            <input type="search" data-product-filter placeholder="Buscar prenda…" class="w-56 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-cyan-500">
        </div>
        <div class="grid max-h-64 gap-1 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($productNames as $name)
                <label data-product-option data-name="{{ mb_strtolower($name) }}" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-white">
                    <input type="checkbox" name="products[]" value="{{ $name }}" @checked($chosen->contains($name)) class="h-4 w-4 rounded border-slate-300 text-cyan-600">
                    <span>{{ $name }}</span>
                </label>
            @endforeach
        </div>
        @error('products') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
    </div>

    <label class="block">
        <span class="mb-2 block text-sm font-bold text-slate-700">Título del modal (opcional)</span>
        <input type="text" name="title" value="{{ old('title', $rule->title ?? '') }}" maxlength="80" class="{{ $field }}" placeholder="Completa tu look">
    </label>

    <label class="block">
        <span class="mb-2 block text-sm font-bold text-slate-700">Prioridad (menor va primero)</span>
        <input type="number" name="sort" value="{{ old('sort', $rule->sort ?? 0) }}" min="0" max="9999" class="{{ $field }}">
    </label>

    <label class="block">
        <span class="mb-2 block text-sm font-bold text-slate-700">Desde (opcional)</span>
        <input type="date" name="starts_at" value="{{ old('starts_at', optional($rule?->starts_at)->format('Y-m-d')) }}" class="{{ $field }}">
    </label>

    <label class="block">
        <span class="mb-2 block text-sm font-bold text-slate-700">Hasta (opcional)</span>
        <input type="date" name="ends_at" value="{{ old('ends_at', optional($rule?->ends_at)->format('Y-m-d')) }}" class="{{ $field }}">
        @error('ends_at') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="flex items-center gap-3 lg:col-span-2">
        <input type="hidden" name="active" value="0">
        <input type="checkbox" name="active" value="1" @checked(old('active', $rule->active ?? true)) class="h-4 w-4 rounded border-slate-300 text-cyan-600">
        <span class="text-sm font-bold text-slate-700">Regla activa</span>
    </label>

    <div class="flex gap-3 lg:col-span-2">
        <button type="submit" class="rounded-xl bg-slate-950 px-6 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">{{ $rule ? 'Guardar cambios' : 'Guardar sugerencia' }}</button>
        @if ($rule)
            <a href="{{ route('admin.sugerencias.index') }}" class="rounded-xl border border-slate-200 px-6 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</a>
        @endif
    </div>
</form>

<script>
    document.querySelectorAll('[data-suggestion-form]').forEach((form) => {
        const type = form.querySelector('[data-trigger-type]');
        const product = form.querySelector('[data-trigger-product]');
        const brand = form.querySelector('[data-trigger-brand]');
        const sync = () => {
            product.hidden = type.value !== 'producto';
            brand.hidden = type.value !== 'marca';
        };
        type.addEventListener('change', sync);
        sync();
        form.querySelector('[data-product-filter]').addEventListener('input', (event) => {
            const text = event.target.value.trim().toLowerCase();
            form.querySelectorAll('[data-product-option]').forEach((option) => {
                option.hidden = text !== '' && !option.dataset.name.includes(text);
            });
        });
    });
</script>
