@extends('layouts.admin')

@section('title', 'Editar sugerencia')

@section('content')
    <a href="{{ route('admin.sugerencias.index') }}" class="text-sm font-bold text-cyan-700 hover:underline">← Sugerencias</a>
    <h1 class="mb-6 mt-3 text-3xl font-black text-slate-950">Editar sugerencia</h1>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        @include('admin.sugerencias._form', ['rule' => $rule])
    </section>
@endsection
