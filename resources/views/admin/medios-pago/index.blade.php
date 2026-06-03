@extends('layouts.admin')
@section('title', 'Medios de Pago')
@section('content')
    @include('admin.partials.module-index', ['eyebrow' => 'Configuracion', 'title' => 'Medios de Pago', 'singular' => 'medio de pago', 'description' => 'Configura las opciones de pago disponibles para clientes.', 'columns' => ['Nombre', 'Imagen', 'Estado', 'Fecha']])
@endsection
