@extends('layouts.app')

@section('title', 'Editar Libro')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h5 mb-0">
            <i class="bi bi-pencil-square me-2 text-primary"></i> Editar Libro
        </h1>

        <a href="{{ route('libros.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('libros.update', $libro->id) }}" method="POST" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                    <label class="form-label">ISBN</label>
                    <input type="text" name="isbn_libro" value="{{ old('isbn_libro', $libro->isbn_libro) }}" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Título</label>
                    <input type="text" name="titulo" value="{{ old('titulo', $libro->titulo) }}" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Autor</label>
                    <input type="text" name="autor_nombre" class="form-control mb-2"
                        value="{{ old('autor_nombre', $libro->autor->nombre ?? '') }}"
                        placeholder="Escriba un nuevo autor (ignorará si selecciona de la lista)">
                    <select name="autor_id" class="form-select">
                        <option value="">-- Seleccione un autor --</option>
                        @foreach($autores as $autor)
                            <option value="{{ $autor->id }}" {{ (old('autor_id', $libro->autor_id) == $autor->id) ? 'selected' : '' }}>
                                {{ $autor->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Género literario</label>
                    <input type="text" name="genero_nombre" class="form-control mb-2"
                        value="{{ old('genero_nombre', $libro->genero->nombre ?? '') }}"
                        placeholder="Escriba un nuevo género (ignorará si selecciona de la lista)">
                    <select name="genero_id" class="form-select">
                        <option value="">-- Seleccione un género --</option>
                        @foreach($generos_literarios as $genero)
                            <option value="{{ $genero->id }}" {{ (old('genero_id', $libro->genero_id) == $genero->id) ? 'selected' : '' }}>
                                {{ $genero->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fecha de publicación</label>
                    <input type="date" name="fecha_publicacion"
                           value="{{ old('fecha_publicacion', $libro->fecha_publicacion ? \Carbon\Carbon::parse($libro->fecha_publicacion)->format('Y-m-d') : '') }}"
                           class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Stock total</label>
                    <input type="number" name="stock_total" class="form-control" min="0" value="{{ $libro->stock_total }}" readonly>
                    <div class="form-text">El stock total se gestiona con las copias.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Stock disponible</label>
                    <input type="number" name="stock_disponible" class="form-control" min="0" value="{{ $libro->stock_disponible }}" readonly>
                    <div class="form-text">Se recalcula automáticamente según estado de copias.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Agregar copias</label>
                    <input type="number" name="add_copias" class="form-control" min="0" value="{{ old('add_copias', 0) }}">
                    <small class="text-muted">Se crearán N copias nuevas con estado <code>disponible</code>.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ubicación por defecto para nuevas copias</label>
                    <select name="id_ubicaciones" class="form-select">
                        <option value="">-- Seleccione (opcional) --</option>
                        @foreach($ubicaciones as $u)
                            <option value="{{ $u->id }}" {{ (old('id_ubicaciones') == $u->id) ? 'selected' : '' }}>
                                {{ $u->estante }}{{ $u->seccion ? ' - ' . $u->seccion : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Si se dejan en blanco, las copias nuevas no tendrán ubicación asignada.</div>
                </div>

                <div class="col-12 d-flex gap-2 justify-content-end mt-2">
                    <a href="{{ route('libros.index') }}" class="btn btn-light">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar libro</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
