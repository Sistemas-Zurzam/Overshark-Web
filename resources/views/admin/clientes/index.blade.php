@extends('layouts.admin')
@section('title', 'Clientes')
@section('content')
    @include('admin.partials.module-index', ['eyebrow' => 'Ventas', 'title' => 'Clientes', 'singular' => 'cliente', 'description' => 'Gestiona los datos y el historial de tus clientes.', 'columns' => ['Nombre', 'Documento', 'Celular', 'Email', 'Registro']])
@endsection
