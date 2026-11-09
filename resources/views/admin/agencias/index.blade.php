@extends('layouts.admin')

@section('title', 'Agencias Shalom')

@php
    $th = 'px-3 py-3 text-left text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary';
    $td = 'px-3 py-3 text-sm text-admin-text';
@endphp

@section('content')
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-1.5 border-b-2 border-admin-text pb-4.5">
            <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Configuración / Envíos</p>
            <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">Agencias Shalom</h1>
            <p class="max-w-160 text-sm text-admin-text-secondary">
                Agencias que la clienta puede elegir al comprar con envío a agencia. La lista viene de Zazu y se actualiza sola en cada
                sincronización, por eso aquí solo se consulta: si una agencia está mal o falta, se corrige en Zazu.
                El precio del envío y del separo se cambia en <a href="{{ route('admin.contenido.edit') }}" class="font-bold text-admin-accent hover:underline">Contenido de la tienda</a>.
            </p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-sm border border-admin-border bg-admin-surface p-4">
                <p class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Agencias</p>
                <p class="mt-1 font-admin-display text-[32px] font-black leading-none text-admin-text">{{ $total }}</p>
            </div>
            <div class="rounded-sm border border-admin-border bg-admin-surface p-4">
                <p class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Departamentos</p>
                <p class="mt-1 font-admin-display text-[32px] font-black leading-none text-admin-text">{{ $departments->count() }}</p>
            </div>
            <div class="col-span-2 rounded-sm border border-admin-border bg-admin-surface p-4 sm:col-span-1">
                <p class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Última sincronización</p>
                <p class="mt-1 text-sm font-bold text-admin-text">{{ $lastSync ? \Illuminate\Support\Carbon::parse($lastSync)->setTimezone(\App\Models\Admin\Promocion::TIMEZONE)->format('d/m/Y H:i') : 'Nunca' }}</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.agencias.index') }}" class="flex flex-col gap-2 sm:flex-row">
            <input type="search" name="q" value="{{ $search }}" placeholder="Buscar agencia, dirección, distrito o código…" class="h-10 w-full max-w-md rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm outline-none">
            <select name="departamento" onchange="this.form.submit()" class="h-10 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm outline-none">
                <option value="">Todos los departamentos</option>
                @foreach ($departments as $name)
                    <option value="{{ $name }}" @selected($department === $name)>{{ $name }}</option>
                @endforeach
            </select>
            <button type="submit" class="h-10 bg-admin-navy px-4 text-xs font-extrabold uppercase tracking-[1px] text-white">Buscar</button>
        </form>

        <section class="overflow-x-auto rounded-sm border border-admin-border bg-admin-surface">
            <table class="w-full min-w-[820px]">
                <thead class="border-b border-admin-border bg-admin-subtle"><tr>
                    @foreach (['Código', 'Agencia', 'Dirección', 'Distrito', 'Provincia', 'Departamento'] as $column)<th class="{{ $th }}">{{ $column }}</th>@endforeach
                </tr></thead>
                <tbody>
                    @forelse ($agencies as $agency)
                        <tr class="border-b border-admin-border last:border-b-0 hover:bg-admin-hover">
                            <td class="{{ $td }}">{{ $agency->ter_id }}</td>
                            <td class="{{ $td }} font-bold">{{ $agency->lugar_over ?: '—' }}</td>
                            <td class="{{ $td }}">{{ $agency->direccion ?: '—' }}</td>
                            <td class="{{ $td }}">{{ $agency->zona ?: '—' }}</td>
                            <td class="{{ $td }}">{{ $agency->provincia ?: '—' }}</td>
                            <td class="{{ $td }}">{{ $agency->departamento ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-14 text-center text-sm text-admin-text-muted">{{ $total === 0 ? 'Aún no hay agencias: se cargan al sincronizar con Zazu.' : 'Ninguna agencia coincide con la búsqueda.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        {{ $agencies->links() }}
    </div>
@endsection
