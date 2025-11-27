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

            <form action="{{ route('libros.store') }}" method="POST" class="row g-3" id="form-libro">
                @csrf

                <div class="col-md-8">
                    <label class="form-label">Búsqueda rápida (OpenLibrary)</label>
                    <div class="d-flex gap-2">
                        <input type="text" id="busqueda" class="form-control" placeholder="Escribe título o autor">
                        <button type="button" id="buscarBtn" class="btn btn-outline-secondary" onclick="document.getElementById('busqueda').focus()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <select class="form-select mt-2" id="resultados" size="5" style="display:none;"></select>
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
                    <input type="text" name="genero_nombre" id="genero_autocomplete" class="form-control mb-2" placeholder="Nuevo género" value="{{ old('genero_nombre') }}">
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

                {{-- AUTOR: sección para múltiples autores --}}
                <div class="col-12">
                    <label class="form-label">Autores</label>

                    {{-- Datalist para autocompletar (autores existentes) --}}
                    <input list="autores_list" id="autor_nombre_single" class="form-control mb-2" placeholder="Escribe para autocompletar autores existentes (opcional)">
                    <datalist id="autores_list">
                        @foreach($autores as $autor)
                            <option value="{{ $autor->nombre }}" data-id="{{ $autor->id_autor }}"></option>
                        @endforeach
                    </datalist>

                    <div id="authors-wrapper" class="mb-2">
                        @php
                            $oldAuthors = old('autor_nombres', []);
                            if (empty($oldAuthors) && old('autor_nombre')) {
                                $oldAuthors = array_filter(array_map('trim', explode(',', old('autor_nombre'))));
                            }
                        @endphp

                        @if(!empty($oldAuthors))
                            @foreach($oldAuthors as $a)
                                <div class="input-group mb-2 author-item">
                                    <input type="text" name="autor_nombres[]" class="form-control" value="{{ $a }}" placeholder="Nombre autor">
                                    <button type="button" class="btn btn-outline-danger btn-remove-author" title="Eliminar autor"><i class="bi bi-x-lg"></i></button>
                                </div>
                            @endforeach
                        @else
                            <div class="input-group mb-2 author-item">
                                <input type="text" name="autor_nombres[]" class="form-control" placeholder="Nombre autor">
                                <button type="button" class="btn btn-outline-danger btn-remove-author" title="Eliminar autor"><i class="bi bi-x-lg"></i></button>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex gap-2 mb-2">
                        <button type="button" id="btn-add-author" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-lg"></i> Añadir autor
                        </button>

                        <div class="d-flex w-100">
                            <select id="autor_select_existing" class="form-select me-2">
                                <option value="">-- Añadir autor existente --</option>
                                @foreach($autores as $autor)
                                    <option value="{{ $autor->nombre }}" data-id="{{ $autor->id_autor }}">{{ $autor->nombre }}</option>
                                @endforeach
                            </select>
                            <button type="button" id="btn-add-existing" class="btn btn-sm btn-outline-success">Agregar</button>
                        </div>
                    </div>

                    <small class="text-muted d-block mb-2">También puedes usar el campo de autocompletar anterior o pegar autores separados por comas en el campo oculto (fallback).</small>

                    <input type="hidden" name="autor_nombre" id="autor_nombre_input" value="{{ old('autor_nombre') }}">
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

{{-- include buscador JS (reemplazado) --}}
<script src="{{ asset('js/busqueda.js') }}" defer></script>

{{-- pequeño script local para integrar el datalist single -> authors-wrapper al pulsar Enter --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const autorSingle = document.getElementById('autor_nombre_single');
    const authorsWrapper = document.getElementById('authors-wrapper');

    if (!autorSingle) return;

    autorSingle.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = autorSingle.value && autorSingle.value.trim();
            if (!val) return;
            // evitar duplicados
            const exists = Array.from(authorsWrapper.querySelectorAll('input[name="autor_nombres[]"]'))
                .some(i => i.value.trim().toLowerCase() === val.toLowerCase());
            if (!exists) {
                const group = document.createElement('div');
                group.className = 'input-group mb-2 author-item';
                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'autor_nombres[]';
                input.className = 'form-control';
                input.value = val;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-danger btn-remove-author';
                btn.innerHTML = '<i class="bi bi-x-lg"></i>';
                btn.addEventListener('click', function () { group.remove(); });
                group.appendChild(input);
                group.appendChild(btn);
                authorsWrapper.appendChild(group);
                autorSingle.value = '';
                // update hidden fallback
                const evt = new Event('input', { bubbles: true });
                authorsWrapper.dispatchEvent(evt);
            }
        }
    });

    // delegate remove buttons (for those added later)
    authorsWrapper.addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-author')) {
            e.target.closest('.author-item').remove();
            // trigger input to update fallback
            const evt = new Event('input', { bubbles: true });
            authorsWrapper.dispatchEvent(evt);
        }
    });
});
</script>
@endsection
