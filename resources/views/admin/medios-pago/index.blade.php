@extends('layouts.admin')

@section('title', 'Medios de Pago')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Configuracion</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Medios de Pago</h1>
            <p class="mt-2 text-slate-500">Configura las opciones de pago disponibles para clientes.</p>
        </div>
        <button type="button" data-payment-form-toggle class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">
            Nuevo medio de pago
        </button>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            Revisa los campos del medio de pago.
        </div>
    @endif

    <section data-payment-form @class([
        'mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6',
        'hidden' => ! ($errors->any() && old('form_mode') !== 'edit'),
    ])>
        <h2 class="text-xl font-black text-slate-950">Agregar medio de pago</h2>
        <form action="{{ route('admin.medios-pago.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 grid gap-5 lg:grid-cols-2">
            @csrf
            <input type="hidden" name="form_mode" value="create">

            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Nombre</span>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" placeholder="Ej. Yape">
                @error('name') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Titular</span>
                <input type="text" name="titular" value="{{ old('titular') }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" placeholder="Ej. Import Textil Maso E.I.R.L">
                @error('titular') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Numero / Celular</span>
                <input type="text" name="numero" value="{{ old('numero') }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100" placeholder="Ej. 987 654 321">
                @error('numero') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Imagen</span>
                <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp,.svg" required class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
                @error('image') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Imagen QR</span>
                <input type="file" name="image_qr" accept=".png,.jpg,.jpeg,.webp,.svg" class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
                @error('image_qr') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="flex items-center gap-3 text-sm font-bold text-slate-700">
                <input type="hidden" name="status" value="0">
                <input type="checkbox" name="status" value="1" @checked(old('status', true)) class="h-5 w-5 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                Medio de pago activo
            </label>

            <div class="flex items-center gap-3 lg:justify-end">
                <button type="button" data-payment-form-toggle class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 hover:border-slate-400">
                    Cancelar
                </button>
                <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">
                    Guardar medio de pago
                </button>
            </div>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <label class="relative block w-full max-w-md">
                <span class="sr-only">Buscar</span>
                <input type="search" placeholder="Buscar medios de pago..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
            </label>
            <button type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:border-cyan-300 hover:text-cyan-700">
                Filtros
            </button>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-3 py-3">Nombre</th>
                        <th class="px-3 py-3">Datos</th>
                        <th class="px-3 py-3">Imagen</th>
                        <th class="px-3 py-3">QR</th>
                        <th class="px-3 py-3">Estado</th>
                        <th class="px-3 py-3">Fecha</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($metodosPago as $metodoPago)
                        @php($isEditing = old('form_mode') === 'edit' && (int) old('metodo_pago_id') === $metodoPago->id)
                        <tr>
                            <td class="px-3 py-4 font-bold text-slate-950">{{ $metodoPago->name }}</td>
                            <td class="px-3 py-4 text-sm text-slate-600">
                                <p class="font-semibold text-slate-950">{{ $metodoPago->titular ?: 'Sin titular' }}</p>
                                <p class="mt-1">{{ $metodoPago->numero ?: 'Sin numero' }}</p>
                            </td>
                            <td class="px-3 py-4">
                                @if ($metodoPago->imageUrl())
                                    <img src="{{ $metodoPago->imageUrl() }}" alt="{{ $metodoPago->name }}" class="h-12 w-16 rounded-lg border border-slate-200 object-contain p-1">
                                @else
                                    <span class="text-sm text-slate-400">Sin imagen</span>
                                @endif
                            </td>
                            <td class="px-3 py-4">
                                @if ($metodoPago->qrImageUrl())
                                    <img src="{{ $metodoPago->qrImageUrl() }}" alt="QR {{ $metodoPago->name }}" class="h-16 w-16 rounded-lg border border-slate-200 object-contain p-1">
                                @else
                                    <span class="text-sm text-slate-400">Sin QR</span>
                                @endif
                            </td>
                            <td class="px-3 py-4">
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-bold',
                                    'bg-emerald-50 text-emerald-700' => $metodoPago->status,
                                    'bg-slate-100 text-slate-500' => ! $metodoPago->status,
                                ])>{{ $metodoPago->status ? 'Activo' : 'Inactivo' }}</span>
                            </td>
                            <td class="px-3 py-4 text-sm text-slate-500">{{ $metodoPago->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-4">
                                <div class="flex justify-end gap-2">
                                    <button type="button" data-payment-edit-toggle="{{ $metodoPago->id }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-cyan-300 hover:text-cyan-700">
                                        Editar
                                    </button>
                                    <form action="{{ route('admin.medios-pago.toggle', $metodoPago) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-cyan-300 hover:text-cyan-700">
                                            {{ $metodoPago->status ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.medios-pago.destroy', $metodoPago) }}" method="POST" onsubmit="return confirm('¿Eliminar este medio de pago?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr data-payment-edit-row="{{ $metodoPago->id }}" @class(['hidden' => ! $isEditing])>
                            <td colspan="7" class="bg-slate-50 px-3 py-5">
                                <form action="{{ route('admin.medios-pago.update', $metodoPago) }}" method="POST" enctype="multipart/form-data" class="grid gap-5 rounded-xl border border-slate-200 bg-white p-5 lg:grid-cols-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="form_mode" value="edit">
                                    <input type="hidden" name="metodo_pago_id" value="{{ $metodoPago->id }}">

                                    <label class="block">
                                        <span class="mb-2 block text-sm font-bold text-slate-700">Nombre</span>
                                        <input type="text" name="name" value="{{ $isEditing ? old('name') : $metodoPago->name }}" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
                                        @if ($isEditing) @error('name') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror @endif
                                    </label>

                                    <label class="block">
                                        <span class="mb-2 block text-sm font-bold text-slate-700">Titular</span>
                                        <input type="text" name="titular" value="{{ $isEditing ? old('titular') : $metodoPago->titular }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
                                        @if ($isEditing) @error('titular') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror @endif
                                    </label>

                                    <label class="block">
                                        <span class="mb-2 block text-sm font-bold text-slate-700">Numero / Celular</span>
                                        <input type="text" name="numero" value="{{ $isEditing ? old('numero') : $metodoPago->numero }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
                                        @if ($isEditing) @error('numero') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror @endif
                                    </label>

                                    <label class="flex items-center gap-3 text-sm font-bold text-slate-700">
                                        <input type="hidden" name="status" value="0">
                                        <input type="checkbox" name="status" value="1" @checked($isEditing ? old('status') : $metodoPago->status) class="h-5 w-5 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                                        Medio de pago activo
                                    </label>

                                    <label class="block">
                                        <span class="mb-2 block text-sm font-bold text-slate-700">Reemplazar imagen</span>
                                        <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp,.svg" class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
                                        <span class="mt-2 block text-xs font-semibold text-slate-400">Dejalo vacio para conservar la imagen actual.</span>
                                        @if ($isEditing) @error('image') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror @endif
                                    </label>

                                    <div class="grid gap-3">
                                        <label class="block">
                                            <span class="mb-2 block text-sm font-bold text-slate-700">Reemplazar imagen QR</span>
                                            <input type="file" name="image_qr" accept=".png,.jpg,.jpeg,.webp,.svg" class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
                                            <span class="mt-2 block text-xs font-semibold text-slate-400">Dejalo vacio para conservar el QR actual.</span>
                                            @if ($isEditing) @error('image_qr') <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span> @enderror @endif
                                        </label>

                                        @if ($metodoPago->qrImageUrl())
                                            <label class="flex items-center gap-3 text-sm font-bold text-slate-700">
                                                <input type="checkbox" name="remove_qr" value="1" class="h-5 w-5 rounded border-slate-300 text-red-600 focus:ring-red-500">
                                                Quitar QR actual
                                            </label>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-3 lg:col-span-2 lg:justify-end">
                                        <button type="button" data-payment-edit-toggle="{{ $metodoPago->id }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 hover:border-slate-400">
                                            Cancelar
                                        </button>
                                        <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">
                                            Guardar cambios
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-16 text-center text-slate-400">
                                No hay medios de pago registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
