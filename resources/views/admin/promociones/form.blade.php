@extends('layouts.admin')

@section('title', $promo ? 'Editar promoción' : 'Nueva promoción')

@php
    $field = 'h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none';
    $label = 'mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary';
    $scope = old('scope_type', $promo->scope_type ?? 'producto');
    $values = collect($promo->scope_values ?? []);
    $chosenProducts = collect(old('scope_products', $scope === 'producto' ? $values->all() : []));
    $chosenBrands = collect(old('scope_brands', $scope === 'marca' ? $values->all() : []))->map(fn ($brand) => mb_strtoupper($brand));
    $chosenCategories = collect(old('scope_categories', $scope === 'categoria' ? $values->all() : []))->map(fn ($key) => (string) $key);
    $discountType = old('discount_type', $promo->discount_type ?? 'porcentaje');
    // Fechas en hora de Lima, como las escribe quien programa la oferta.
    $startsAt = old('starts_at', $promo?->local('starts_at')->format('Y-m-d\TH:i'));
    $endsAt = old('ends_at', $promo?->local('ends_at')->format('Y-m-d\TH:i'));
    $error = fn (string $name) => $errors->first($name);
@endphp

@section('content')
    <div class="flex flex-col gap-5">
        <a href="{{ route('admin.promociones.index') }}" class="flex items-center gap-1.5 text-[13px] font-medium text-admin-text-secondary">← Promociones</a>

        <div class="border-b-2 border-admin-text pb-4.5">
            <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[40px]">{{ $promo ? 'Editar promoción' : 'Nueva promoción' }}</h1>
            <p class="mt-1 text-sm text-admin-text-secondary">Las horas se escriben en hora de Lima. Al terminar, el precio normal vuelve solo.</p>
        </div>

        <form action="{{ $promo ? route('admin.promociones.update', $promo) : route('admin.promociones.store') }}" method="POST" data-promo-form class="grid gap-5 rounded-sm border border-admin-border bg-admin-surface p-6 lg:grid-cols-2">
            @csrf
            @if ($promo) @method('PUT') @endif

            <label class="block">
                <span class="{{ $label }}">Nombre (solo lo ves tú)</span>
                <input type="text" name="name" value="{{ old('name', $promo->name ?? '') }}" required class="{{ $field }}" placeholder="Ej. Relámpago de viernes">
                @if ($error('name')) <span class="mt-1 block text-xs font-semibold text-admin-danger">{{ $error('name') }}</span> @endif
            </label>

            <label class="block">
                <span class="{{ $label }}">Etiqueta de la tarjeta (opcional)</span>
                <input type="text" name="label" value="{{ old('label', $promo->label ?? '') }}" maxlength="40" class="{{ $field }}" placeholder="Ej. 2×1 · Solo hoy (si está vacía muestra -30%)">
            </label>

            <label class="block">
                <span class="{{ $label }}">Aplica a</span>
                <select name="scope_type" data-scope-type class="{{ $field }}">
                    <option value="producto" @selected($scope === 'producto')>Prendas concretas</option>
                    <option value="marca" @selected($scope === 'marca')>Todas las prendas de una marca</option>
                    <option value="categoria" @selected($scope === 'categoria')>Una categoría</option>
                </select>
            </label>

            <div class="grid grid-cols-2 gap-4">
                <label class="block">
                    <span class="{{ $label }}">Descuento</span>
                    <select name="discount_type" class="{{ $field }}">
                        <option value="porcentaje" @selected($discountType === 'porcentaje')>Porcentaje (%)</option>
                        <option value="precio_fijo" @selected($discountType === 'precio_fijo')>Precio fijo (S/)</option>
                    </select>
                </label>
                <label class="block">
                    <span class="{{ $label }}">Valor</span>
                    <input type="number" name="discount_value" value="{{ old('discount_value', $promo->discount_value ?? '') }}" step="0.01" min="0.01" required class="{{ $field }}" placeholder="30">
                    @if ($error('discount_value')) <span class="mt-1 block text-xs font-semibold text-admin-danger">{{ $error('discount_value') }}</span> @endif
                </label>
            </div>

            <div class="lg:col-span-2">
                <div data-scope-box="producto">
                    <div class="mb-1.5 flex flex-wrap items-center justify-between gap-2">
                        <span class="{{ $label }} mb-0">Prendas</span>
                        <input type="search" data-option-filter placeholder="Buscar prenda…" class="h-9 w-56 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm outline-none">
                    </div>
                    <div class="grid max-h-64 gap-1 overflow-y-auto rounded-sm border border-admin-border-strong p-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($productNames as $name)
                            <label data-option data-name="{{ mb_strtolower($name) }}" class="flex items-center gap-2 px-2 py-1.5 text-sm hover:bg-admin-subtle">
                                <input type="checkbox" name="scope_products[]" value="{{ $name }}" @checked($chosenProducts->contains($name)) class="size-4">
                                <span>{{ $name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @if ($error('scope_products')) <span class="mt-1 block text-xs font-semibold text-admin-danger">{{ $error('scope_products') }}</span> @endif
                </div>

                <div data-scope-box="marca">
                    <span class="{{ $label }}">Marcas</span>
                    <div class="flex flex-wrap gap-4 rounded-sm border border-admin-border-strong p-3">
                        @foreach ($brandNames as $brandName)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="scope_brands[]" value="{{ $brandName }}" @checked($chosenBrands->contains(mb_strtoupper($brandName))) class="size-4"> {{ $brandName }}</label>
                        @endforeach
                    </div>
                    @if ($error('scope_brands')) <span class="mt-1 block text-xs font-semibold text-admin-danger">{{ $error('scope_brands') }}</span> @endif
                </div>

                <div data-scope-box="categoria">
                    <span class="{{ $label }}">Categorías</span>
                    <div class="flex flex-wrap gap-4 rounded-sm border border-admin-border-strong p-3">
                        @foreach ($categorias as $categoryKey => $categoryLabel)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="scope_categories[]" value="{{ $categoryKey }}" @checked($chosenCategories->contains($categoryKey)) class="size-4"> {{ $categoryLabel }}</label>
                        @endforeach
                    </div>
                    @if ($error('scope_categories')) <span class="mt-1 block text-xs font-semibold text-admin-danger">{{ $error('scope_categories') }}</span> @endif
                </div>
            </div>

            <label class="block">
                <span class="{{ $label }}">Empieza (hora de Lima)</span>
                <input type="datetime-local" name="starts_at" value="{{ $startsAt }}" required data-datetime class="{{ $field }}">
                @if ($error('starts_at')) <span class="mt-1 block text-xs font-semibold text-admin-danger">{{ $error('starts_at') }}</span> @endif
            </label>

            <div>
                <label class="block">
                    <span class="{{ $label }}">Termina (hora de Lima)</span>
                    <input type="datetime-local" name="ends_at" value="{{ $endsAt }}" required data-datetime class="{{ $field }}">
                    @if ($error('ends_at')) <span class="mt-1 block text-xs font-semibold text-admin-danger">{{ $error('ends_at') }}</span> @endif
                </label>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                    <span class="font-bold text-admin-text-secondary">Dura desde el inicio:</span>
                    @foreach ([1, 3, 6, 12, 24, 48, 72] as $hours)
                        <button type="button" data-end-hours="{{ $hours }}" class="border border-admin-border-strong bg-admin-surface px-2.5 py-1 font-bold text-admin-text hover:border-admin-accent hover:text-admin-accent">{{ $hours < 24 ? $hours.' h' : ($hours / 24).($hours === 24 ? ' día' : ' días') }}</button>
                    @endforeach
                </div>
            </div>

            <label class="flex items-center gap-3 lg:col-span-2">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" @checked(old('active', $promo->active ?? true)) class="size-4">
                <span class="text-sm font-bold text-admin-text">Promoción activa (si la pausas, el precio normal vuelve de inmediato)</span>
            </label>

            <div class="flex gap-3 lg:col-span-2">
                <button type="submit" class="bg-admin-navy px-6 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">{{ $promo ? 'Guardar cambios' : 'Guardar promoción' }}</button>
                <a href="{{ route('admin.promociones.index') }}" class="border border-admin-border-strong px-6 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Cancelar</a>
            </div>
        </form>
    </div>

    @vite('resources/js/admin-datetime.js')

    <script>
        document.querySelectorAll('[data-promo-form]').forEach((form) => {
            const type = form.querySelector('[data-scope-type]');
            const sync = () => form.querySelectorAll('[data-scope-box]').forEach((box) => {
                box.hidden = box.dataset.scopeBox !== type.value;
                // Solo viaja lo del alcance elegido.
                box.querySelectorAll('input[type=checkbox]').forEach((input) => { input.disabled = box.hidden; });
            });
            type.addEventListener('change', sync);
            sync();
            form.querySelector('[data-option-filter]')?.addEventListener('input', (event) => {
                const text = event.target.value.trim().toLowerCase();
                form.querySelectorAll('[data-option]').forEach((option) => { option.hidden = text !== '' && !option.dataset.name.includes(text); });
            });
        });
    </script>
@endsection
