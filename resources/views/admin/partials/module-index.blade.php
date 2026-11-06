<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-bold uppercase tracking-widest text-[#0078d7]">{{ $eyebrow ?? 'Administracion' }}</p>
        <h1 class="mt-2 text-3xl font-black text-[#030a1c]">{{ $title }}</h1>
        <p class="mt-2 text-slate-500">{{ $description }}</p>
    </div>
    <button type="button" class="rounded-xl bg-[#030a1c] px-5 py-3 text-sm font-bold text-white shadow-[0_8px_20px_rgb(3_10_28/18%)] transition hover:bg-[#0078d7] hover:shadow-[0_10px_24px_rgb(0_120_215/30%)]">
        Nuevo {{ $singular }}
    </button>
</div>

<section class="rounded-2xl border border-slate-200 border-t-[3px] border-t-[#0078d7] bg-white p-5 shadow-[0_6px_24px_rgb(3_10_28/6%)] lg:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <label class="relative block w-full max-w-md">
            <span class="sr-only">Buscar</span>
            <input type="search" placeholder="Buscar {{ strtolower($title) }}..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none transition focus:border-[#0078d7] focus:ring-2 focus:ring-[#dbeeff]">
        </label>
        <button type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:border-[#0078d7] hover:bg-[#e8f2fc] hover:text-[#0078d7]">
            Filtros
        </button>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-slate-200 bg-[#f1f2f4] text-xs uppercase tracking-wider text-slate-500">
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
