@extends('layouts.web')

{{--
    Tienda de Bravos (diseño Figma "02 · Tienda Bravos · Todos"): /buscar?marca=BRAVOS. Cabecera negra,
    filtro por categoría con conteos, resultados con la tarjeta de producto y franja de entrega y pagos.
    Usa los mismos datos y filtros GET que web/search.blade.php.
--}}

@section('title', 'Tienda Bravos | Overshark')
@section('body-class', 'theme-neon theme-bravos')

@php
    $url = fn (array $changes = []) => route('web.products.search', array_filter([...$filters, ...$changes], fn ($value) => $value !== null && $value !== ''));
    $categories = collect(\App\Support\StorefrontCategories::all())->filter(fn ($category, string $slug) => ($categoryCounts[$slug] ?? 0) > 0 || $categoria === $slug);
    $categoryLabel = $categoria !== '' ? \App\Support\StorefrontCategories::label($categoria) : 'Todos';
    $sorts = ['' => 'Destacados', 'novedades' => 'Novedades', 'precio-asc' => 'Precio: menor a mayor', 'precio-desc' => 'Precio: mayor a menor'];
    $filtered = $search !== '' || isset($filters['talla']) || isset($filters['color']) || isset($filters['min']) || isset($filters['max']);
    $option = fn (bool $on) => $on ? '●' : '○';
@endphp

