@extends('layouts.admin')

@section('title', 'Marcas')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Configuracion comercial</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950">Marcas</h1>
        <p class="mt-2 max-w-3xl text-slate-500">Crea las marcas de la tienda, define su paleta visual y controla si aparecen en el menu publico.</p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            Revisa los datos de la marca: {{ $errors->first() }}
        </div>
    @endif

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-950">Nueva marca</h2>
                <p class="mt-1 text-sm text-slate-500">La URL se genera con el slug y la página filtrará automáticamente sus productos.</p>
            </div>
            <span class="rounded-full bg-cyan-50 px-3 py-1.5 text-xs font-bold text-cyan-700">Paleta de 5 colores</span>
        </div>

        <form action="{{ route('admin.brands.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            <div class="grid gap-4 md:grid-cols-3">
                <label class="block md:col-span-2">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Nombre</span>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="120" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="Ej. Overshark Girls">
                </label>
                <label class="block">
                    <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Slug de URL</span>
                    <input type="text" name="slug" value="{{ old('slug') }}" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="overshark-girls">
                </label>
            </div>
            <label class="block max-w-xl">
                <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Logo de la marca</span>
                <input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,.svg" class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
                <span class="mt-2 block text-xs text-slate-400">Opcional. PNG, JPG, WEBP o SVG; máximo 4 MB.</span>
            </label>
            @include('admin.brands._palette-fields', ['values' => null, 'prefix' => 'new'])
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-slate-500">Usa valores hexadecimales de seis digitos, por ejemplo #0078D7.</p>
                <button type="submit" class="rounded-xl bg-slate-950 px-6 py-3 text-sm font-bold text-white transition hover:bg-cyan-600 focus:outline-none focus:ring-4 focus:ring-cyan-100">Crear marca</button>
            </div>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-black text-slate-950">Marcas configuradas</h2>
                <p class="mt-1 text-sm text-slate-500">Cada marca tiene una vista publica independiente.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-500">{{ $brands->count() }} marcas</span>
        </div>

        <div class="mt-6 space-y-5">
            @forelse ($brands as $brand)
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4 lg:p-5" style="border-top: 5px solid {{ $brand->primary_color }}">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl text-xs font-black" style="background: {{ $brand->accent_color }}; color: {{ $brand->text_color }}; border: 3px solid {{ $brand->primary_color }}">Aa</div>
                            <div>
                                <h3 class="text-lg font-black text-slate-950">{{ $brand->name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">/marcas/{{ $brand->slug }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach (['primary_color' => 'Principal', 'secondary_color' => 'Secundario', 'accent_color' => 'Acento', 'background_color' => 'Fondo', 'text_color' => 'Texto'] as $field => $label)
                                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-bold text-slate-600">
                                            <span class="h-3 w-3 rounded-full border border-slate-200" style="background-color: {{ $brand->{$field} }}"></span>
                                            {{ $label }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full px-3 py-1.5 text-xs font-black {{ $brand->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">{{ $brand->is_active ? 'Visible' : 'Oculta' }}</span>
                            <form action="{{ route('admin.brands.toggle', $brand) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:border-cyan-500 hover:text-cyan-700">{{ $brand->is_active ? 'Ocultar' : 'Mostrar' }}</button>
                            </form>
                        </div>
                    </div>

                    <form action="{{ route('admin.brands.update', $brand) }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5 border-t border-slate-200 pt-5">
                        @csrf
                        @method('PATCH')
                        <div class="grid gap-4 md:grid-cols-3">
                            <label class="block md:col-span-2">
                                <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Nombre</span>
                                <input type="text" name="name" value="{{ $brand->name }}" maxlength="120" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100">
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Slug</span>
                                <input type="text" name="slug" value="{{ $brand->slug }}" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100">
                            </label>
                        </div>
                        <label class="block max-w-xl">
                            <span class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Logo de la marca</span>
                            <div class="flex flex-wrap items-center gap-4">
                                @if ($brand->logoUrl())
                                    <img src="{{ $brand->logoUrl() }}" alt="Logo de {{ $brand->name }}" class="h-14 max-w-48 rounded-xl border border-slate-200 bg-white object-contain p-2">
                                @endif
                                <input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,.svg" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
                            </div>
                            <span class="mt-2 block text-xs text-slate-400">Sube un archivo nuevo para reemplazar el actual.</span>
                        </label>
                        @include('admin.brands._palette-fields', ['values' => $brand, 'prefix' => 'brand-'.$brand->id])
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-slate-500">Los productos se agrupan por el valor de su campo marca, sin importar mayusculas.</p>
                            <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600 focus:outline-none focus:ring-4 focus:ring-cyan-100">Guardar cambios</button>
                        </div>
                    </form>
                </article>
            @empty
                <div class="grid min-h-44 place-items-center rounded-2xl border border-dashed border-slate-300 text-center text-sm text-slate-500">Todavia no hay marcas configuradas.</div>
            @endforelse
        </div>
    </section>
@endsection
