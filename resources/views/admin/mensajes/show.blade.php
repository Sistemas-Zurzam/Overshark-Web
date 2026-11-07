@extends('layouts.admin')

@section('title', 'Mensaje de '.$message->name)

@section('content')
    <div class="flex flex-col gap-5">
        <a href="{{ route('admin.messages.index') }}" class="flex items-center gap-1.5 text-[13px] font-medium text-admin-text-secondary">
            <svg class="size-4" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M8 12.6667L3.33333 8L8 3.33333" stroke="currentColor" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12.6667 8H3.33333" stroke="currentColor" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Mensajes
        </a>

        <div class="flex flex-col gap-4 border-b-2 border-admin-text pb-4.5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2.5">
                    <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[40px]">{{ $message->name }}</h1>
                    <x-admin.badge :tone="$message->read_at ? 'success' : 'warning'" :label="$message->read_at ? 'Atendido' : 'Pendiente'" />
                </div>
                <p class="text-sm text-admin-text-secondary">{{ $message->subjectLabel() }} · Recibido el {{ $message->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                @if ($message->whatsappUrl())
                    <a href="{{ $message->whatsappUrl() }}" target="_blank" class="flex items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Responder por WhatsApp</a>
                @endif
                <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subjectLabel().' · Overshark') }}" class="flex items-center gap-2 bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">Responder por correo</a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="flex flex-col gap-4 lg:col-span-2">
                <div class="rounded-sm border border-admin-border bg-admin-surface p-6">
                    <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Mensaje</h2>
                    <p class="mt-4 whitespace-pre-line text-sm leading-6 text-admin-text">{{ $message->message }}</p>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <div class="rounded-sm border border-admin-border bg-admin-surface p-6">
                    <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Contacto</h2>
                    <dl class="mt-4 flex flex-col gap-3">
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Correo</dt><dd class="text-sm text-admin-text">{{ $message->email }}</dd></div>
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Teléfono</dt><dd class="text-sm text-admin-text">{{ $message->phone ?: '—' }}</dd></div>
                        <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Prefiere que lo contacten por</dt><dd class="text-sm text-admin-text">{{ $message->preferenceLabel() }}</dd></div>
                        @if ($message->order_number)
                            <div><dt class="text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">N° de pedido</dt><dd class="text-sm text-admin-text">{{ $message->order_number }}</dd></div>
                        @endif
                    </dl>
                </div>

                <form action="{{ route('admin.messages.toggle', $message) }}" method="POST" class="rounded-sm border border-admin-border bg-admin-surface p-6">
                    @csrf
                    @method('PATCH')
                    <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Estado</h2>
                    <p class="mt-2 text-xs text-admin-text-muted">{{ $message->read_at ? 'Atendido el '.$message->read_at->format('d/m/Y H:i') : 'Aún no se ha respondido.' }}</p>
                    <button type="submit" class="mt-4 w-full bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">
                        {{ $message->read_at ? 'Marcar como pendiente' : 'Marcar como atendido' }}
                    </button>
                </form>

                <form action="{{ route('admin.messages.destroy', $message) }}" method="POST" onsubmit="return confirm('¿Eliminar este mensaje?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full border border-admin-danger px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-danger">Eliminar mensaje</button>
                </form>
            </div>
        </div>
    </div>
@endsection
