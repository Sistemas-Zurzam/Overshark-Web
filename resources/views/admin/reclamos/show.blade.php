@extends('layouts.admin')

@section('title', 'Reclamo #'.str_pad($reclamo->id, 6, '0', STR_PAD_LEFT))

@php
    $badgeTone = match ($reclamo->status) {
        \App\Models\LibroReclamacion::STATUS_PENDING => 'warning',
        \App\Models\LibroReclamacion::STATUS_REVIEWING => 'info',
        \App\Models\LibroReclamacion::STATUS_RESOLVED => 'success',
        default => 'neutral',
    };
    $mailtoSubject = rawurlencode('Respuesta a tu '.$reclamo->claim_type.' #'.str_pad($reclamo->id, 6, '0', STR_PAD_LEFT));
@endphp

@section('content')
    <div class="flex flex-col gap-5">
        <a href="{{ route('admin.reclamos.index') }}" class="flex items-center gap-1.5 text-[13px] font-medium text-admin-text-secondary">
            <svg class="size-4" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M8 12.6667L3.33333 8L8 3.33333" stroke="currentColor" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12.6667 8H3.33333" stroke="currentColor" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Reclamos
        </a>

        <div class="flex flex-col gap-4 border-b-2 border-admin-text pb-4.5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2.5">
                    <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[40px]">#{{ str_pad($reclamo->id, 6, '0', STR_PAD_LEFT) }}</h1>
                    <x-admin.badge :tone="$reclamo->claim_type === 'reclamo' ? 'danger' : 'info'" :label="ucfirst($reclamo->claim_type)" />
                    <x-admin.badge :tone="$badgeTone" :label="$reclamo->statusLabel()" />
                </div>
                <p class="text-sm text-admin-text-secondary">{{ $reclamo->consumer_name }} · Registrado el {{ $reclamo->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <a href="mailto:{{ $reclamo->email }}?subject={{ $mailtoSubject }}" class="flex shrink-0 items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">
                Responder por correo
            </a>
        </div>

        @if ($reclamo->isOverdue())
            <div class="flex items-center gap-3 rounded-sm border border-admin-danger bg-admin-danger-soft px-4 py-3.5">
                <img src="{{ asset('images/admin/icon-alert.svg') }}" class="size-5 shrink-0" alt="">
                <p class="text-sm font-bold text-admin-text">Vencido hace {{ $reclamo->businessDaysOverdue() }} días hábiles · plazo legal: {{ $reclamo->legalDeadline()->format('d/m/Y') }}</p>
            </div>
        @elseif ($reclamo->isDueSoon())
            <div class="flex items-center gap-3 rounded-sm border border-admin-warning bg-admin-warning-soft px-4 py-3.5">
                <x-admin.clock-icon class="size-5 shrink-0 text-admin-warning" />
                <p class="text-sm font-bold text-admin-text">Vence en {{ $reclamo->businessDaysUntilDeadline() }} días hábiles · plazo legal: {{ $reclamo->legalDeadline()->format('d/m/Y') }}</p>
            </div>
        @elseif ($reclamo->isAnswered())
            <div class="flex items-center gap-3 rounded-sm border border-admin-success bg-admin-success-soft px-4 py-3.5">
                <img src="{{ asset('images/admin/icon-check.svg') }}" class="size-4 shrink-0" alt="">
                <p class="text-sm font-bold text-admin-text">
                    {{ $reclamo->wasAnsweredOnTime() ? 'Respondido a tiempo' : 'Respondido fuera de plazo' }} el {{ $reclamo->responded_at->format('d/m/Y H:i') }}
                </p>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="flex flex-col gap-4 lg:col-span-2">
                <div class="rounded-sm border border-admin-border bg-admin-surface p-6">
                    <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Consumidor</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Nombre</dt><dd class="text-sm text-admin-text">{{ $reclamo->consumer_name }}</dd></div>
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Documento</dt><dd class="text-sm text-admin-text">{{ $reclamo->document_type }} {{ $reclamo->document_number }}</dd></div>
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Correo</dt><dd class="text-sm text-admin-text">{{ $reclamo->email }}</dd></div>
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Teléfono</dt><dd class="text-sm text-admin-text">{{ $reclamo->phone }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Domicilio</dt><dd class="text-sm text-admin-text">{{ $reclamo->address }}</dd></div>
                    </dl>

                    @if ($reclamo->is_minor)
                        <div class="mt-4 rounded-sm bg-admin-subtle p-4">
                            <p class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Representante del menor</p>
                            <dl class="mt-2 grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                                <div><dt class="text-xs text-admin-text-muted">Nombre</dt><dd class="text-sm text-admin-text">{{ $reclamo->guardian_name }}</dd></div>
                                <div><dt class="text-xs text-admin-text-muted">Documento</dt><dd class="text-sm text-admin-text">{{ $reclamo->guardian_document_type }} {{ $reclamo->guardian_document_number }}</dd></div>
                            </dl>
                        </div>
                    @endif
                </div>

                <div class="rounded-sm border border-admin-border bg-admin-surface p-6">
                    <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Compra</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">N° de pedido</dt><dd class="text-sm text-admin-text">{{ $reclamo->order_number ?: '—' }}</dd></div>
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Comprobante</dt><dd class="text-sm text-admin-text">{{ $reclamo->receipt_type }} · {{ $reclamo->purchase_date?->format('d/m/Y') }}</dd></div>
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Canal de compra</dt><dd class="text-sm text-admin-text">{{ $reclamo->purchase_channel }}</dd></div>
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Monto reclamado</dt><dd class="text-sm text-admin-text">{{ $reclamo->claimed_amount !== null ? 'S/ '.number_format((float) $reclamo->claimed_amount, 2) : '—' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Producto o servicio</dt><dd class="text-sm text-admin-text">{{ $reclamo->order_product }}</dd></div>
                        @if ($reclamo->order_description)
                            <div class="sm:col-span-2"><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Detalle del pedido</dt><dd class="text-sm text-admin-text">{{ $reclamo->order_description }}</dd></div>
                        @endif
                    </dl>
                </div>

                <div class="rounded-sm border border-admin-border bg-admin-surface p-6">
                    <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Detalle del {{ $reclamo->claim_type }}</h2>
                    <dl class="mt-4 flex flex-col gap-3">
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Producto o servicio reclamado</dt><dd class="text-sm text-admin-text">{{ $reclamo->claim_product }}</dd></div>
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Descripción</dt><dd class="text-sm leading-6 text-admin-text">{{ $reclamo->claim_description }}</dd></div>
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Solución esperada</dt><dd class="text-sm leading-6 text-admin-text">{{ $reclamo->expected_solution }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <form action="{{ route('admin.reclamos.status', $reclamo) }}" method="POST" class="flex flex-col gap-3 rounded-sm border border-admin-border bg-admin-surface p-6">
                    @csrf
                    @method('PATCH')
                    <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Estado</h2>
                    <label class="flex flex-col gap-1.5">
                        <span class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Actualizar a</span>
                        <select name="status" class="h-10 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text outline-none">
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($reclamo->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <p class="text-xs text-admin-text-muted">Al marcar como "Atendido" se registra la fecha y hora actual como fecha de respuesta.</p>
                    <button type="submit" class="bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">Guardar estado</button>
                </form>

                <div class="rounded-sm border border-admin-border bg-admin-surface p-6">
                    <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Plazo legal</h2>
                    <dl class="mt-4 flex flex-col gap-3">
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Registrado</dt><dd class="text-sm text-admin-text">{{ $reclamo->created_at->format('d/m/Y H:i') }}</dd></div>
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Vence</dt><dd class="text-sm text-admin-text">{{ $reclamo->legalDeadline()->format('d/m/Y') }}</dd></div>
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Respondido</dt><dd class="text-sm text-admin-text">{{ $reclamo->responded_at?->format('d/m/Y H:i') ?? 'Aún no' }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
