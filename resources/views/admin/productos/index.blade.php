@extends('layouts.admin')
@section('title', 'Productos')
@section('content')
    @include('admin.partials.module-index', ['eyebrow' => 'Catalogo', 'title' => 'Productos', 'singular' => 'producto', 'description' => 'Gestiona el catalogo, precios, stock e imagenes.', 'columns' => ['Producto', 'Categoria', 'Stock', 'Precio', 'Estado']])
@endsection
