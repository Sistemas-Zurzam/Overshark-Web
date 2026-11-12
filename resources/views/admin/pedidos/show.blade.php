@extends('layouts.admin')

@section('title', 'Pedido '.$order->displayNumber())

@php
    $place = $order->distrito ? collect([$order->distrito->name, $order->distrito->provincia?->name, $order->distrito->provincia?->departamento?->name])->filter()->implode(', ') : null;
@endphp

@section('content')
    <a href="{{ route('admin.pedidos.index') }}" class="text-sm font-bold text-cyan-700 hover:underline">← Pedidos</a>
    <div class="mb-8 mt-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Pedido</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">{{ $order->displayNumber() }}</h1>
            <p class="mt-2 text-slate-500">{{ $order->created_at?->format('d/m/Y H:i') }} · {{ $order->statusOrder?->name }}</p>
            @if ($order->nota_venta)
                <p class="mt-1 text-sm font-bold text-slate-700">Nota de venta del CRM · pedido web {{ $order->number_zazu }}</p>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.pedidos.status', $order) }}" class="flex gap-2">
            @csrf @method('PATCH')
            <select name="status" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm">
                {{-- Los estados de pago los pone el sistema: si el pedido está en uno, se muestra sin poder elegirlo. --}}
                @if (! in_array($order->statusOrder?->name, $statuses, true))
                    <option value="" selected disabled>{{ $order->statusOrder?->name }} (automático)</option>
                @endif
                @foreach ($statuses as $statusName)
                    <option value="{{ $statusName }}" @selected($order->statusOrder?->name === $statusName)>{{ $statusName }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white hover:bg-cyan-600">Guardar estado</button>
        </form>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
            <h2 class="text-lg font-black text-slate-950">Prendas</h2>
            <ul class="mt-4 divide-y divide-slate-100">
                @foreach ($order->productos_json ?? [] as $line)
                    <li class="flex gap-4 py-3">
                        <img src="{{ $line['imagen'] ?? asset('images/default-hero-banner.png') }}" alt="" class="h-20 w-16 rounded-lg bg-slate-100 object-cover">
                        <div class="text-sm">
                            <p class="font-bold text-slate-950">{{ $line['producto'] ?? 'Producto' }} × {{ $line['cantidad'] ?? 1 }}</p>
                            @if (($line['tipo'] ?? '') === 'combo')
                                @foreach ($line['selecciones'] ?? [] as $selection)
                                    <p class="text-slate-500">{{ $selection['producto'] ?? '' }} · {{ $selection['color'] ?? '' }} · {{ $selection['talla'] ?? '' }}</p>
                                @endforeach
                            @else
                                <p class="text-slate-500">{{ $line['color'] ?? '' }} · Talla {{ $line['talla'] ?? '-' }}</p>
                            @endif
                            <p class="font-bold">S/ {{ number_format((float) ($line['precio'] ?? 0) * (int) ($line['cantidad'] ?? 1), 2) }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
            <dl class="mt-4 space-y-1 border-t border-slate-200 pt-4 text-sm">
                <div class="flex justify-between"><dt>Subtotal</dt><dd>S/ {{ number_format((float) $order->subtotal, 2) }}</dd></div>
                <div class="flex justify-between"><dt>Envío</dt><dd>S/ {{ number_format((float) $order->envio, 2) }}</dd></div>
                <div class="flex justify-between text-base font-black"><dt>Total</dt><dd>S/ {{ number_format((float) $order->precio_total, 2) }}</dd></div>
            </dl>
        </section>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Cliente y entrega</h2>
                <dl class="mt-3 space-y-2">
                    <div><dt class="text-xs font-bold uppercase text-slate-500">Cliente</dt><dd>{{ $order->cliente_name }} · {{ $order->cliente_doc_identidad }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase text-slate-500">Contacto</dt><dd>{{ $order->cliente_cel }} · {{ $order->email }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase text-slate-500">Entrega</dt><dd>{{ \App\Support\Shipping::deliveryLabel($order->entrega, $order->agencia, $order->direccion) }}</dd></div>
                    @if ($place)<div><dt class="text-xs font-bold uppercase text-slate-500">Distrito</dt><dd>{{ $place }}</dd></div>@endif
                    @if ($order->referencia)<div><dt class="text-xs font-bold uppercase text-slate-500">Referencia</dt><dd>{{ $order->referencia }}</dd></div>@endif
                    <div><dt class="text-xs font-bold uppercase text-slate-500">Comprobante</dt><dd>{{ ucfirst((string) $order->tipo_comprobante) }}@if ($order->ruc) · RUC {{ $order->ruc }} · {{ $order->razon_social }}@endif</dd></div>
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Pago</h2>
                <p class="mt-2">{{ $order->metodo_pago_nombre ?? '—' }}</p>
                @if ($order->comprobante_pago)
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($order->comprobante_pago) }}" target="_blank" rel="noopener" class="mt-3 block">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($order->comprobante_pago) }}" alt="Captura del pago" class="max-h-80 rounded-xl border border-slate-200">
                    </a>
                @elseif ($order->pago_referencia)
                    <p class="mt-2 text-emerald-700">Cobrado con tarjeta por Izipay · transacción {{ $order->pago_referencia }}</p>
                @elseif ((float) $order->pago_monto > 0)
                    <p class="mt-2 text-red-600">Sin captura de pago.</p>
                @endif
                @if ((float) $order->pago_monto > 0)
                    <p class="mt-2">Pagado en línea: <span class="font-bold">S/ {{ number_format((float) $order->pago_monto, 2) }}</span></p>
                @endif
                @if ($order->pago_qr_expira)
                    <p class="mt-2 text-amber-700">Esperando el pago con tarjeta · se cancela a las {{ $order->pago_qr_expira->format('d/m H:i') }} si no se paga</p>
                @endif
                @if ((float) $order->separo > 0)
                    <dl class="mt-3 space-y-1 border-t border-slate-200 pt-3">
                        <div class="flex justify-between"><dt>Separo (pagado en el checkout)</dt><dd class="font-bold">S/ {{ number_format((float) $order->separo, 2) }}</dd></div>
                        <div class="flex justify-between"><dt>Saldo a cobrar al entregar</dt><dd class="font-bold">S/ {{ number_format((float) $order->precio_total - (float) $order->separo, 2) }}</dd></div>
                    </dl>
                @endif
            </section>

            {{-- Después del pago (App\Services\Orders\OrderFulfillment): nota de venta, ERP, comprobante y correo. --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Después del pago</h2>
                @if ($order->tasks->isEmpty())
                    <p class="mt-2 text-slate-500">Empieza cuando el pago se confirme.</p>
                @else
                    <ol class="mt-3 space-y-3">
                        @foreach ($order->tasks->sortBy(fn ($task) => array_search($task->task, array_keys(\App\Services\Orders\OrderFulfillment::TASKS), true)) as $task)
                            <li class="flex items-start gap-3">
                                <span @class([
                                    'mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-xs font-bold',
                                    'bg-emerald-50 text-emerald-700' => $task->status === \App\Models\Admin\OrderTask::DONE,
                                    'bg-slate-100 text-slate-600' => $task->status === \App\Models\Admin\OrderTask::SKIPPED,
                                    'bg-amber-50 text-amber-700' => $task->status === \App\Models\Admin\OrderTask::PENDING,
                                    'bg-red-50 text-red-700' => $task->status === \App\Models\Admin\OrderTask::FAILED,
                                ])>{{ $task->status }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-slate-900">{{ \App\Services\Orders\OrderFulfillment::LABELS[$task->task] ?? $task->task }}</p>
                                    @if ($task->message)<p class="text-slate-500">{{ $task->message }}</p>@endif
                                    @if ($task->attempts > 1)<p class="text-xs text-slate-400">{{ $task->attempts }} intentos</p>@endif
                                    @if ($task->status === \App\Models\Admin\OrderTask::FAILED)
                                        <form method="POST" action="{{ route('admin.pedidos.tasks.retry', [$order, $task]) }}" class="mt-1">
                                            @csrf
                                            <button type="submit" class="text-xs font-bold text-blue-700 hover:underline">Reintentar</button>
                                        </form>
                                    @endif
                                    @if ($task->task === 'correo')
                                        <div class="mt-1 flex gap-4">
                                            <a href="{{ route('admin.pedidos.email.preview', $order) }}" target="_blank" rel="noopener" class="text-xs font-bold text-blue-700 hover:underline">Ver correo</a>
                                            @if ($task->status !== \App\Models\Admin\OrderTask::PENDING)
                                                <form method="POST" action="{{ route('admin.pedidos.email.resend', $order) }}">
                                                    @csrf
                                                    <button type="submit" class="text-xs font-bold text-blue-700 hover:underline">Reenviar al cliente</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </aside>
    </div>
@endsection
