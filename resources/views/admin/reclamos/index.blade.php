@extends('layouts.admin')

@section('title', 'Reclamos')

@php
    $badgeTone = fn (string $status) => match ($status) {
        \App\Models\LibroReclamacion::STATUS_PENDING => 'warning',
        \App\Models\LibroReclamacion::STATUS_REVIEWING => 'info',
        \App\Models\LibroReclamacion::STATUS_RESOLVED => 'success',
        default => 'neutral',
    };
@endphp

@section('content')
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-4 border-b-2 border-admin-text pb-4.5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-1.5">
                <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Ventas / Reclamos</p>
                <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">Libro de reclamaciones</h1>
                <p class="text-sm text-admin-text-secondary">Reclamos y quejas enviados desde la tienda. Plazo legal de respuesta: 15 días hábiles.</p>
            </div>
            <a href="{{ route('admin.reclamos.export', request()->query()) }}" class="flex shrink-0 items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">
                <img src="{{ asset('images/admin/icon-download.svg') }}" class="size-4" alt="">
                Exportar registro
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-admin-success bg-admin-success-soft px-4 py-3 text-sm font-semibold text-admin-success">{{ session('status') }}</div>
        @endif

        @if ($mostOverdue)
            <div class="flex flex-col gap-3 rounded-sm border border-admin-danger bg-admin-danger-soft px-4 py-3.5 sm:flex-row sm:items-center">
                <img src="{{ asset('images/admin/icon-alert.svg') }}" class="size-5 shrink-0" alt="">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-admin-text">
                        {{ $counts['vencidos'] }} {{ \Illuminate\Support\Str::plural('reclamo', $counts['vencidos']) }} {{ $counts['vencidos'] === 1 ? 'superó' : 'superaron' }} el plazo legal de respuesta
                    </p>
                    <p class="text-[13px] text-admin-text-secondary">
                        #{{ str_pad($mostOverdue->id, 6, '0', STR_PAD_LEFT) }} se registró el {{ $mostOverdue->created_at->format('d/m/Y') }}; el plazo venció el {{ $mostOverdue->legalDeadline()->format('d/m/Y') }}. Respóndelo cuanto antes para reducir el riesgo de una denuncia ante INDECOPI.
                    </p>
                </div>
                <a href="{{ route('admin.reclamos.show', $mostOverdue) }}" class="shrink-0 bg-admin-navy px-5 py-3 text-center text-xs font-extrabold uppercase tracking-[1px] text-white">Responder ahora</a>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="flex flex-col gap-1.5 rounded-sm border border-admin-border bg-admin-surface p-5">
                <p class="text-[11px] font-extrabold uppercase tracking-[1.5px] text-admin-text-secondary">Total registrados</p>
                <p class="font-admin-display text-[32px] font-black leading-10.5 text-admin-text sm:text-[40px]">{{ $counts['total'] }}</p>
                <p class="text-xs font-semibold text-admin-text-muted">Desde el inicio</p>
            </div>
            <div class="flex flex-col gap-1.5 rounded-sm border border-admin-border bg-admin-surface p-5">
                <p class="text-[11px] font-extrabold uppercase tracking-[1.5px] text-admin-text-secondary">Pendientes</p>
                <p class="font-admin-display text-[32px] font-black leading-10.5 text-admin-text sm:text-[40px]">{{ $counts['pendientes'] }}</p>
                <p class="text-xs font-semibold text-admin-text-muted">Sin respuesta enviada</p>
            </div>
            <div class="flex flex-col gap-1.5 rounded-sm border border-admin-border bg-admin-surface p-5">
                <p class="text-[11px] font-extrabold uppercase tracking-[1.5px] text-admin-text-secondary">Por vencer</p>
                <p class="font-admin-display text-[32px] font-black leading-10.5 text-admin-text sm:text-[40px]">{{ $counts['por_vencer'] }}</p>
                <p class="text-xs font-semibold text-admin-text-muted">Vencen en ≤ 3 días hábiles</p>
            </div>
            <div class="flex flex-col gap-1.5 rounded-sm border border-admin-danger bg-admin-surface p-5">
                <p class="text-[11px] font-extrabold uppercase tracking-[1.5px] text-admin-text-secondary">Vencidos</p>
                <p class="font-admin-display text-[32px] font-black leading-10.5 text-admin-danger sm:text-[40px]">{{ $counts['vencidos'] }}</p>
                <p class="text-xs font-semibold text-admin-danger">Fuera del plazo legal</p>
            </div>
        </div>

        <div class="flex flex-col rounded-sm border border-admin-border bg-admin-surface">
            <form action="{{ route('admin.reclamos.index') }}" method="GET" class="flex flex-col gap-3 px-5 py-4 lg:flex-row lg:items-center">
                <div class="flex h-10 flex-1 items-center gap-2 rounded-sm border border-admin-border-strong bg-admin-surface px-3">
                    <button type="submit" class="shrink-0" aria-label="Buscar">
                        <img src="{{ asset('images/admin/icon-search.svg') }}" class="size-4" alt="">
                    </button>
                    <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Buscar por código, consumidor o N° de pedido" autocomplete="off" class="w-full text-sm font-medium text-admin-text outline-none placeholder:text-admin-text-muted">
                </div>
                <select name="tipo" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-42.5">
                    <option value="">Tipo: todos</option>
                    <option value="reclamo" @selected($filters['tipo'] === 'reclamo')>Reclamo</option>
                    <option value="queja" @selected($filters['tipo'] === 'queja')>Queja</option>
                </select>
                <select name="estado" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-42.5">
                    <option value="">Estado: todos</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($filters['estado'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="plazo" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-42.5">
                    <option value="">Plazo: todos</option>
                    <option value="vencido" @selected($filters['plazo'] === 'vencido')>Vencido</option>
                    <option value="por_vencer" @selected($filters['plazo'] === 'por_vencer')>Por vencer</option>
                    <option value="vigente" @selected($filters['plazo'] === 'vigente')>Vigente</option>
                </select>
            </form>

            <div class="overflow-x-auto">
                <div class="flex min-w-270 items-center gap-0 border-y border-admin-border bg-admin-subtle px-4 py-2.5">
                    <div class="w-24 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Código</div>
                    <div class="w-52.5 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Consumidor</div>
                    <div class="w-27.5 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Tipo</div>
                    <div class="w-52 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Motivo y pedido</div>
                    <div class="w-52 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Plazo de respuesta</div>
                    <div class="w-30 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Estado</div>
                    <div class="w-31.5"></div>
                </div>

                @forelse ($reclamos as $reclamo)
                    <div class="flex min-w-270 items-center gap-0 border-b border-admin-border px-4 py-3.5 {{ $reclamo->isOverdue() ? 'bg-admin-danger-soft' : '' }}">
                        <div class="w-24 text-sm font-bold text-admin-text">#{{ str_pad($reclamo->id, 6, '0', STR_PAD_LEFT) }}</div>
                        <div class="w-52.5 pr-2">
                            <p class="truncate text-sm font-bold text-admin-text">{{ $reclamo->consumer_name }}</p>
                            <p class="truncate text-xs text-admin-text-muted">{{ $reclamo->maskedDocument() }} · {{ $reclamo->maskedEmail() }}</p>
                        </div>
                        <div class="w-27.5"><x-admin.badge :tone="$reclamo->claim_type === 'reclamo' ? 'danger' : 'info'" :label="ucfirst($reclamo->claim_type)" /></div>
                        <div class="w-52 pr-2">
                            <p class="truncate text-[13px] font-medium text-admin-text">{{ $reclamo->claim_description }}</p>
                            <p class="truncate text-xs text-admin-text-muted">
                                @if ($reclamo->order_number)
                                    Pedido {{ $reclamo->order_number }} · {{ $reclamo->receipt_type }} {{ $reclamo->purchase_date?->format('d/m/Y') }}
                                @else
                                    Sin pedido asociado
                                @endif
                            </p>
                        </div>
                        <div class="w-52 pr-2">
                            @if ($reclamo->isAnswered())
                                <div class="flex items-center gap-1.5">
                                    <img src="{{ asset('images/admin/icon-check.svg') }}" class="size-3.5 shrink-0" alt="">
                                    <p class="text-[13px] font-bold {{ $reclamo->wasAnsweredOnTime() ? 'text-admin-success' : 'text-admin-warning' }}">{{ $reclamo->wasAnsweredOnTime() ? 'Respondido a tiempo' : 'Respondido fuera de plazo' }}</p>
                                </div>
                                <p class="text-xs text-admin-text-muted">Respondido: {{ $reclamo->responded_at->format('d/m/Y') }}</p>
                            @elseif ($reclamo->isOverdue())
                                <div class="flex items-center gap-1.5 text-admin-danger">
                                    <x-admin.clock-icon class="size-3.5 shrink-0" />
                                    <p class="text-[13px] font-bold">Vencido hace {{ $reclamo->businessDaysOverdue() }} días hábiles</p>
                                </div>
                                <p class="text-xs text-admin-text-muted">Plazo: {{ $reclamo->legalDeadline()->format('d/m/Y') }}</p>
                            @elseif ($reclamo->isDueSoon())
                                <div class="flex items-center gap-1.5 text-admin-warning">
                                    <x-admin.clock-icon class="size-3.5 shrink-0" />
                                    <p class="text-[13px] font-bold">Vence en {{ $reclamo->businessDaysUntilDeadline() }} días hábiles</p>
                                </div>
                                <p class="text-xs text-admin-text-muted">Plazo: {{ $reclamo->legalDeadline()->format('d/m/Y') }}</p>
                            @else
                                <p class="text-[13px] font-medium text-admin-text-secondary">Plazo: {{ $reclamo->legalDeadline()->format('d/m/Y') }}</p>
                            @endif
                        </div>
                        <div class="w-30"><x-admin.badge :tone="$badgeTone($reclamo->status)" :label="$reclamo->statusLabel()" /></div>
                        <div class="flex w-31.5 justify-end">
                            @if ($reclamo->status === \App\Models\LibroReclamacion::STATUS_RESOLVED)
                                <a href="{{ route('admin.reclamos.show', $reclamo) }}" class="px-4 py-2.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-accent">Ver</a>
                            @else
                                <a href="{{ route('admin.reclamos.show', $reclamo) }}" class="border border-admin-border-strong bg-admin-surface px-4 py-2.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Responder</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="flex min-w-270 flex-col items-center gap-1 px-4 py-16 text-center">
                        <p class="text-[15px] font-bold text-admin-text">Aún no hay reclamos registrados</p>
                        <p class="text-[13px] text-admin-text-secondary">Cuando un cliente envíe el formulario, aparecerá en esta tabla.</p>
                    </div>
                @endforelse
            </div>

            <div class="flex flex-col items-center justify-between gap-3 px-5 py-3.5 sm:flex-row">
                <p class="text-[13px] text-admin-text-secondary">
                    @if ($reclamos->total() > 0)
                        Mostrando {{ $reclamos->firstItem() }}–{{ $reclamos->lastItem() }} de {{ $reclamos->total() }} reclamos
                    @else
                        Sin resultados
                    @endif
                </p>
                <p class="text-xs text-admin-text-muted">Datos personales enmascarados en el listado; completos solo en el detalle.</p>
            </div>

            @if ($reclamos->lastPage() > 1)
                <div class="flex items-center justify-center gap-2 border-t border-admin-border px-5 py-3.5">
                    @for ($p = 1; $p <= $reclamos->lastPage(); $p++)
                        <a href="{{ $reclamos->url($p) }}" class="grid size-9 place-items-center rounded text-[13px] font-bold {{ $p === $reclamos->currentPage() ? 'bg-admin-navy text-white' : 'bg-admin-surface text-admin-text-secondary' }}">{{ $p }}</a>
                    @endfor
                </div>
            @endif
        </div>
    </div>
@endsection
