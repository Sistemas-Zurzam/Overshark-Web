@extends('layouts.admin')

@section('title', 'Clientes')

@php
    $th = 'px-3 py-3 text-left text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary';
    $td = 'px-3 py-3 text-sm text-admin-text';
    $tabLink = fn (string $key) => route('admin.clientes.index', array_filter(['tab' => $key === 'cuentas' ? 'cuentas' : null, 'q' => $search]));
@endphp

@section('content')
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-1.5 border-b-2 border-admin-text pb-4.5">
            <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Ventas / Clientes</p>
            <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">Clientes</h1>
            <p class="max-w-160 text-sm text-admin-text-secondary">Quienes compraron en la tienda y las cuentas registradas en la web. Los datos se generan solos al comprar o al crear una cuenta.</p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <nav class="flex gap-2" aria-label="Tipo de cliente">
                <a href="{{ $tabLink('compradores') }}" @class(['px-4 py-2.5 text-xs font-extrabold uppercase tracking-[1px]', 'bg-admin-navy text-white' => $tab === 'compradores', 'border border-admin-border-strong text-admin-text' => $tab !== 'compradores'])>Compradores ({{ $buyerCount }})</a>
                <a href="{{ $tabLink('cuentas') }}" @class(['px-4 py-2.5 text-xs font-extrabold uppercase tracking-[1px]', 'bg-admin-navy text-white' => $tab === 'cuentas', 'border border-admin-border-strong text-admin-text' => $tab !== 'cuentas'])>Cuentas web ({{ $accountCount }})</a>
            </nav>
            <form method="GET" action="{{ route('admin.clientes.index') }}" class="flex gap-2">
                @if ($tab === 'cuentas') <input type="hidden" name="tab" value="cuentas"> @endif
                <input type="search" name="q" value="{{ $search }}" placeholder="Buscar nombre, documento, correo o celular…" class="h-10 w-72 max-w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm outline-none">
                <button type="submit" class="bg-admin-navy px-4 text-xs font-extrabold uppercase tracking-[1px] text-white">Buscar</button>
            </form>
        </div>

        <section class="overflow-x-auto rounded-sm border border-admin-border bg-admin-surface">
            @if ($tab === 'compradores')
                <table class="w-full min-w-[820px]">
                    <thead class="border-b border-admin-border bg-admin-subtle"><tr>
                        @foreach (['Nombre', 'Documento', 'Celular', 'Correo', 'Pedidos', 'Comprado', 'Registro'] as $column)<th class="{{ $th }}">{{ $column }}</th>@endforeach
                    </tr></thead>
                    <tbody>
                        @forelse ($buyers as $buyer)
                            <tr class="border-b border-admin-border last:border-b-0 hover:bg-admin-hover">
                                <td class="{{ $td }}"><a href="{{ route('admin.clientes.show', $buyer) }}" class="font-bold text-admin-accent hover:underline">{{ trim($buyer->name.' '.$buyer->apellidos) }}</a></td>
                                <td class="{{ $td }}">{{ $buyer->documento_identidad ?: '—' }}</td>
                                <td class="{{ $td }}">{{ $buyer->cel ?: '—' }}</td>
                                <td class="{{ $td }}">{{ $buyer->email ?: '—' }}</td>
                                <td class="{{ $td }}">{{ $buyer->orders_count }}</td>
                                <td class="{{ $td }}">S/ {{ number_format((float) $buyer->orders_sum_precio_total, 2) }}</td>
                                <td class="{{ $td }}">{{ $buyer->created_at?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-14 text-center text-sm text-admin-text-muted">{{ $search !== '' ? 'Ningún comprador coincide con la búsqueda.' : 'Aún no hay compradores registrados.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="w-full min-w-[820px]">
                    <thead class="border-b border-admin-border bg-admin-subtle"><tr>
                        @foreach (['Nombre', 'Correo', 'Celular', 'Correo verificado', 'Pedidos', 'Registro'] as $column)<th class="{{ $th }}">{{ $column }}</th>@endforeach
                    </tr></thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            <tr class="border-b border-admin-border last:border-b-0 hover:bg-admin-hover">
                                <td class="{{ $td }} font-bold">{{ $account->fullName() ?: '—' }}</td>
                                <td class="{{ $td }}">{{ $account->email }}</td>
                                <td class="{{ $td }}">{{ $account->phone ?: '—' }}</td>
                                <td class="{{ $td }}">{{ $account->email_verified_at ? 'Sí' : 'Pendiente' }}</td>
                                <td class="{{ $td }}">{{ $account->orders_count }}</td>
                                <td class="{{ $td }}">{{ $account->created_at?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-14 text-center text-sm text-admin-text-muted">{{ $search !== '' ? 'Ninguna cuenta coincide con la búsqueda.' : 'Aún no hay cuentas registradas en la web.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </section>

        {{ ($buyers ?? $accounts)->links() }}
    </div>
@endsection
