@extends('layouts.app')

@section('title', 'Agregar Libro')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h5 mb-0">
            <i class="bi bi-plus-circle me-2 text-primary"></i> Agregar Libro
        </h1>

        <a href="{{ route('libros.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm">
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

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form action="{{ route('libros.store') }}" method="POST" class="row g-3">
                @csrf

                <div class="col-md-8">
                    <label class="form-label">Búsqueda rápida (OpenLibrary)</label>
                    <div class="d-flex gap-2">
                        <input type="text" id="busqueda" class="form-control" placeholder="Escribe título o autor">
                        <button type="button" id="buscarBtn" class="btn btn-outline-secondary" onclick="document.getElementById('busqueda').focus()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <select class="form-select mt-2" id="resultados" size="5"></select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">ISBN</label>
                    <input type="text" name="isbn_libro" id="isbn" class="form-control" required value="{{ old('isbn_libro') }}">
                </div>

                <div class="col-md-8">
                    <label class="form-label">Título</label>
                    <input type="text" name="titulo" id="titulo" class="form-control" required value="{{ old('titulo') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Editorial</label>
                    <input type="text" name="editorial" id="editorial" class="form-control" value="{{ old('editorial') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Género</label>
                    <input type="text" name="genero_nombre" id="genero_autocomplete" class="form-control mb-2" placeholder="Nuevo género">
                    <select name="genero_id" id="genero_select" class="form-select">
                        <option value="">-- Seleccione un género --</option>
                        @foreach($generos_literarios as $genero)
                            <option value="{{ $genero->id_genero }}" {{ old('genero_id') == $genero->id_genero ? 'selected' : '' }}>
                                {{ $genero->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Año de publicación</label>
                    <input type="date" name="fecha_publicacion" id="fecha_publicacion" class="form-control" value="{{ old('fecha_publicacion') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Autor</label>
                    <input type="text" name="autor_nombre" id="autor_nombre" class="form-control mb-2" placeholder="Escriba o seleccione un autor" value="{{ old('autor_nombre') }}">
                    <select name="autor_id" id="autor_select" class="form-select">
                        <option value="">-- Seleccione un autor --</option>
                        @foreach($autores as $autor)
                            <option value="{{ $autor->id_autor }}" {{ old('autor_id') == $autor->id_autor ? 'selected' : '' }}>{{ $autor->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ubicación por defecto para las copias</label>
                    <select name="id_ubicacion" class="form-select">
                        <option value="">-- Seleccione una ubicación (opcional) --</option>
                        @foreach($ubicaciones as $u)
                            <option value="{{ $u->id_ubicacion }}" {{ old('id_ubicacion') == $u->id_ubicacion ? 'selected' : '' }}>
                                {{ $u->estante }}{{ $u->seccion ? ' - ' . $u->seccion : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Número de copias a crear</label>
                    <input type="number" name="num_copias" class="form-control" min="0" value="{{ old('num_copias', 1) }}">
                    <small class="text-muted">Se crearán N registros en la tabla <code>copia</code> asociados a este libro.</small>
                </div>

                <div class="col-12 d-flex gap-2 justify-content-end mt-2">
                    <a href="{{ route('libros.index') }}" class="btn btn-light">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar libro</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- include buscador JS (ya lo tenías) --}}
<script src="{{ asset('js/busqueda.js') }}" defer></script>
@endsection
