@extends('layouts.admin')

@section('title', 'Banners')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Contenido</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Banners</h1>
            <p class="mt-2 text-slate-500">Sube y administra las imágenes que aparecen en la portada.</p>
        </div>
        <button type="button" data-banner-form-toggle class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">
            Nuevo banner
        </button>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <section data-banner-form @class([
        'mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6',
        'hidden' => ! $errors->any(),
    ])>
        <div class="mb-5">
            <h2 class="text-xl font-black text-slate-950">Agregar nuevo banner</h2>
            <p class="mt-1 text-sm text-slate-500">Recomendado: imagen horizontal de al menos 1600 × 700 px.</p>
        </div>

        <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" class="grid gap-5 lg:grid-cols-2">
            @csrf

            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Nombre</span>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" placeholder="Ej. Promoción de verano">
                @error('name') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Imagen del banner</span>
                <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" required class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
                @error('image') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Duración en segundos</span>
                <input type="number" name="time" value="{{ old('time', 5) }}" min="1" max="60" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
                @error('time') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Ajuste de imagen</span>
                <select name="modo" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
                    <option value="cover" @selected(old('modo', 'cover') === 'cover')>Cubrir todo el espacio</option>
                    <option value="contain" @selected(old('modo') === 'contain')>Mostrar imagen completa</option>
                </select>
            </label>

            <label class="flex items-center gap-3 text-sm font-bold text-slate-700">
                <input type="hidden" name="status" value="0">
                <input type="checkbox" name="status" value="1" @checked(old('status', true)) class="h-5 w-5 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                Mostrar banner en la portada
            </label>

            <div class="flex items-center gap-3 lg:justify-end">
                <button type="button" data-banner-form-toggle class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 hover:border-slate-400">
                    Cancelar
                </button>
                <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">
                    Guardar banner
                </button>
            </div>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        @if ($banners->isEmpty())
            <div class="grid min-h-64 place-items-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 text-center">
                <div>
                    <p class="font-bold text-slate-700">No hay banners registrados.</p>
                    <p class="mt-1 text-sm text-slate-400">Usa “Nuevo banner” para subir la primera imagen.</p>
                </div>
            </div>
        @else
            <div class="grid gap-5 xl:grid-cols-2">
                @foreach ($banners as $banner)
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="aspect-[16/7] bg-slate-100">
                            <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->name }}" class="h-full w-full object-{{ $banner->modo === 'contain' ? 'contain' : 'cover' }}">
                        </div>
                        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="font-black text-slate-950">{{ $banner->name }}</h2>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-xs font-bold',
                                        'bg-emerald-50 text-emerald-700' => $banner->status,
                                        'bg-slate-100 text-slate-500' => ! $banner->status,
                                    ])>{{ $banner->status ? 'Activo' : 'Inactivo' }}</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">{{ $banner->time ?? 5 }} segundos · {{ $banner->modo === 'contain' ? 'Imagen completa' : 'Cubrir espacio' }}</p>
                            </div>
                            <div class="flex gap-2">
                                <form action="{{ route('admin.banners.toggle', $banner) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-cyan-300 hover:text-cyan-700">
                                        {{ $banner->status ? 'Ocultar' : 'Activar' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" onsubmit="return confirm('¿Eliminar este banner?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
