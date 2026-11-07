@extends('layouts.admin')

@section('title', 'Asignar productos a bodega')

@section('content')
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-1.5 border-b-2 border-admin-text pb-4.5">
            <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">
                <a href="{{ route('admin.bodegas.index') }}" class="hover:underline">Catálogo / Bodegas</a> / Asignar
            </p>
            <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">Asignar productos</h1>
            <p class="max-w-160 text-sm text-admin-text-secondary">Elige los productos sin bodega y envíalos a una ubicación para que dejen de estar invisibles en el stock.</p>
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-admin-success bg-admin-success-soft px-4 py-3 text-sm font-semibold text-admin-success">{{ session('status') }}</div>
        @endif
        @error('groups')
            <div class="rounded-sm border border-admin-danger bg-admin-danger-soft px-4 py-3 text-sm font-semibold text-admin-danger">{{ $message }}</div>
        @enderror

        <form action="{{ route('admin.bodegas.assign') }}" method="GET" class="flex h-10 w-full max-w-100 items-center gap-2 rounded-sm border border-admin-border-strong bg-admin-surface px-3">
            <button type="submit" class="shrink-0" aria-label="Buscar">
                <img src="{{ asset('images/admin/icon-search.svg') }}" class="size-4" alt="">
            </button>
            <input type="search" name="search" value="{{ $search }}" placeholder="Buscar producto" autocomplete="off" class="w-full text-sm font-medium text-admin-text outline-none placeholder:text-admin-text-muted">
        </form>

        @if ($groups->isEmpty())
            <div class="rounded-sm border border-admin-border bg-admin-surface p-8 text-center text-sm text-admin-text-secondary">
                No hay productos sin bodega asignada.
            </div>
        @else
            <form action="{{ route('admin.bodegas.assign.store') }}" method="POST" class="flex flex-col gap-4">
                @csrf

                <div class="flex flex-col rounded-sm border border-admin-border bg-admin-surface">
                    <div class="flex items-center gap-3 border-b border-admin-border bg-admin-subtle px-4 py-2.5">
                        <input type="checkbox" data-assign-select-all class="size-4">
                        <span class="text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Producto</span>
                        <span class="ml-auto w-20 text-right text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Variantes</span>
                        <span class="w-24 text-right text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Unidades</span>
                    </div>
                    @foreach ($groups as $group)
                        <label class="flex items-center gap-3 border-b border-admin-border px-4 py-3 last:border-b-0 hover:bg-admin-hover">
                            <input type="checkbox" name="groups[]" value="{{ $group->name }}|{{ $group->zazu_company_id }}" data-assign-checkbox class="size-4">
                            <span class="flex-1 truncate text-sm font-semibold text-admin-text">{{ $group->name }}</span>
                            <span class="truncate text-xs text-admin-text-muted">{{ $group->empresa_nombre ?: '—' }}</span>
                            <span class="w-20 text-right text-sm text-admin-text">{{ $group->variant_count }}</span>
                            <span class="w-24 text-right text-sm font-bold text-admin-text">{{ number_format((float) $group->total_stock) }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="flex flex-col gap-3 rounded-sm border border-admin-border bg-admin-surface p-5 sm:flex-row sm:items-center sm:justify-between">
                    <label class="flex items-center gap-3">
                        <span class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Enviar a</span>
                        <select name="bodega_id" required class="h-10 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text outline-none">
                            <option value="">Selecciona una bodega</option>
                            @foreach ($bodegas as $bodega)
                                <option value="{{ $bodega->id }}">{{ $bodega->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">Asignar seleccionados</button>
                </div>
            </form>
        @endif
    </div>

    <script>
        document.querySelector('[data-assign-select-all]')?.addEventListener('change', (event) => {
            document.querySelectorAll('[data-assign-checkbox]').forEach((checkbox) => {
                checkbox.checked = event.target.checked;
            });
        });
    </script>
@endsection
