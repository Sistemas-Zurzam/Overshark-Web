@extends('layouts.admin')

@section('title', trim($cliente->name.' '.$cliente->apellidos))

@php
    $dt = 'text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary';
    $dd = 'text-sm text-admin-text';
@endphp

@section('content')
    <div class="flex flex-col gap-5">
        <a href="{{ route('admin.clientes.index') }}" class="flex items-center gap-1.5 text-[13px] font-medium text-admin-text-secondary">← Clientes</a>

        <div class="flex flex-col gap-1.5 border-b-2 border-admin-text pb-4.5">
            <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[40px]">{{ trim($cliente->name.' '.$cliente->apellidos) }}</h1>
            <p class="text-sm text-admin-text-secondary">Cliente desde el {{ $cliente->created_at?->format('d/m/Y') }} · {{ $orders->count() }} {{ $orders->count() === 1 ? 'pedido' : 'pedidos' }} · S/ {{ number_format($total, 2) }} en total</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <section class="rounded-sm border border-admin-border bg-admin-surface p-6 lg:col-span-1">
                <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Datos</h2>
                <dl class="mt-4 flex flex-col gap-3">
                    <div><dt class="{{ $dt }}">Documento</dt><dd class="{{ $dd }}">{{ $cliente->tipoDocumento?->name }} {{ $cliente->documento_identidad ?: '—' }}</dd></div>
                    <div><dt class="{{ $dt }}">Celular</dt><dd class="{{ $dd }}">{{ $cliente->cel ?: '—' }}</dd></div>
                    <div><dt class="{{ $dt }}">Correo</dt><dd class="{{ $dd }}">{{ $cliente->email ?: '—' }}</dd></div>
                    <div><dt class="{{ $dt }}">Cuenta en la web</dt><dd class="{{ $dd }}">{{ $account ? 'Sí'.($account->email_verified_at ? ' (correo verificado)' : ' (correo sin verificar)') : 'No tiene' }}</dd></div>
                    @if ($cliente->etiqueta)
                        <div><dt class="{{ $dt }}">Etiqueta</dt><dd class="{{ $dd }}">{{ $cliente->etiqueta }}</dd></div>
                    @endif
                </dl>
                @if ($cliente->email)
                    <a href="mailto:{{ $cliente->email }}" class="mt-5 inline-flex border border-admin-border-strong px-4 py-2.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Escribir por correo</a>
                @endif
            </section>

            <section class="overflow-x-auto rounded-sm border border-admin-border bg-admin-surface lg:col-span-2">
                <h2 class="px-6 pt-6 font-admin-display text-xl font-black uppercase leading-none text-admin-text">Pedidos</h2>
                <table class="mt-4 w-full min-w-[520px]">
                    <thead class="border-y border-admin-border bg-admin-subtle"><tr>
                        @foreach (['Pedido', 'Fecha', 'Estado', 'Total'] as $column)<th class="px-4 py-3 text-left {{ $dt }}">{{ $column }}</th>@endforeach
                    </tr></thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="border-b border-admin-border last:border-b-0 hover:bg-admin-hover">
                                <td class="px-4 py-3 text-sm"><a href="{{ route('admin.pedidos.show', $order) }}" class="font-bold text-admin-accent hover:underline">{{ $order->displayNumber() ?: $order->code() }}</a></td>
                                <td class="px-4 py-3 text-sm">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm">{{ $order->statusName() ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">S/ {{ number_format((float) $order->precio_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-admin-text-muted">Este cliente aún no tiene pedidos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
@endsection
