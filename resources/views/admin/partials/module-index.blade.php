<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">{{ $eyebrow ?? 'Administracion' }}</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950">{{ $title }}</h1>
        <p class="mt-2 text-slate-500">{{ $description }}</p>
    </div>
    <button type="button" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">
        Nuevo {{ $singular }}
    </button>
</div>

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <label class="relative block w-full max-w-md">
            <span class="sr-only">Buscar</span>
            <input type="search" placeholder="Buscar {{ strtolower($title) }}..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
        </label>
        <button type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:border-cyan-300 hover:text-cyan-700">
            Filtros
        </button>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-400">
                <tr>
                    @foreach ($columns as $column)
                        <th class="px-3 py-3">{{ $column }}</th>
                    @endforeach
                    <th class="px-3 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="px-3 py-16 text-center text-slate-400">
                        No hay {{ strtolower($title) }} registrados.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
