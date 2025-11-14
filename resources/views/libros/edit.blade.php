@extends('layouts.app')

@section('title', 'Editar Libro')

@section('content')
<h1>Editar Libro</h1>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('libros.update', $libro->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label class="form-label">ISBN</label>
        <input type="text" name="isbn_libro" value="{{ $libro->isbn_libro }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Título</label>
        <input type="text" name="titulo" value="{{ $libro->titulo }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Autor</label>
        <input type="text" name="autor_nombre" class="form-control mb-1"
            value="{{ $libro->autor->nombre ?? '' }}"
            placeholder="Escriba un nuevo autor (ignorado si selecciona de la lista)">
        <select name="autor_id" class="form-select">
            <option value="">-- Seleccione un autor --</option>
            @foreach($autores as $autor)
                <option value="{{ $autor->id }}" {{ $libro->autor_id == $autor->id ? 'selected' : '' }}>
                    {{ $autor->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Género Literario</label>
        <input type="text" name="genero_nombre" class="form-control mb-1"
            value="{{ $libro->genero->nombre ?? '' }}"
            placeholder="Escriba un nuevo género (ignorado si selecciona de la lista)">
        <select name="genero_id" class="form-select">
            <option value="">-- Seleccione un género --</option>
            @foreach($generos_literarios as $genero)
                <option value="{{ $genero->id }}" {{ $libro->genero_id == $genero->id ? 'selected' : '' }}>
                    {{ $genero->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Fecha de Publicación</label>
        <input type="date" name="fecha_publicacion" value="{{ $libro->fecha_publicacion ? \Carbon\Carbon::parse($libro->fecha_publicacion)->format('Y-m-d') : '' }}" class="form-control">
    </div>

    <div class="mb-3">
        <label class="form-label">Stock Total</label>
        <input type="number" name="stock_total" class="form-control" min="0" value="{{ $libro->stock_total }}" readonly>
    </div>

    <div class="mb-3">
        <label class="form-label">Stock Disponible</label>
        <input type="number" name="stock_disponible" class="form-control" min="0" value="{{ $libro->stock_disponible }}" readonly>
    </div>

  
    <div class="mb-3">
        <label class="form-label">Agregar copias</label>
        <input type="number" name="add_copias" class="form-control" min="0" value="0">
        <small class="form-text text-muted">Ingrese cuántas copias desea agregar al libro (se crearán con estado 'disponible').</small>
    </div>

    <button type="submit" class="btn btn-primary">Actualizar</button>
    <a href="{{ route('libros.index') }}" class="btn btn-secondary">Cancelar</a>
</form>
@endsection
