@extends('layouts.admin')

@section('title', 'Combos')

@section('content')
    @php
        $oldProducts = old('products', [['producto_id' => '', 'cantidad' => 1]]);
    @endphp

    <div class="mb-8 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Contenido comercial</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Combos</h1>
            <p class="mt-2 max-w-2xl text-slate-500">Administra promociones, precios, cantidades y productos que aparecen en el menú público.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <form action="{{ route('admin.combos.import') }}" method="POST">
                @csrf
                <button type="submit" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:border-cyan-500 hover:text-cyan-700">
                    Cargar catálogo base
                </button>
            </form>
            <button type="button" data-combo-form-toggle class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">
                Nuevo combo
            </button>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('import_missing'))
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900" role="status">
            <p class="font-black">Productos aún no vinculados</p>
            <p class="mt-1">Los combos ya se guardaron con su composición ZAZU. Sincroniza el inventario para crear las relaciones de productos.</p>
            <p class="mt-2 font-semibold">{{ collect(session('import_missing'))->unique()->implode(', ') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800" role="alert">
            <p class="font-black">Revisa los datos del combo</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section data-combo-form @class([
        'mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6',
        'hidden' => ! $errors->any(),
    ])>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-cyan-600">Alta manual</p>
                <h2 class="mt-1 text-xl font-black text-slate-950">Agregar nuevo combo</h2>
                <p class="mt-1 text-sm text-slate-500">Si no subes una imagen, se usará la foto estándar del catálogo.</p>
            </div>
        </div>

        <form action="{{ route('admin.combos.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-6" novalidate>
            @csrf

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Marca</span>
                    <input id="combo-brand" type="text" name="brand" value="{{ old('brand', 'Overshark') }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Modalidad</span>
                    <input id="combo-modality" type="text" name="modality" value="{{ old('modality', 'Promoción') }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" placeholder="Live, Publicidad...">
                </label>
                <label class="block md:col-span-2">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Nombre del combo</span>
                    <input id="combo-name" type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" placeholder="Ej. 5x99 o Combo Waflera">
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Precio total</span>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-sm font-bold text-slate-400">S/</span>
                        <input id="combo-price" type="number" name="price" value="{{ old('price') }}" min="0" step="0.01" inputmode="decimal" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" placeholder="99.00">
                    </div>
                </label>
                <label class="block md:col-span-2">
                    <span class="mb-2 block text-sm font-bold text-slate-700">URL de destino <span class="font-normal text-slate-400">(opcional)</span></span>
                    <input id="combo-url" type="text" name="url" value="{{ old('url') }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" placeholder="Se genera /combos/ID automáticamente">
                </label>
            </div>

            <fieldset class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <legend class="px-2 text-sm font-black text-slate-950">Productos y cantidades</legend>
                @if ($productOptions->isEmpty())
                    <p class="rounded-xl border border-dashed border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
                        Aún no hay productos sincronizados. Puedes cargar el catálogo base ahora y vincular los productos después de sincronizar ZAZU.
                    </p>
                @endif
                <div data-combo-product-rows class="space-y-3">
                    @foreach ($oldProducts as $index => $productRow)
                        <div data-combo-product-row class="grid gap-3 sm:grid-cols-[1fr_130px_auto]">
                            <label class="block">
                                <span class="sr-only">Producto {{ $index + 1 }}</span>
                                <select name="products[{{ $index }}][producto_id]" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
                                    <option value="">Selecciona un producto</option>
                                    @foreach ($productOptions as $productOption)
                                        <option value="{{ $productOption->id }}" @selected((string) ($productRow['producto_id'] ?? '') === (string) $productOption->id)>
                                            {{ $productOption->name }}{{ $productOption->empresa_nombre ? ' · '.$productOption->empresa_nombre : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block">
                                <span class="sr-only">Cantidad {{ $index + 1 }}</span>
                                <input type="number" name="products[{{ $index }}][cantidad]" value="{{ $productRow['cantidad'] ?? 1 }}" min="1" max="99" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
                            </label>
                            <button type="button" data-combo-product-remove class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-500 transition hover:border-red-300 hover:text-red-600" aria-label="Quitar producto">Quitar</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" data-combo-product-add class="mt-4 rounded-xl border border-cyan-200 bg-white px-4 py-2.5 text-sm font-bold text-cyan-700 transition hover:bg-cyan-50">+ Agregar producto</button>
            </fieldset>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-700">Imagen estándar o personalizada</span>
                    <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
                    <span class="mt-2 block text-xs text-slate-400">PNG, JPG, JPEG o WEBP; máximo 8 MB.</span>
                </label>
                <label class="flex items-start gap-3 pt-1 text-sm font-bold text-slate-700">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1" @checked(old('status', true)) class="mt-0.5 h-5 w-5 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                    <span>Mostrar en el menú público</span>
                </label>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <button type="button" data-combo-form-toggle class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 transition hover:border-slate-400">Cancelar</button>
                <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">Guardar combo</button>
            </div>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-950">Catálogo de combos</h2>
                <p class="mt-1 text-sm text-slate-500">Cada tarjeta conserva el precio y la composición definida en tu matriz.</p>
            </div>
            <p class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-500">{{ $combos->count() }} registros</p>
        </div>

        @if ($combos->isEmpty())
            <div class="mt-6 grid min-h-64 place-items-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-center">
                <div>
                    <p class="font-bold text-slate-700">No hay combos registrados.</p>
                    <p class="mt-1 text-sm text-slate-400">Usa “Cargar catálogo base” para crear las promociones de la matriz.</p>
                </div>
            </div>
        @else
            <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($combos as $combo)
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                            <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="h-full w-full object-cover">
                            <span class="absolute left-4 top-4 rounded-full bg-white/95 px-3 py-1.5 text-xs font-black uppercase tracking-wide text-slate-700">{{ $combo->brand ?: 'Overshark' }}</span>
                        </div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="font-black text-slate-950">{{ $combo->name }}</h2>
                                    <p class="mt-1 text-xs font-bold uppercase tracking-wide text-cyan-700">{{ $combo->modality ?: 'Promoción' }}</p>
                                </div>
                                <span @class(['rounded-full px-2.5 py-1 text-xs font-bold', 'bg-emerald-50 text-emerald-700' => $combo->status, 'bg-slate-100 text-slate-500' => ! $combo->status])>{{ $combo->status ? 'Activo' : 'Inactivo' }}</span>
                            </div>
                            @if ($combo->price !== null)
                                <p class="mt-4 text-2xl font-black text-slate-950">S/ {{ number_format((float) $combo->price, 2) }}</p>
                            @endif
                            <p class="mt-1 text-xs font-semibold text-slate-400">{{ $combo->quantityLabel() }}</p>
                            <ul class="mt-4 space-y-1.5 text-sm text-slate-600">
                                @forelse ($combo->displayItems() as $item)
                                    <li class="flex items-start justify-between gap-3"><span>{{ $item['zazu'] }}</span><strong class="text-slate-950">×{{ $item['cantidad'] }}</strong></li>
                                @empty
                                    <li class="text-slate-400">Composición pendiente</li>
                                @endforelse
                            </ul>
                            @if ($combo->notes)
                                <p class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-xs font-semibold leading-5 text-amber-900">{{ $combo->notes }}</p>
                            @endif
                            <div class="mt-5 flex flex-wrap gap-2">
                                <a href="{{ $combo->url ?: route('web.combos.show', $combo) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-cyan-300 hover:text-cyan-700">Ver página</a>
                                <form action="{{ route('admin.combos.toggle', $combo) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">{{ $combo->status ? 'Ocultar' : 'Activar' }}</button>
                                </form>
                                <form action="{{ route('admin.combos.destroy', $combo) }}" method="POST" data-combo-delete-form data-combo-name="{{ $combo->name }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <dialog data-combo-delete-dialog class="w-[min(92vw,28rem)] rounded-2xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/50">
        <div class="p-6">
            <h2 class="text-lg font-black text-slate-950">Eliminar combo</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Se eliminará <strong data-combo-delete-name>este combo</strong> y su composición. Esta acción no se puede deshacer.</p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" data-combo-delete-cancel class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Cancelar</button>
                <button type="button" data-combo-delete-confirm class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-700">Eliminar</button>
            </div>
        </div>
    </dialog>
@endsection