@section('content')
    <div class="os-hh os-bravos">
        {{-- Cabecera de tienda --}}
        <header class="bg-[#0a0a0a] text-white">
            <div class="os-bravos-wrap flex flex-col items-start gap-4 py-12 lg:py-14">
                <nav aria-label="Ruta" class="text-[12px] font-bold uppercase leading-[1.4] text-[#bfbfbf]">
                    <a href="{{ route('web.brands.show', $bravosBrand) }}" class="hover:text-white">Bravos</a>
                    <span aria-hidden="true">/</span>
                    <a href="{{ $url(['categoria' => null]) }}" class="hover:text-white">Tienda</a>
                    <span aria-hidden="true">/</span>
                    <span class="text-white">{{ $categoryLabel }}</span>
                </nav>
                <h1 class="text-[34px] font-extrabold leading-[1.4] sm:text-[42px]">TIENDA BRAVOS</h1>
                <p class="text-[16px] leading-[1.4] text-[#ccc]">Neru, Boxy Fit y Bratz. Arma tu estilo urbano.</p>
                <a href="{{ route('web.brands.show', $bravosBrand) }}" class="os-focus text-[13px] font-bold leading-[1.4] hover:text-[#ffdb00]">← VOLVER A BRAVOS</a>
            </div>
        </header>

        {{-- Filtros y resultados --}}
        <div class="os-bravos-wrap grid gap-8 py-10 lg:grid-cols-[224px_minmax(0,1fr)] lg:gap-12 lg:py-16">
            <aside class="flex flex-col gap-6 lg:gap-7" aria-label="Filtros">
                <div class="flex items-baseline justify-between gap-4 lg:flex-col lg:items-start lg:gap-7">
                    <h2 class="text-[24px] font-extrabold leading-[1.4] text-[#0a0a0a]">FILTROS</h2>
                    <a href="{{ route('web.products.search', ['marca' => $marca]) }}" class="os-focus text-[13px] font-bold leading-[1.4] text-[#595959] hover:text-[#0a0a0a] hover:underline">Limpiar filtros</a>
                </div>

                <section class="flex flex-col gap-4 lg:gap-7" aria-labelledby="fb-cat">
                    <h3 id="fb-cat" class="text-[12px] font-extrabold leading-[1.4] text-[#0a0a0a]">CATEGORÍA</h3>
                    <ul class="flex flex-wrap gap-x-6 gap-y-3 lg:flex-col lg:gap-7">
                        <li>
                            <a href="{{ $url(['categoria' => null]) }}" class="os-focus flex items-baseline justify-between gap-3 leading-[1.4] lg:w-[224px]" @if ($categoria === '') aria-current="page" @endif>
                                <span @class(['text-[14px] text-[#0a0a0a] whitespace-pre', 'font-bold' => $categoria === ''])>{{ $option($categoria === '') }}  Todos</span>
                                <span class="text-[12px] text-[#737373]">{{ $allCount }}</span>
                            </a>
                        </li>
                        @foreach ($categories as $slug => $category)
                            <li>
                                <a href="{{ $url(['categoria' => $categoria === $slug ? null : $slug]) }}" class="os-focus flex items-baseline justify-between gap-3 leading-[1.4] hover:underline lg:w-[224px]" @if ($categoria === $slug) aria-current="page" @endif>
                                    <span @class(['text-[14px] text-[#0a0a0a] whitespace-pre', 'font-bold' => $categoria === $slug])>{{ $option($categoria === $slug) }}  {{ $category['label'] }}</span>
                                    <span class="text-[12px] text-[#737373]">{{ $categoryCounts[$slug] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <div class="hidden flex-col gap-3 bg-[#f2f2f2] p-5 lg:flex">
                    <p class="text-[17px] font-extrabold leading-[1.4] text-[#0a0a0a]">ENVÍO S/ 14</p>
                    <p class="text-[13px] leading-[1.4] text-[#595959]">A todo el Perú.<br>Lima: delivery o agencia.<br>Provincia: agencia.</p>
                </div>
            </aside>

            <section aria-label="Resultados" class="flex min-w-0 flex-col gap-7">
                <div class="flex flex-wrap items-center justify-between gap-3 leading-[1.4]">
                    <p class="text-[16px] font-bold text-[#0a0a0a]">
                        {{ $products->total() }} {{ $products->total() === 1 ? 'modelo' : 'modelos' }}
                        @if ($filtered)
                            <a href="{{ route('web.products.search', ['marca' => $marca, 'categoria' => $categoria ?: null]) }}" class="os-focus ml-2 text-[13px] font-bold text-[#595959] underline">Quitar otros filtros</a>
                        @endif
                    </p>
                    <form action="{{ route('web.products.search') }}" method="GET">
                        @foreach (\Illuminate\Support\Arr::except($filters, ['orden']) as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach
                        <label class="flex items-center gap-1 text-[13px] text-[#595959]">
                            <span>Orden:</span>
                            <select name="orden" class="os-focus cursor-pointer bg-transparent pr-1 text-[13px] text-[#595959] outline-none" data-auto-submit>
                                @foreach ($sorts as $value => $label)
                                    <option value="{{ $value }}" @selected($orden === $value || ($value === '' && $orden === 'populares'))>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <noscript><button type="submit" class="mt-2 text-[13px] font-bold underline">Ordenar</button></noscript>
                    </form>
                </div>

                @if ($products->isEmpty())
                    <div class="grid min-h-64 place-items-center bg-[#f5f5f5] px-5 text-center">
                        <div>
                            <p class="text-[18px] font-extrabold text-[#0a0a0a]">No encontramos modelos con esos filtros</p>
                            <p class="mt-1 text-[14px] text-[#595959]">Quita algún filtro o mira toda la colección.</p>
                            <a href="{{ route('web.products.search', ['marca' => $marca]) }}" class="os-focus mt-5 inline-flex p-[18px] text-[13px] font-bold uppercase text-white [background:#0a0a0a] hover:[background:#333]">Ver toda la colección →</a>
                        </div>
                    </div>
                @else
                    <ul class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:gap-8">
                        @foreach ($products as $product)
                            @php($colorCount = collect($product->display_colors)->count())
                            <li class="flex flex-col gap-3">
                                @include('web.partials.hh-product-card', ['photoClass' => 'aspect-[304/314]'])
                                @if ($colorCount > 1)
                                    <p class="text-[12px] font-bold leading-[1.4] text-[#0a0a0a]">{{ $colorCount }} colores</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    @if ($products->hasPages())
                        <nav class="flex justify-center gap-2 pt-6" aria-label="Paginación">
                            @foreach (range(1, $products->lastPage()) as $page)
                                <a href="{{ $products->url($page) }}" @class(['os-focus grid h-11 min-w-11 place-items-center px-3 text-[14px] font-bold', 'bg-[#0a0a0a] text-white' => $page === $products->currentPage(), 'bg-[#f2f2f2] text-[#0a0a0a] hover:bg-[#e5e5e5]' => $page !== $products->currentPage()]) @if ($page === $products->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                            @endforeach
                        </nav>
                    @endif
                @endif
            </section>
        </div>

        {{-- Entrega y pagos --}}
        <section class="bg-[#0a0a0a] text-white" aria-label="Entrega y pagos">
            <ul class="os-bravos-wrap flex flex-col gap-3 py-8 text-[15px] font-bold leading-[1.45] sm:flex-row sm:flex-wrap sm:gap-x-16 sm:text-[16px] lg:py-10">
                <li>ENVÍO S/ 14 A TODO EL PERÚ</li>
                <li>YAPE · PLIN · CONTRAENTREGA</li>
                <li>COMPRA COMO INVITADO</li>
            </ul>
        </section>
    </div>
@endsection
