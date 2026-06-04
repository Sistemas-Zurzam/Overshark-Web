@extends('layouts.admin')

@section('title', 'Combos')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Contenido</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Combos</h1>
            <p class="mt-2 text-slate-500">Administra las opciones que aparecen al pulsar el botón Combos.</p>
        </div>
        <button type="button" data-combo-form-toggle class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">
            Nuevo combo
        </button>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <section data-combo-form @class([
        'mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6',
        'hidden' => ! $errors->any(),
    ])>
        <h2 class="text-xl font-black text-slate-950">Agregar nuevo combo</h2>
        <p class="mt-1 text-sm text-slate-500">La URL puede ser una ruta interna como /combos/verano o una dirección completa.</p>

        <form action="{{ route('admin.combos.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 grid gap-5 lg:grid-cols-2">
            @csrf

            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Nombre</span>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" placeholder="Ej. Combo verano">
                @error('name') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">URL de destino</span>
                <input type="text" name="url" value="{{ old('url') }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" placeholder="/productos o https://...">
                @error('url') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block lg:col-span-2">
                <span class="mb-2 block text-sm font-bold text-slate-700">Imagen</span>
                <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" required class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
                @error('image') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="flex items-center gap-3 text-sm font-bold text-slate-700">
                <input type="hidden" name="status" value="0">
                <input type="checkbox" name="status" value="1" @checked(old('status', true)) class="h-5 w-5 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                Mostrar en el botón Combos
            </label>

            <div class="flex items-center gap-3 lg:justify-end">
                <button type="button" data-combo-form-toggle class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600">Cancelar</button>
                <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white hover:bg-cyan-600">Guardar combo</button>
            </div>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        @if ($combos->isEmpty())
            <div class="grid min-h-64 place-items-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-center">
                <div><p class="font-bold text-slate-700">No hay combos registrados.</p><p class="mt-1 text-sm text-slate-400">Crea uno para mostrarlo en el menú público.</p></div>
            </div>
        @else
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($combos as $combo)
                    <article class="overflow-hidden rounded-2xl border border-slate-200">
                        <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="aspect-[4/3] w-full object-cover">
                        <div class="p-5">
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="font-black text-slate-950">{{ $combo->name }}</h2>
                                <span @class(['rounded-full px-2.5 py-1 text-xs font-bold', 'bg-emerald-50 text-emerald-700' => $combo->status, 'bg-slate-100 text-slate-500' => ! $combo->status])>{{ $combo->status ? 'Activo' : 'Inactivo' }}</span>
                            </div>
                            <p class="mt-2 truncate text-sm text-slate-500">{{ $combo->url }}</p>
                            <div class="mt-4 flex gap-2">
                                <form action="{{ route('admin.combos.toggle', $combo) }}" method="POST">@csrf @method('PATCH')<button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">{{ $combo->status ? 'Ocultar' : 'Activar' }}</button></form>
                                <form action="{{ route('admin.combos.destroy', $combo) }}" method="POST" onsubmit="return confirm('¿Eliminar este combo?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600">Eliminar</button></form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
