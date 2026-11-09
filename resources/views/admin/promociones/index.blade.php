@extends('layouts.admin')

@section('title', 'Promociones')

@php
    $tone = ['activa' => 'success', 'programada' => 'info', 'finalizada' => 'neutral', 'inactiva' => 'warning'];
    $scopeLabel = ['producto' => 'Prendas', 'marca' => 'Marcas', 'categoria' => 'Categorías'];
    $categoryNames = \App\Models\Admin\Categoria::query()->pluck('name', 'id');
@endphp

@section('content')
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-4 border-b-2 border-admin-text pb-4.5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-1.5">
                <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Catálogo / Promociones</p>
                <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">Promociones</h1>
                <p class="max-w-160 text-sm text-admin-text-secondary">
                    Ofertas por horas: un descuento sobre prendas, una marca o una categoría que solo vale entre dos momentos (hora de Lima).
                    Empiezan y terminan solas; no cambian el precio de Zazu. Si varias aplican a una prenda, gana la más barata; los combos no entran.
                </p>
            </div>
            <a href="{{ route('admin.promociones.create') }}" class="flex shrink-0 items-center gap-2 bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">
                <img src="{{ asset('images/admin/icon-plus-white.svg') }}" class="size-4" alt="">
                Nueva promoción
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-admin-success bg-admin-success-soft px-4 py-3 text-sm font-semibold text-admin-success">{{ session('status') }}</div>
        @endif

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach (['activa' => 'Activas ahora', 'programada' => 'Programadas', 'finalizada' => 'Finalizadas', 'inactiva' => 'Pausadas'] as $key => $label)
                <div class="rounded-sm border border-admin-border bg-admin-surface p-4">
                    <p class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">{{ $label }}</p>
                    <p class="mt-1 font-admin-display text-[32px] font-black leading-none text-admin-text">{{ $counts[$key] ?? 0 }}</p>
                </div>
            @endforeach
        </div>

        <section class="rounded-sm border border-admin-border bg-admin-surface">
            @forelse ($promos as $promo)
                @php
                    $status = $promo->status();
                    $targets = collect($promo->scope_values)->map(fn ($value) => $promo->scope_type === 'categoria' ? ($categoryNames[(int) $value] ?? '#'.$value) : $value);
                @endphp
                <article class="flex flex-col gap-3 border-b border-admin-border px-5 py-4 last:border-b-0 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 font-bold text-admin-text">
                            {{ $promo->name }}
                            <x-admin.badge :tone="$tone[$status]" :label="$promo->statusLabel()" />
                            <span class="text-sm font-extrabold text-admin-danger">{{ $promo->discountLabel() }}</span>
                        </p>
                        <p class="mt-1 text-sm text-admin-text-secondary">
                            {{ $scopeLabel[$promo->scope_type] }}: <span class="font-semibold text-admin-text">{{ $targets->take(4)->implode(', ') }}@if ($targets->count() > 4) y {{ $targets->count() - 4 }} más @endif</span>
                        </p>
                        <p class="mt-1 text-xs text-admin-text-muted">
                            {{ $promo->local('starts_at')->format('d/m/Y H:i') }} → {{ $promo->local('ends_at')->format('d/m/Y H:i') }} (hora de Lima)
                            @if ($status === 'activa') · termina {{ $promo->ends_at->diffForHumans() }} @endif
                            @if ($status === 'programada') · empieza {{ $promo->starts_at->diffForHumans() }} @endif
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-4 text-sm font-bold">
                        <a href="{{ route('admin.promociones.edit', $promo) }}" class="text-admin-accent hover:underline">Editar</a>
                        @if ($status !== 'finalizada')
                            <form method="POST" action="{{ route('admin.promociones.toggle', $promo) }}">@csrf @method('PATCH')<button type="submit" class="text-admin-text-secondary hover:underline">{{ $promo->active ? 'Pausar' : 'Reanudar' }}</button></form>
                        @endif
                        <form method="POST" action="{{ route('admin.promociones.destroy', $promo) }}" onsubmit="return confirm('¿Eliminar esta promoción?')">@csrf @method('DELETE')<button type="submit" class="text-admin-danger hover:underline">Eliminar</button></form>
                    </div>
                </article>
            @empty
                <p class="px-5 py-8 text-sm text-admin-text-secondary">Aún no hay promociones. Crea una para activar una oferta relámpago en la tienda.</p>
            @endforelse
        </section>
    </div>
@endsection
