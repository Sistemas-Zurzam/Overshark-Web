@extends('layouts.admin')

@section('title', 'Mensajes')

@php
    $queryWithout = fn (string ...$keys) => request()->except($keys);
    $relativeDate = function (\Illuminate\Support\Carbon $date) {
        if ($date->isToday()) {
            return 'Hoy, '.$date->format('H:i');
        }
        if ($date->isYesterday()) {
            return 'Ayer, '.$date->format('H:i');
        }

        return $date->format('d/m/Y');
    };
@endphp

@section('content')
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-4 border-b-2 border-admin-text pb-4.5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-1.5">
                <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Ventas / Mensajes</p>
                <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">Mensajes de contacto</h1>
                <p class="max-w-160 text-sm text-admin-text-secondary">Lo que escriben los clientes en el formulario de Contacto. Respóndeles por correo o teléfono y márcalos como atendidos.</p>
            </div>
            <a href="{{ route('web.contact') }}" target="_blank" class="flex shrink-0 items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Ver formulario de Contacto</a>
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-admin-success bg-admin-success-soft px-4 py-3 text-sm font-semibold text-admin-success">{{ session('status') }}</div>
        @endif

        <div class="flex flex-col rounded-sm border border-admin-border bg-admin-surface">
            <div class="flex items-center gap-6 overflow-x-auto border-b border-admin-border px-5">
                @foreach ([
                    ['key' => 'pendientes', 'label' => 'Pendientes', 'count' => $counts['pendientes'], 'pill' => 'bg-admin-warning-soft text-admin-warning'],
                    ['key' => 'atendidos', 'label' => 'Atendidos', 'count' => $counts['atendidos'], 'pill' => 'bg-admin-hover text-admin-text-secondary'],
                    ['key' => 'todos', 'label' => 'Todos', 'count' => $counts['todos'], 'pill' => 'bg-admin-hover text-admin-text-secondary'],
                ] as $t)
                    <a href="{{ route('admin.messages.index', $queryWithout('tab', 'page') + ['tab' => $t['key']]) }}"
                       class="flex shrink-0 items-center gap-2 border-b-2 py-3.5 {{ $tab === $t['key'] ? 'border-admin-accent' : 'border-transparent' }}">
                        <span class="text-sm {{ $tab === $t['key'] ? 'font-bold text-admin-text' : 'font-medium text-admin-text-secondary' }}">{{ $t['label'] }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $t['pill'] }}">{{ $t['count'] }}</span>
                    </a>
                @endforeach
            </div>

            <form action="{{ route('admin.messages.index') }}" method="GET" class="flex flex-col gap-3 px-5 py-4 lg:flex-row lg:items-center">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="flex h-10 flex-1 items-center gap-2 rounded-sm border border-admin-border-strong bg-admin-surface px-3">
                    <button type="submit" class="shrink-0" aria-label="Buscar">
                        <img src="{{ asset('images/admin/icon-search.svg') }}" class="size-4" alt="">
                    </button>
                    <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Buscar por nombre, correo o texto del mensaje" autocomplete="off" class="w-full text-sm font-medium text-admin-text outline-none placeholder:text-admin-text-muted">
                </div>
                <select name="periodo" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-45">
                    <option value="">Período: todos</option>
                    <option value="7" @selected($filters['periodo'] === '7')>Últimos 7 días</option>
                    <option value="30" @selected($filters['periodo'] === '30')>Últimos 30 días</option>
                    <option value="mes" @selected($filters['periodo'] === 'mes')>Este mes</option>
                </select>
            </form>

            <div class="overflow-x-auto">
                <div class="flex min-w-220 items-center gap-0 border-y border-admin-border bg-admin-subtle px-4 py-2.5">
                    <div class="w-55 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Remitente</div>
                    <div class="w-100.5 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Mensaje</div>
                    <div class="w-30 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Prefiere</div>
                    <div class="w-30 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Recibido</div>
                    <div class="w-27.5 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Estado</div>
                    <div class="w-26.5"></div>
                </div>

                @forelse ($messages as $message)
                    <div class="flex min-w-220 items-center gap-0 border-b border-admin-border px-4 py-3.5">
                        <div class="w-55 pr-2">
                            <p class="truncate text-sm font-bold text-admin-text">{{ $message->name }}</p>
                            <p class="truncate text-xs text-admin-text-muted">{{ $message->maskedEmail() }}</p>
                        </div>
                        <div class="w-100.5 pr-2">
                            <p class="truncate text-sm font-bold text-admin-text">{{ $message->subjectLabel() }}</p>
                            <p class="truncate text-xs text-admin-text-muted">{{ $message->message }}</p>
                        </div>
                        <div class="w-30 text-sm text-admin-text">{{ $message->preferenceLabel() }}</div>
                        <div class="w-30 text-[13px] text-admin-text-secondary">{{ $relativeDate($message->created_at) }}</div>
                        <div class="w-27.5">
                            <x-admin.badge :tone="$message->read_at ? 'success' : 'warning'" :label="$message->read_at ? 'Atendido' : 'Pendiente'" />
                        </div>
                        <div class="flex w-26.5 justify-end">
                            @if ($message->read_at)
                                <a href="{{ route('admin.messages.show', $message) }}" class="px-4 py-2.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-accent">Ver</a>
                            @else
                                <a href="{{ route('admin.messages.show', $message) }}" class="border border-admin-border-strong bg-admin-surface px-4 py-2.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Atender</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="flex min-w-220 flex-col items-center gap-2.5 px-4 py-14 text-center">
                        <span class="grid size-12 place-items-center rounded-full bg-admin-subtle">
                            <img src="{{ asset('images/admin/icon-mail-filled.svg') }}" class="size-5" alt="">
                        </span>
                        <p class="text-[15px] font-bold text-admin-text">{{ $tab === 'pendientes' ? 'No hay mensajes pendientes' : 'Aún no han llegado mensajes' }}</p>
                        <p class="max-w-75 text-[13px] text-admin-text-secondary">Cuando un cliente escriba desde Contacto, verás aquí su mensaje.</p>
                    </div>
                @endforelse
            </div>

            <div class="flex flex-col items-center justify-between gap-3 px-5 py-3.5 sm:flex-row">
                <p class="text-[13px] text-admin-text-secondary">
                    @if ($messages->total() > 0)
                        Mostrando {{ $messages->firstItem() }}–{{ $messages->lastItem() }} de {{ $messages->total() }} mensajes
                    @else
                        Sin resultados
                    @endif
                </p>
                @if ($messages->lastPage() > 1)
                    <div class="flex items-center gap-2">
                        @if ($messages->onFirstPage())
                            <span class="flex items-center gap-2 border border-admin-border px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text-muted">
                                <img src="{{ asset('images/admin/icon-arrow-left.svg') }}" class="size-4 opacity-40" alt="">
                                Anterior
                            </span>
                        @else
                            <a href="{{ $messages->previousPageUrl() }}" class="flex items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">
                                <img src="{{ asset('images/admin/icon-arrow-left.svg') }}" class="size-4" alt="">
                                Anterior
                            </a>
                        @endif
                        @if ($messages->hasMorePages())
                            <a href="{{ $messages->nextPageUrl() }}" class="flex items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">
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
