@extends('layouts.admin')

@section('title', 'Sugerencias')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Ventas</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950">Sugerencias del carrito</h1>
        <p class="mt-2 max-w-3xl text-slate-500">
            Al agregar una prenda, la clienta ve “Completa tu look” con prendas para sumar con un clic. Primero se usan las reglas de
            la prenda agregada, luego las de su marca y, si faltan, prendas con stock de la misma marca. Nunca se sugiere lo que ya
            está en el carrito ni algo sin stock.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
    @endif

    <section class="mb-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        <h2 class="mb-5 text-xl font-black text-slate-950">Nueva sugerencia</h2>
        @include('admin.sugerencias._form', ['rule' => null])
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <h2 class="border-b border-slate-100 px-5 py-4 text-xl font-black text-slate-950 lg:px-6">Reglas ({{ $rules->count() }})</h2>
        @forelse ($rules as $rule)
            <article class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between lg:px-6">
                <div class="min-w-0">
                    <p class="font-bold text-slate-950">{{ $rule->name }}
                        <span @class(['ml-2 rounded-full px-2 py-0.5 text-xs font-bold', 'bg-emerald-50 text-emerald-700' => $rule->active, 'bg-slate-100 text-slate-500' => ! $rule->active])>{{ $rule->active ? 'Activa' : 'Inactiva' }}</span>
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $rule->trigger_type === 'marca' ? 'Marca' : 'Prenda' }}: <span class="font-semibold text-slate-700">{{ $rule->trigger_value }}</span>
                        → {{ collect($rule->products)->implode(', ') }}
                    </p>
                    @if ($rule->starts_at || $rule->ends_at)
                        <p class="mt-1 text-xs text-slate-400">{{ $rule->starts_at?->format('d/m/Y') ?? 'siempre' }} → {{ $rule->ends_at?->format('d/m/Y') ?? 'sin fin' }}</p>
                    @endif
                </div>
                <div class="flex shrink-0 items-center gap-4 text-sm font-bold">
                    <a href="{{ route('admin.sugerencias.edit', $rule) }}" class="text-cyan-700 hover:underline">Editar</a>
                    <form method="POST" action="{{ route('admin.sugerencias.toggle', $rule) }}">@csrf @method('PATCH')<button type="submit" class="text-slate-600 hover:underline">{{ $rule->active ? 'Desactivar' : 'Activar' }}</button></form>
                    <form method="POST" action="{{ route('admin.sugerencias.destroy', $rule) }}" onsubmit="return confirm('¿Eliminar esta sugerencia?')">@csrf @method('DELETE')<button type="submit" class="text-red-600 hover:underline">Eliminar</button></form>
                </div>
            </article>
        @empty
            <p class="px-5 py-8 text-sm text-slate-500 lg:px-6">Aún no hay reglas. Mientras tanto se sugieren prendas con stock de la misma marca.</p>
        @endforelse
    </section>
@endsection
