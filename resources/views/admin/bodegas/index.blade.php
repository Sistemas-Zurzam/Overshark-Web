@extends('layouts.admin')
@section('title', 'Bodegas')
@section('content')
    @include('admin.partials.module-index', ['eyebrow' => 'Inventario', 'title' => 'Bodegas', 'singular' => 'bodega', 'description' => 'Gestiona las ubicaciones donde almacenas tus productos.', 'columns' => ['Nombre', 'Direccion', 'Responsable', 'Estado', 'Fecha']])
@endsection
