@extends('layouts.admin')

@section('title', 'Carga masiva de imágenes')

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-blue-600">Catálogo</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Carga masiva de imágenes</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Sube fotos de varios productos y colores en un lote. Indica el producto y el color en cada nombre de archivo.</p>
        </div>
        <a href="{{ route('admin.productos.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-950">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Volver a productos
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

    @if (session('preview_ready'))
        <div class="mb-5 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-800">Revisa las coincidencias. Todavía no se han cambiado las imágenes del catálogo.</div>
    @endif

    @if ($preview)
        @php($validItems = collect($preview['items'] ?? [])->filter(fn ($item) => empty($item['error']) && ! empty($item['temp_path'])) )
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_8px_28px_rgb(15_23_42_/_5%)]">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-6">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">Revisa los archivos</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $validItems->count() }} coincidencias válidas de {{ count($preview['items'] ?? []) }} archivos.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <form action="{{ route('admin.productos.images.cancel') }}" method="POST">@csrf<button class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancelar</button></form>
                    <form action="{{ route('admin.productos.images.apply') }}" method="POST" onsubmit="return confirm('¿Aplicar las imágenes a los productos coincidentes?')">
                        @csrf
                        <input type="hidden" name="token" value="{{ $preview['token'] }}">
                        <button type="submit" @disabled($validItems->isEmpty()) class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-40">Aplicar {{ $validItems->count() }} imágenes</button>
                    </form>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] font-extrabold uppercase tracking-[0.14em] text-slate-400">
                        <tr><th class="px-6 py-3">Archivo</th><th class="px-4 py-3">Producto y color</th><th class="px-4 py-3">Destino</th><th class="px-4 py-3">Coincidencias</th><th class="px-6 py-3">Resultado</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($preview['items'] ?? [] as $item)
                            <tr class="align-top">
                                <td class="px-6 py-4 font-semibold text-slate-800">{{ $item['filename'] }}</td>
                                <td class="px-4 py-4"><span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700">{{ $item['product_name'] ?: '—' }}</span>@if (!empty($item['color']))<span class="mt-2 block text-xs font-bold text-blue-700">Color: {{ $item['color'] }}</span>@endif</td>
                                <td class="px-4 py-4 text-xs font-semibold text-slate-600">{{ !empty($item['color']) ? 'Galería del color' : 'Imagen principal' }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $item['matches'] }}{{ $item['matches'] === 1 ? ' producto' : ' productos' }}
                                    @if (! empty($item['products']))<span class="mt-1 block max-w-md text-xs leading-5 text-slate-400">{{ implode(' · ', $item['products']) }}</span>@endif
                                </td>
                                <td class="px-6 py-4">
                                    @if (! empty($item['error']))
                                        <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700">{{ $item['error'] }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Coincidencia encontrada</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(300px,.85fr)]">
            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_8px_28px_rgb(15_23_42_/_5%)] sm:p-7">
                <div class="mb-5 flex items-start gap-4">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-100">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5"/><path d="M5 14v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5"/></svg>
                    </span>
                    <div><h2 class="text-lg font-extrabold text-slate-900">Selecciona las imágenes</h2><p class="mt-1 text-sm leading-6 text-slate-500">Puedes cargar hasta 20 archivos PNG, JPG o WEBP de máximo 8 MB cada uno. Puedes usar espacios en el nombre.</p></div>
                </div>

                <form action="{{ route('admin.productos.images.preview') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <label for="product-images" class="group flex min-h-56 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/70 px-5 text-center transition hover:border-blue-400 hover:bg-blue-50/40">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-200 transition group-hover:text-blue-700">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                        </span>
                        <span class="mt-4 text-sm font-extrabold text-slate-800">Haz clic para elegir imágenes</span>
                        <span class="mt-1 text-xs text-slate-500">Selección múltiple disponible</span>
                        <input id="product-images" name="images[]" type="file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" multiple required class="sr-only">
                    </label>
                    <p id="selected-image-count" class="mt-3 min-h-5 text-xs font-semibold text-slate-500" aria-live="polite"></p>
                    <button type="submit" class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100 sm:w-auto">
                        Revisar coincidencias
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </form>
            </section>

            <aside class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-5 sm:p-7">
                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-blue-700">Cómo nombrar los archivos</p>
                <h2 class="mt-2 text-xl font-black text-slate-900">Producto y color en el nombre</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Escribe el nombre exacto del producto, dos guiones bajos y el color. Agrega otro separador y un número para ordenar varias fotos del mismo color.</p>
                <div class="mt-5 space-y-2 rounded-xl border border-blue-100 bg-white/80 p-4 font-mono text-xs font-bold text-slate-700">
                    <p>Camisero Pike__Negro__01.jpg</p>
                    <p>Camisero Pike__Negro__02.jpg</p>
                    <p>Camisero Pike__Azul__01.jpg</p>
                </div>
                <p class="mt-3 text-xs leading-5 text-slate-600">La parte después de <code>__</code> es el color. Numera las fotos para ordenarlas. Se permiten hasta 5 imágenes por color y reemplazan la galería actual de ese color.</p>
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900">
                    Para la imagen principal puedes usar <code>Camisero Pike.jpg</code>. Los nombres del producto y del color deben coincidir con el catálogo. La revisión muestra todas las coincidencias antes de guardar.
                </div>
            </aside>
        </div>
    @endif

    <section class="mt-7 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_8px_28px_rgb(15_23_42_/_5%)]">
        <div class="border-b border-slate-100 px-5 py-5 lg:px-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-blue-700">Estado del catálogo</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Imágenes por variante y color</h2>
                    <p class="mt-1 text-sm text-slate-500">Revisa qué colores tienen galería, cuáles usan la imagen principal y dónde faltan imágenes.</p>
                </div>
                <div class="grid grid-cols-3 gap-2 sm:gap-3">
                    <div class="rounded-xl bg-slate-50 px-3 py-2 text-center"><span class="block text-lg font-black text-slate-950">{{ $coverageSummary['products'] }}</span><span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Productos</span></div>
                    <div class="rounded-xl bg-emerald-50 px-3 py-2 text-center"><span class="block text-lg font-black text-emerald-700">{{ $coverageSummary['covered'] }}</span><span class="text-[10px] font-bold uppercase tracking-wide text-emerald-700">Colores completos</span></div>
                    <div class="rounded-xl bg-red-50 px-3 py-2 text-center"><span class="block text-lg font-black text-red-700">{{ $coverageSummary['missing'] }}</span><span class="text-[10px] font-bold uppercase tracking-wide text-red-700">Por completar</span></div>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.productos.images.bulk') }}" class="mt-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_220px_auto]">
                <label class="sr-only" for="coverage-search">Buscar producto</label>
                <input id="coverage-search" type="search" name="coverage_search" value="{{ $coverageSearch }}" placeholder="Buscar producto..." class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                <label class="sr-only" for="coverage-status">Filtrar cobertura</label>
                <select id="coverage-status" name="coverage_status" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-blue-400">
                    <option value="all" @selected($coverageStatus === 'all')>Todos los productos</option>
                    <option value="missing" @selected($coverageStatus === 'missing')>Con colores sin imagen</option>
                    <option value="complete" @selected($coverageStatus === 'complete')>Todos los colores cubiertos</option>
                </select>
                <button class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700">Filtrar</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-extrabold uppercase tracking-[0.14em] text-slate-400">
                    <tr><th class="px-6 py-3">Producto</th><th class="px-4 py-3">Cobertura</th><th class="px-6 py-3">Estado por color</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($coverageProducts as $productCoverage)
                        <tr class="align-top hover:bg-slate-50/60">
                            <td class="px-6 py-4">
                                <p class="font-extrabold text-slate-900">{{ $productCoverage['name'] }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $productCoverage['color_count'] }} {{ $productCoverage['color_count'] === 1 ? 'color' : 'colores' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
                                    'bg-emerald-50 text-emerald-700' => $productCoverage['complete'],
                                    'bg-red-50 text-red-700' => ! $productCoverage['complete'],
                                ])>
                                    {{ $productCoverage['covered_count'] }}/{{ $productCoverage['color_count'] }} colores completos
                                </span>
                                @if (! $productCoverage['complete'])<span class="mt-1 block text-xs font-semibold text-red-600">Faltan {{ $productCoverage['missing_count'] }}</span>@endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($productCoverage['colors'] as $colorCoverage)
                                        <span @class([
                                            'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-bold',
                                            'border-emerald-200 bg-emerald-50 text-emerald-800' => $colorCoverage['source'] === 'gallery',
                                            'border-blue-200 bg-blue-50 text-blue-800' => $colorCoverage['source'] === 'main',
                                            'border-amber-200 bg-amber-50 text-amber-800' => $colorCoverage['source'] === 'partial',
                                            'border-red-200 bg-red-50 text-red-700' => $colorCoverage['source'] === 'missing',
                                        ])>
                                            <span>{{ $colorCoverage['name'] }}</span>
                                            @if ($colorCoverage['source'] === 'gallery')<span class="opacity-70">· {{ $colorCoverage['gallery_count'] }} fotos</span>
                                            @elseif ($colorCoverage['source'] === 'main')<span class="opacity-70">· variantes cubiertas</span>
                                            @elseif ($colorCoverage['source'] === 'partial')<span class="opacity-80">· {{ $colorCoverage['main_image_count'] }}/{{ $colorCoverage['variant_count'] }} variantes</span>
                                            @else<span class="opacity-80">· falta imagen</span>@endif
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-10 text-center text-sm font-semibold text-slate-400">No hay productos que coincidan con esos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($coverageProducts->hasPages())
            <div class="border-t border-slate-100 px-5 py-4 lg:px-6">{{ $coverageProducts->links() }}</div>
        @endif

        <div class="flex flex-wrap gap-x-5 gap-y-2 border-t border-slate-100 bg-slate-50/70 px-5 py-3 text-xs font-semibold text-slate-500 lg:px-6">
            <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-emerald-500"></span>Galería propia del color</span>
            <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-blue-500"></span>Imagen principal cubre todas las variantes</span>
            <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-amber-500"></span>Imagen en algunas variantes</span>
            <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-red-500"></span>Sin imagen</span>
        </div>
    </section>

    <script>
        document.querySelector('#product-images')?.addEventListener('change', (event) => {
            const count = event.target.files.length;
            document.querySelector('#selected-image-count').textContent = count
                ? `${count} ${count === 1 ? 'imagen seleccionada' : 'imágenes seleccionadas'}`
                : '';
        });
    </script>
@endsection
