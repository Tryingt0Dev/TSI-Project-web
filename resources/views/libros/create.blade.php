@extends('layouts.app')

@section('title', 'Agregar Libro')

@section('content')
<h1>Agregar Libro</h1>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<form action="{{ route('libros.store') }}" method="POST">
    @csrf

    <div class="mb-3">
        <input type="text" id="busqueda" class="form-control" placeholder="Escribe título o autor">
        <select class="form-select" id="resultados" size="5"></select>
    </div>

    <div class="mb-3">
        <label class="form-label">ISBN</label>
        <input type="text" name="isbn_libro" id="isbn" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Título</label>
        <input type="text" name="titulo" id="titulo" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Editorial</label>
        <input type="text" name="editorial" id="editorial" class="form-control">
    </div>

    <div class="mb-3">
        <label class="form-label">Género</label>
        <input type="text" name="genero_nombre" id="genero_autocomplete" class="form-control" placeholder="Escriba un nuevo género o seleccione">
        <select name="genero_id" id="genero_select" class="form-select mt-1">
            <option value="">-- Seleccione un género --</option>
            @foreach($generos_literarios as $genero)
                <option value="{{ $genero->id }}">{{ $genero->nombre }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Año de publicación</label>
        <input type="date" name="fecha_publicacion" id="fecha_publicacion" class="form-control">
    </div>

    <div class="mb-3">
        <label class="form-label">Autor</label>
        <input type="text" name="autor_nombre" id="autor_nombre" class="form-control" placeholder="Escriba o seleccione un autor">
        <select name="autor_id" id="autor_select" class="form-select mt-1">
            <option value="">-- Seleccione un autor --</option>
            @foreach($autores as $autor)
                <option value="{{ $autor->id }}">{{ $autor->nombre }}</option>
            @endforeach
        </select>
    </div>

    <!-- NUEVO: Ubicación por defecto para las copias -->
    <div class="mb-3">
        <label class="form-label">Ubicación por defecto para las copias</label>
        <select name="id_ubicaciones" class="form-select">
            <option value="">-- Seleccione una ubicación (opcional) --</option>
            @foreach($ubicaciones as $u)
                <option value="{{ $u->id }}">{{ $u->estante }} {{ $u->seccion ? ' - ' . $u->seccion : '' }}</option>
            @endforeach
        </select>
    </div>

    
    <div class="mb-3">
        <label class="form-label">Número de copias a crear</label>
        <input type="number" name="num_copias" class="form-control" min="0" value="1">
        <small class="form-text text-muted">Se crearán N registros en la tabla <code>copia</code> asociados a este libro.</small>
    </div>

    
    <div class="alert alert-info">
        <strong>Nota:</strong> <em>El stock total y disponible se calcularán automáticamente a partir de las copias creadas.</em>
    </div>

    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('libros.index') }}" class="btn btn-secondary">Cancelar</a>
</form>


@endsection
