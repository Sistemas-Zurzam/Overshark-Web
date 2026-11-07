@extends('layouts.admin')

@section('title', 'Bodegas')

@section('content')
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-4 border-b-2 border-admin-text pb-4.5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-1.5">
                <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Catálogo / Bodegas</p>
                <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">Bodegas</h1>
                <p class="max-w-160 text-sm text-admin-text-secondary">Ubicaciones donde se guarda el stock. Asigna cada producto a una bodega para controlar disponibilidad.</p>
            </div>
            <button type="button" data-bodega-form-toggle class="flex shrink-0 items-center gap-2 bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">
                <img src="{{ asset('images/admin/icon-plus-white.svg') }}" class="size-4" alt="">
                Nueva bodega
            </button>
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-admin-success bg-admin-success-soft px-4 py-3 text-sm font-semibold text-admin-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-sm border border-admin-danger bg-admin-danger-soft px-4 py-3 text-sm font-semibold text-admin-danger">{{ session('error') }}</div>
        @endif

        <section data-bodega-form class="hidden flex flex-col gap-4 rounded-sm border border-admin-border bg-admin-surface p-6">
            <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Nueva bodega</h2>
            <form action="{{ route('admin.bodegas.store') }}" method="POST" class="grid gap-4 md:grid-cols-2">
                @csrf
                <label class="block">
                    <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Nombre</span>
                    <input type="text" name="name" required class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none" placeholder="Ej. Almacén Gamarra">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Descripción <span class="font-medium normal-case text-admin-text-muted">(opcional)</span></span>
                    <input type="text" name="subtitle" class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none" placeholder="Ej. Principal, Marca Bravos">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Dirección <span class="font-medium normal-case text-admin-text-muted">(opcional)</span></span>
                    <input type="text" name="address" class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Responsable <span class="font-medium normal-case text-admin-text-muted">(opcional)</span></span>
                    <input type="text" name="responsable" class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none">
                </label>
                <div class="flex items-center gap-3 md:col-span-2 md:justify-end">
                    <button type="button" data-bodega-form-toggle class="border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Cancelar</button>
                    <button type="submit" class="bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">Guardar bodega</button>
                </div>
            </form>
        </section>

        <div class="flex flex-col rounded-sm border border-admin-border bg-admin-surface">
            <form action="{{ route('admin.bodegas.index') }}" method="GET" class="flex flex-col gap-3 px-5 py-4 lg:flex-row lg:items-center">
                <div class="flex h-10 flex-1 items-center gap-2 rounded-sm border border-admin-border-strong bg-admin-surface px-3">
                    <button type="submit" class="shrink-0" aria-label="Buscar">
                        <img src="{{ asset('images/admin/icon-search.svg') }}" class="size-4" alt="">
                    </button>
                    <input type="search" name="search" value="{{ $search }}" placeholder="Buscar bodega, dirección o responsable" autocomplete="off" class="w-full text-sm font-medium text-admin-text outline-none placeholder:text-admin-text-muted">
                </div>
                <select name="estado" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-45">
                    <option value="">Estado: todas</option>
                    <option value="activas" @selected($estado === 'activas')>Activas</option>
                    <option value="inactivas" @selected($estado === 'inactivas')>Inactivas</option>
                </select>
            </form>

            <div class="overflow-x-auto">
                <div class="flex min-w-220 items-center gap-0 border-y border-admin-border bg-admin-subtle px-4 py-2.5">
                    <div class="w-55 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Bodega</div>
                    <div class="w-70 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Dirección</div>
                    <div class="w-40 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Responsable</div>
                    <div class="w-22.5 text-right text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Productos</div>
                    <div class="w-27.5 text-right text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Unidades</div>
                    <div class="w-27.5 pl-6 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Estado</div>
                    <div class="w-27.5"></div>
                </div>

                @foreach ($bodegas as $row)
                    @php($bodega = $row->model)
                    <div class="flex min-w-220 items-center gap-0 border-b border-admin-border px-4 py-3.5">
                        <div class="w-55 pr-2">
                            <p class="truncate text-sm font-bold text-admin-text">{{ $bodega->name }}</p>
                            <p class="truncate text-xs text-admin-text-muted">{{ $bodega->subtitle ?: '—' }}</p>
                        </div>
                        <div class="w-70 truncate pr-2 text-[13px] text-admin-text-secondary">{{ $bodega->address ?: '—' }}</div>
                        <div class="w-40 truncate pr-2 text-sm text-admin-text">{{ $bodega->responsable ?: '—' }}</div>
                        <div class="w-22.5 text-right text-sm font-bold text-admin-text">{{ $row->productCount }}</div>
                        <div class="w-27.5 text-right text-sm font-bold text-admin-text">{{ number_format($row->unitCount) }}</div>
                        <div class="w-27.5 pl-6"><x-admin.badge :tone="$bodega->is_active ? 'success' : 'neutral'" :label="$bodega->is_active ? 'Activa' : 'Inactiva'" /></div>
                        <div class="flex w-27.5 justify-end gap-2">
                            <button type="button" data-bodega-edit-toggle="bodega-edit-{{ $bodega->id }}" class="rounded p-1 text-admin-text-secondary hover:bg-admin-hover" title="Editar">
                                <img src="{{ asset('images/admin/icon-more.svg') }}" class="size-5" alt="Editar">
                            </button>
                        </div>
                    </div>
                    <div id="bodega-edit-{{ $bodega->id }}" class="hidden border-b border-admin-border bg-admin-subtle px-4 py-4">
                        <form action="{{ route('admin.bodegas.update', $bodega) }}" method="POST" class="grid gap-3 md:grid-cols-4">
                            @csrf @method('PATCH')
                            <input type="text" name="name" value="{{ $bodega->name }}" required class="h-9 rounded-sm border border-admin-border-strong bg-admin-surface px-2.5 text-sm text-admin-text outline-none" placeholder="Nombre">
                            <input type="text" name="subtitle" value="{{ $bodega->subtitle }}" class="h-9 rounded-sm border border-admin-border-strong bg-admin-surface px-2.5 text-sm text-admin-text outline-none" placeholder="Descripción">
                            <input type="text" name="address" value="{{ $bodega->address }}" class="h-9 rounded-sm border border-admin-border-strong bg-admin-surface px-2.5 text-sm text-admin-text outline-none" placeholder="Dirección">
                            <input type="text" name="responsable" value="{{ $bodega->responsable }}" class="h-9 rounded-sm border border-admin-border-strong bg-admin-surface px-2.5 text-sm text-admin-text outline-none" placeholder="Responsable">
                            <div class="flex items-center gap-2 md:col-span-4">
                                <button type="submit" class="bg-admin-navy px-4 py-2 text-xs font-extrabold uppercase tracking-[1px] text-white">Guardar</button>
                                <button type="button" data-bodega-edit-toggle="bodega-edit-{{ $bodega->id }}" class="border border-admin-border-strong bg-admin-surface px-4 py-2 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Cancelar</button>
                            </div>
                        </form>
                        <div class="mt-3 flex gap-2 border-t border-admin-border pt-3">
                            <form action="{{ route('admin.bodegas.toggle', $bodega) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="border border-admin-border-strong bg-admin-surface px-4 py-2 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">{{ $bodega->is_active ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                            <form action="{{ route('admin.bodegas.destroy', $bodega) }}" method="POST" onsubmit="return confirm('¿Eliminar esta bodega?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="border border-admin-danger px-4 py-2 text-xs font-extrabold uppercase tracking-[1px] text-admin-danger">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @endforeach

                <div class="flex min-w-220 items-center gap-0 px-4 py-3.5">
                    <div class="w-55 pr-2">
                        <p class="text-sm font-bold text-admin-text">Sin asignar</p>
                        <p class="text-xs text-admin-text-muted">Productos sin bodega</p>
                    </div>
                    <div class="w-70 pr-2 text-[13px] text-admin-text-secondary">—</div>
                    <div class="w-40 pr-2 text-sm text-admin-text">—</div>
                    <div class="w-22.5 text-right text-sm font-bold {{ $unassignedProductCount > 0 ? 'text-admin-warning' : 'text-admin-text' }}">{{ $unassignedProductCount }}</div>
                    <div class="w-27.5 text-right text-sm font-bold {{ $unassignedProductCount > 0 ? 'text-admin-warning' : 'text-admin-text' }}">{{ number_format($unassignedUnitCount) }}</div>
                    <div class="w-27.5 pl-6">
                        <x-admin.badge :tone="$unassignedProductCount > 0 ? 'warning' : 'success'" :label="$unassignedProductCount > 0 ? 'Revisar' : 'Al día'" />
                    </div>
                    <div class="flex w-27.5 justify-end">
                        @if ($unassignedProductCount > 0)
                            <a href="{{ route('admin.bodegas.assign') }}" class="border border-admin-border-strong bg-admin-surface px-4 py-2.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Asignar</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
