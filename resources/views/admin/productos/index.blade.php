@extends('layouts.admin')

@section('title', 'Productos')

@php
    $hasFilters = $filters['search'] !== '' || $filters['empresa'] !== '' || $filters['marca'] !== '';
    $queryWithout = fn (string ...$keys) => request()->except($keys);
@endphp

@section('content')
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-4 border-b-2 border-admin-text pb-4.5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-1.5">
                <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Catálogo / Productos</p>
                <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">Productos</h1>
                <p class="text-sm text-admin-text-secondary">Los productos se sincronizan desde ZAZU. Aquí editas marca, textos comerciales e imágenes.</p>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-1.5">
                    <span class="size-2 shrink-0 rounded-full bg-admin-success"></span>
                    <span class="text-[13px] text-admin-text-secondary">
                        @if ($lastSync)
                            Sincronizado con ZAZU · {{ \Illuminate\Support\Carbon::parse($lastSync)->format('d/m/Y H:i') }}
                        @else
                            Aún no se sincroniza con ZAZU
                        @endif
                    </span>
                </div>
                <a href="{{ route('admin.productos.export', request()->query()) }}" class="flex items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">
                    <img src="{{ asset('images/admin/icon-download.svg') }}" class="size-4" alt="">
                    Exportar CSV
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-admin-success bg-admin-success-soft px-4 py-3 text-sm font-semibold text-admin-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-sm border border-admin-danger bg-admin-danger-soft px-4 py-3 text-sm font-semibold text-admin-danger">{{ session('error') }}</div>
        @endif

        <div class="flex flex-col rounded-sm border border-admin-border bg-admin-surface">
            <div class="flex items-center gap-6 overflow-x-auto border-b border-admin-border px-5">
                @foreach ([
                    ['key' => 'all', 'label' => 'Todos', 'count' => $counts['all'], 'pill' => 'bg-admin-hover text-admin-text-secondary'],
                    ['key' => 'review', 'label' => 'Requieren revisión', 'count' => $counts['review'], 'pill' => 'bg-admin-warning-soft text-admin-warning'],
                    ['key' => 'out_of_stock', 'label' => 'Sin stock', 'count' => $counts['out_of_stock'], 'pill' => 'bg-admin-hover text-admin-text-secondary'],
                ] as $t)
                    <a href="{{ route('admin.productos.index', $queryWithout('tab', 'page') + ($t['key'] === 'all' ? [] : ['tab' => $t['key']])) }}"
                       class="flex shrink-0 items-center gap-2 border-b-2 py-3.5 {{ $tab === $t['key'] ? 'border-admin-accent' : 'border-transparent' }}">
                        <span class="text-sm {{ $tab === $t['key'] ? 'font-bold text-admin-text' : 'font-medium text-admin-text-secondary' }}">{{ $t['label'] }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $t['pill'] }}">{{ $t['count'] }}</span>
                    </a>
                @endforeach
            </div>

            <form action="{{ route('admin.productos.index') }}" method="GET" class="flex flex-col gap-3 px-5 py-4 lg:flex-row lg:items-center">
                @if ($tab !== 'all')
                    <input type="hidden" name="tab" value="{{ $tab }}">
                @endif
                <div class="flex h-10 flex-1 items-center gap-2 rounded-sm border border-admin-border-strong bg-admin-surface px-3">
                    <button type="submit" class="shrink-0" aria-label="Buscar">
                        <img src="{{ asset('images/admin/icon-search.svg') }}" class="size-4" alt="">
                    </button>
                    <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Buscar por nombre o SKU" autocomplete="off" class="w-full text-sm font-medium text-admin-text outline-none placeholder:text-admin-text-muted">
                </div>
                <select name="marca" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-45">
                    <option value="">Marca: todas</option>
                    @foreach ($marcas as $marca)
                        <option value="{{ $marca }}" @selected($filters['marca'] === $marca)>{{ $marca }}</option>
                    @endforeach
                </select>
                <select name="empresa" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-45">
                    <option value="">Empresa: todas</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa }}" @selected($filters['empresa'] === $empresa)>{{ $empresa }}</option>
                    @endforeach
                </select>
                <select name="estado" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-45">
                    <option value="">Estado: todos</option>
                    @foreach ($estadoLabels as $value => $label)
                        <option value="{{ $value }}" @selected($estado === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>

            <div class="flex flex-wrap items-center gap-2 px-5 pb-3">
                <span class="text-[13px] text-admin-text-muted">Filtros activos:</span>
                @if (! $hasFilters && $estado === '')
                    <span class="rounded-full bg-admin-accent-soft py-1 pl-2.5 pr-2 text-xs font-medium text-admin-accent">
                        Ninguno — se muestran los {{ $counts['all'] }} productos
                    </span>
                @else
                    @if ($filters['search'] !== '')
                        <a href="{{ route('admin.productos.index', $queryWithout('search', 'page')) }}" class="flex items-center gap-1.5 rounded-full bg-admin-accent-soft py-1 pl-2.5 pr-2 text-xs font-medium text-admin-accent">
                            Buscar: {{ $filters['search'] }} <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                    @if ($filters['marca'] !== '')
                        <a href="{{ route('admin.productos.index', $queryWithout('marca', 'page')) }}" class="flex items-center gap-1.5 rounded-full bg-admin-accent-soft py-1 pl-2.5 pr-2 text-xs font-medium text-admin-accent">
                            Marca: {{ $filters['marca'] }} <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                    @if ($filters['empresa'] !== '')
                        <a href="{{ route('admin.productos.index', $queryWithout('empresa', 'page')) }}" class="flex items-center gap-1.5 rounded-full bg-admin-accent-soft py-1 pl-2.5 pr-2 text-xs font-medium text-admin-accent">
                            Empresa: {{ $filters['empresa'] }} <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                    @if ($estado !== '')
                        <a href="{{ route('admin.productos.index', $queryWithout('estado', 'page')) }}" class="flex items-center gap-1.5 rounded-full bg-admin-accent-soft py-1 pl-2.5 pr-2 text-xs font-medium text-admin-accent">
                            Estado: {{ $estadoLabels[$estado] }} <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                @endif
            </div>

            <div class="overflow-x-auto">
                <div class="flex min-w-[1080px] items-center gap-0 border-y border-admin-border bg-admin-subtle px-4 py-2.5">
                    <div class="w-75 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Producto</div>
                    <div class="w-35 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Marca</div>
                    <div class="w-42.5 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Empresa</div>
                    <div class="w-24 text-right text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Precio</div>
                    <div class="w-24 text-right text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Stock</div>
                    <div class="w-37.5 pl-6 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Estado</div>
                    <div class="w-24 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Actualizado</div>
                    <div class="w-8"></div>
                </div>

                @forelse ($productos as $producto)
                    @php
                        $productImage = (string) $producto->imagen;
                        $productImageUrl = $productImage === '' ? null : (\Illuminate\Support\Str::startsWith($productImage, ['http://', 'https://', '/storage/']) ? $productImage : \Illuminate\Support\Facades\Storage::disk('public')->url($productImage));
                        $estadoTone = match ($producto->estado) {
                            'publicado' => 'success',
                            'sin_marca' => 'warning',
                            'precio_cero' => 'danger',
                            default => 'neutral',
                        };
                    @endphp
                    <div class="flex h-16 min-w-[1080px] items-center border-b border-admin-border px-4">
                        <div class="flex w-75 items-center gap-3">
                            <div class="grid size-10 shrink-0 place-items-center overflow-hidden bg-admin-hover">
                                @if ($productImageUrl)
                                    <img src="{{ $productImageUrl }}" alt="" class="size-full object-cover" loading="lazy">
                                @else
                                    <span class="text-[9px] font-extrabold text-admin-text-muted">IMG</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-admin-text">{{ $producto->name }}</p>
                                <p class="truncate text-xs text-admin-text-muted">{{ $producto->variant_count }} {{ \Illuminate\Support\Str::plural('variante', $producto->variant_count) }}</p>
                            </div>
                        </div>
                        <div class="w-35 text-sm {{ $producto->marca ? 'font-medium text-admin-text' : 'text-admin-text-muted' }}">{{ $producto->marca ?: 'Sin marca' }}</div>
                        <div class="w-42.5 text-[13px] text-admin-text-secondary">{{ $producto->empresa_nombre ?: 'Overshark' }}</div>
                        <div class="w-24 text-right text-sm font-medium {{ (float) $producto->min_price <= 0.0 ? 'text-admin-danger' : 'text-admin-text' }}">
                            S/ {{ number_format((float) $producto->min_price, 2) }}
                            @if ((float) $producto->min_price !== (float) $producto->max_price)
                                – S/ {{ number_format((float) $producto->max_price, 2) }}
                            @endif
                        </div>
                        <div class="w-24 text-right text-sm font-medium {{ (float) $producto->total_stock <= 0.0 ? 'text-admin-danger' : 'text-admin-text' }}">{{ number_format((float) $producto->total_stock) }}</div>
                        <div class="w-37.5 pl-6"><x-admin.badge :tone="$estadoTone" :label="$estadoLabels[$producto->estado]" /></div>
                        <div class="w-24 text-[13px] text-admin-text-secondary">{{ $producto->updated_at?->format('d/m/Y') ?? '—' }}</div>
                        <div class="flex w-8 justify-end">
                            <a href="{{ route('admin.productos.show', $producto->id) }}" class="rounded p-1 text-admin-text-secondary transition hover:bg-admin-hover" title="Ver variantes">
                                <img src="{{ asset('images/admin/icon-more.svg') }}" class="size-5" alt="Ver variantes">
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="flex min-w-[1080px] items-center justify-center px-4 py-16 text-sm text-admin-text-muted">
                        No hay productos que coincidan con estos filtros.
                    </div>
                @endforelse
            </div>

            <div class="flex flex-col items-center justify-between gap-3 px-5 py-3.5 sm:flex-row">
                <p class="text-[13px] text-admin-text-secondary">
                    @if ($productos->total() > 0)
                        Mostrando {{ $productos->firstItem() }}–{{ $productos->lastItem() }} de {{ $productos->total() }} productos
                    @else
                        Sin resultados
                    @endif
                </p>
                @if ($productos->lastPage() > 1)
                    <div class="flex items-center gap-2">
                        @if ($productos->onFirstPage())
                            <span class="flex items-center gap-2 border border-admin-border px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text-muted">
                                <img src="{{ asset('images/admin/icon-arrow-left.svg') }}" class="size-4 opacity-40" alt="">
                                Anterior
                            </span>
                        @else
                            <a href="{{ $productos->previousPageUrl() }}" class="flex items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">
                                <img src="{{ asset('images/admin/icon-arrow-left.svg') }}" class="size-4" alt="">
                                Anterior
                            </a>
                        @endif

                        @for ($p = 1; $p <= $productos->lastPage(); $p++)
                            <a href="{{ $productos->url($p) }}" class="grid size-9 place-items-center rounded text-[13px] font-bold {{ $p === $productos->currentPage() ? 'bg-admin-navy text-white' : 'bg-admin-surface text-admin-text-secondary' }}">{{ $p }}</a>
                        @endfor

                        @if ($productos->hasMorePages())
                            <a href="{{ $productos->nextPageUrl() }}" class="flex items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">
                                Siguiente
                                <img src="{{ asset('images/admin/icon-chevron-right.svg') }}" class="size-4" alt="">
                            </a>
                        @else
                            <span class="flex items-center gap-2 border border-admin-border px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text-muted">
                                Siguiente
                                <img src="{{ asset('images/admin/icon-chevron-right.svg') }}" class="size-4 opacity-40" alt="">
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
