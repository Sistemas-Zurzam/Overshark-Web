@extends('layouts.admin')

@section('title', $producto->name)

@section('content')
    <div class="mb-8 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <a href="{{ route('admin.productos.index') }}" class="text-sm font-bold text-cyan-700 hover:text-cyan-900">Volver a productos</a>
            <p class="mt-4 text-sm font-bold uppercase tracking-widest text-cyan-600">Producto</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">{{ $producto->name }}</h1>
            <p class="mt-2 text-slate-500">Variantes sincronizadas desde Odoo y gestion de imagenes.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-600 shadow-sm">
            <div><span class="font-bold text-slate-950">Template Odoo:</span> {{ $producto->odoo_template_id ?? '-' }}</div>
            <div><span class="font-bold text-slate-950">Variantes:</span> {{ $variantsBySize->flatten(1)->count() }}</div>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            Revisa los archivos: solo PNG, JPG, JPEG o WEBP, maximo 8 MB por imagen.
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[360px_1fr]">
        <aside class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Imagen del producto</h2>
                <div class="mt-4 aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                    @if ($producto->imageUrl())
                        <img src="{{ $producto->imageUrl() }}" alt="{{ $producto->name }}" class="h-full w-full object-cover">
                    @else
                        <div class="grid h-full place-items-center px-6 text-center text-sm font-semibold text-slate-400">Sin imagen principal</div>
                    @endif
                </div>
                <form action="{{ route('admin.productos.image', $producto) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" required class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
                    <button type="submit" class="w-full rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">Guardar imagen</button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Imagenes por color</h2>
                <p class="mt-1 text-sm text-slate-500">Sube hasta 5 imagenes. Al guardar, reemplaza las anteriores de ese color.</p>

                <form action="{{ route('admin.productos.color-images', $producto) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <select name="color" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
                        <option value="">Selecciona color</option>
                        @foreach ($colors as $color)
                            <option value="{{ $color }}">{{ $color }}</option>
                        @endforeach
                    </select>
                    <input type="file" name="images[]" accept=".png,.jpg,.jpeg,.webp" multiple required class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
                    <button type="submit" class="w-full rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:border-cyan-300 hover:text-cyan-700">Guardar imagenes del color</button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Guia de tallas</h2>
                <p class="mt-1 text-sm text-slate-500">Sube una imagen para mostrarla al hacer click en Guia de tallas.</p>

                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                    @if ($producto->sizeGuideImageUrl())
                        <img src="{{ $producto->sizeGuideImageUrl() }}" alt="Guia de tallas {{ $producto->name }}" class="h-auto w-full object-contain">
                    @else
                        <div class="grid min-h-40 place-items-center px-6 text-center text-sm font-semibold text-slate-400">Sin guia de tallas</div>
                    @endif
                </div>

                <form action="{{ route('admin.productos.size-guide', $producto) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <input type="file" name="guia_tallas_imagen" accept=".png,.jpg,.jpeg,.webp" required class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
                    <button type="submit" class="w-full rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:border-cyan-300 hover:text-cyan-700">Guardar guia de tallas</button>
                </form>
            </section>
        </aside>

        <main class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
                <h2 class="text-lg font-black text-slate-950">Detalle comercial</h2>
                <p class="mt-1 text-sm text-slate-500">Estos textos se muestran en la pagina del producto y se aplican a todas sus variantes.</p>

                <form action="{{ route('admin.productos.details', $producto) }}" method="POST" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-4 lg:grid-cols-3">
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Material</span>
                            <input type="text" name="material" value="{{ old('material', $producto->material) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Fit</span>
                            <input type="text" name="fit" value="{{ old('fit', $producto->fit) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Sensacion</span>
                            <input type="text" name="sensacion" value="{{ old('sensacion', $producto->sensacion) }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Descripcion</span>
                        <textarea name="descripcion" rows="4" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">{{ old('descripcion', $producto->descripcion) }}</textarea>
                    </label>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Composicion</span>
                            <textarea name="composicion" rows="4" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">{{ old('composicion', $producto->composicion) }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Cuidados</span>
                            <textarea name="cuidados" rows="4" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">{{ old('cuidados', $producto->cuidados) }}</textarea>
                        </label>
                    </div>

                    <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">Guardar detalle comercial</button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
                <h2 class="text-lg font-black text-slate-950">Variantes</h2>
                <div class="mt-5 space-y-6">
                    @foreach ($variantsBySize as $size => $variants)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <h3 class="text-sm font-black uppercase text-slate-950">Talla: {{ $size }}</h3>
                            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                @foreach ($variants as $variant)
                                    <article class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700">
                                        <p><span class="font-black text-slate-950">Color:</span> {{ $variant->color ?? '-' }}</p>
                                        <p><span class="font-black text-slate-950">SKU:</span> {{ $variant->default_code ?? '-' }}</p>
                                        <p><span class="font-black text-slate-950">Precio:</span> S/ {{ number_format((float) $variant->price, 2) }}</p>
                                        <p><span class="font-black text-slate-950">Costo:</span> S/ {{ number_format((float) $variant->standard_price, 2) }}</p>
                                        <p><span class="font-black text-slate-950">Stock:</span> {{ number_format((float) $variant->qty_available, 2) }}</p>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
                <h2 class="text-lg font-black text-slate-950">Galerias guardadas por color</h2>
                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    @forelse ($colorImages as $color => $gallery)
                        <article class="rounded-2xl border border-slate-200 p-4">
                            <h3 class="font-black text-slate-950">{{ $color }}</h3>
                            <div class="mt-3 grid grid-cols-5 gap-2">
                                @foreach ($gallery->imageUrls() as $url)
                                    <img src="{{ $url }}" alt="{{ $producto->name }} {{ $color }}" class="aspect-square rounded-lg border border-slate-200 object-cover">
                                @endforeach
                            </div>
                        </article>
                    @empty
                        <p class="rounded-xl bg-slate-50 p-4 text-sm font-semibold text-slate-400">Todavia no hay imagenes por color.</p>
                    @endforelse
                </div>
            </section>
        </main>
    </div>
@endsection
