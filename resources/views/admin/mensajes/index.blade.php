@extends('layouts.admin')

@section('title', 'Mensajes')

@section('content')
    <div class="mb-8 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Atención al cliente</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Mensajes de contacto</h1>
            <p class="mt-2 max-w-2xl text-slate-500">Lo que escriben los clientes en el formulario de <a href="{{ route('web.contact') }}" target="_blank" class="font-bold text-cyan-700 underline">Contacto</a>. Respóndeles por correo o teléfono y márcalos como atendidos.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.messages.index') }}" @class(['rounded-xl px-4 py-2.5 text-sm font-bold', 'bg-slate-950 text-white' => $filter === 'todos', 'border border-slate-300 bg-white text-slate-700' => $filter !== 'todos'])>Todos</a>
            <a href="{{ route('admin.messages.index', ['filtro' => 'pendientes']) }}" @class(['rounded-xl px-4 py-2.5 text-sm font-bold', 'bg-slate-950 text-white' => $filter === 'pendientes', 'border border-slate-300 bg-white text-slate-700' => $filter !== 'pendientes'])>Pendientes ({{ $pending }})</a>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700" role="status">{{ session('status') }}</div>
    @endif

    @if ($messages->isEmpty())
        <div class="grid min-h-48 place-items-center rounded-2xl border border-dashed border-slate-200 bg-white text-center">
            <p class="font-bold text-slate-700">{{ $filter === 'pendientes' ? 'No hay mensajes pendientes.' : 'Aún no han llegado mensajes.' }}</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($messages as $message)
                <article @class(['rounded-2xl border bg-white p-5 shadow-sm', 'border-cyan-300' => ! $message->read_at, 'border-slate-200 opacity-80' => $message->read_at])>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-black text-slate-950">{{ $message->name }}</h2>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $message->subjectLabel() }}</span>
                                <span @class(['rounded-full px-2.5 py-1 text-xs font-bold', 'bg-cyan-50 text-cyan-700' => ! $message->read_at, 'bg-emerald-50 text-emerald-700' => $message->read_at])>{{ $message->read_at ? 'Atendido' : 'Pendiente' }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                <a href="mailto:{{ $message->email }}" class="font-semibold text-cyan-700 hover:underline">{{ $message->email }}</a>
                                @if ($message->phone) · <a href="tel:{{ $message->phone }}" class="hover:underline">{{ $message->phone }}</a>@endif
                                @if ($message->order_number) · Pedido {{ $message->order_number }}@endif
                            </p>
                        </div>
                        <time class="shrink-0 text-xs font-semibold text-slate-400" datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('d/m/Y H:i') }}</time>
                    </div>
                    <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $message->message }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subjectLabel().' · Overshark') }}" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-bold text-white hover:bg-cyan-600">Responder por correo</a>
                        <form action="{{ route('admin.messages.toggle', $message) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">{{ $message->read_at ? 'Marcar como pendiente' : 'Marcar como atendido' }}</button>
                        </form>
                        <form action="{{ route('admin.messages.destroy', $message) }}" method="POST" onsubmit="return confirm('¿Eliminar este mensaje?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600">Eliminar</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-6">{{ $messages->links() }}</div>
    @endif
@endsection
