@extends('layouts.admin')

@section('title', 'Reclamos')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-cyan-700">Atencion al cliente</p>
                <h1 class="mt-2 text-3xl font-black">Libro de reclamaciones</h1>
                <p class="mt-2 text-sm text-slate-500">Consulta los reclamos y quejas enviados desde la tienda.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:w-80">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase text-slate-400">Total</p>
                    <p class="mt-2 text-2xl font-black">{{ $totalReclamos }}</p>
                </div>
                <div class="rounded-xl border border-cyan-100 bg-cyan-50 p-4">
                    <p class="text-xs font-bold uppercase text-cyan-700">Pendientes</p>
                    <p class="mt-2 text-2xl font-black text-cyan-800">{{ $reclamosPendientes }}</p>
                </div>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-lg font-black">Registro de reclamos</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Codigo</th>
                            <th class="px-6 py-4">Consumidor</th>
                            <th class="px-6 py-4">Contacto</th>
                            <th class="px-6 py-4">Pedido</th>
                            <th class="px-6 py-4">Tipo</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($reclamos as $reclamo)
                            <tr class="align-top transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-6 py-5 font-black">#{{ str_pad($reclamo->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-6 py-5">
                                    <p class="font-bold">{{ $reclamo->consumer_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $reclamo->document_type }} {{ $reclamo->document_number }}</p>
                                    @if ($reclamo->is_minor)
                                        <p class="mt-2 w-fit rounded-full bg-blue-100 px-2 py-1 text-xs font-bold text-blue-700">Menor de edad</p>
                                    @endif
                                </td>
                                <td class="px-6 py-5">
                                    <p class="font-semibold">{{ $reclamo->email }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $reclamo->phone }}</p>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="font-semibold">{{ $reclamo->order_number }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $reclamo->receipt_type }} · {{ $reclamo->purchase_date?->format('d/m/Y') }}</p>
                                    <p class="mt-2 max-w-xs text-xs leading-5 text-slate-500">{{ $reclamo->order_product }}</p>
                                </td>
                                <td class="px-6 py-5">
                                    <span @class([
                                        'inline-flex rounded-full px-3 py-1 text-xs font-black uppercase',
                                        'bg-red-50 text-red-700' => $reclamo->claim_type === 'reclamo',
                                        'bg-amber-50 text-amber-700' => $reclamo->claim_type === 'queja',
                                    ])>
                                        {{ $reclamo->claim_type }}
                                    </span>
                                    <p class="mt-3 max-w-sm text-xs leading-5 text-slate-500">{{ $reclamo->claim_description }}</p>
                                    <p class="mt-2 max-w-sm text-xs font-semibold leading-5 text-slate-700">Solucion: {{ $reclamo->expected_solution }}</p>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-black uppercase text-slate-700">{{ $reclamo->status }}</span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-5 text-slate-500">{{ $reclamo->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <p class="text-lg font-black">Aun no hay reclamos registrados</p>
                                    <p class="mt-2 text-sm text-slate-500">Cuando un cliente envie el formulario, aparecera en esta tabla.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($reclamos->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $reclamos->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
