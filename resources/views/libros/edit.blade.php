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

            <form action="{{ route('libros.update', $libro->id_libro_interno) }}" method="POST" class="row g-3" id="form-libro-edit">
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

                {{-- AUTORES: multi input (editable) --}}
                <div class="col-12">
                    <label class="form-label">Autores</label>

                    <div id="authors-wrapper" class="mb-2">
                        @php
                            // Preferir old inputs si existen, si no usar autores del libro
                            $oldAuthors = old('autor_nombres', []);
                            if (empty($oldAuthors)) {
                                $oldAuthors = $libro->autores->pluck('nombre')->toArray();
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

                    <small class="text-muted d-block mb-2">Puedes editar, añadir o eliminar autores. Al guardar se sincronizarán en la tabla pivote.</small>

                    {{-- campo legacy / fallback (se conserva por compatibilidad) --}}
                    <input type="hidden" name="autor_nombre" id="autor_nombre_input" value="{{ old('autor_nombre', $libro->autores->pluck('nombre')->join(', ')) }}">
                </div>

                {{-- Género (se mantiene igual) --}}
                <div class="col-md-6">
                    <label class="form-label">Género literario</label>
                    <input type="text" name="genero_nombre" class="form-control mb-2"
                        value="{{ old('genero_nombre', $libro->genero->nombre ?? '') }}"
                        placeholder="Escriba un nuevo género (ignorará si selecciona de la lista)">
                    <select name="genero_id" class="form-select">
                        <option value="">-- Seleccione un género --</option>
                        @foreach($generos_literarios as $genero)
                            <option value="{{ $genero->id_genero }}" {{ (old('genero_id', $libro->id_genero) == $genero->id_genero) ? 'selected' : '' }}>
                                {{ $genero->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Ubicacion/Agregar copias (se mantienen) --}}
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
                            <option value="{{ $u->id_ubicacion }}" {{ (old('id_ubicaciones') == $u->id_ubicacion) ? 'selected' : '' }}>
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

{{-- JS para manejar inputs de autores (similar al create) --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const authorsWrapper = document.getElementById('authors-wrapper');
    const btnAdd = document.getElementById('btn-add-author');
    const btnAddExisting = document.getElementById('btn-add-existing');
    const autorSelectExisting = document.getElementById('autor_select_existing');
    const autorNombreHidden = document.getElementById('autor_nombre_input');

    // función para crear un input de autor
    function createAuthorInput(value = '') {
        const group = document.createElement('div');
        group.className = 'input-group mb-2 author-item';

        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'autor_nombres[]';
        input.className = 'form-control';
        input.placeholder = 'Nombre autor';
        input.value = value;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-danger btn-remove-author';
        btn.title = 'Eliminar autor';
        btn.innerHTML = '<i class="bi bi-x-lg"></i>';

        btn.addEventListener('click', function () {
            group.remove();
            updateFallbackField();
        });

        group.appendChild(input);
        group.appendChild(btn);

        return group;
    }

    // añadir autor manual
    btnAdd.addEventListener('click', function () {
        authorsWrapper.appendChild(createAuthorInput(''));
    });

    // añadir autor existente desde select
    btnAddExisting.addEventListener('click', function () {
        const selected = autorSelectExisting.value;
        if (!selected) return;
        authorsWrapper.appendChild(createAuthorInput(selected));
        autorSelectExisting.selectedIndex = 0;
        updateFallbackField();
    });

    // manejar botones eliminar ya renderizados (incluye los que vinieron con old)
    document.querySelectorAll('.btn-remove-author').forEach(btn => {
        btn.addEventListener('click', function (e) {
            // el botón puede contener un <i>, por eso buscamos closest
            const item = e.target.closest('.author-item');
            if (item) item.remove();
            updateFallbackField();
        });
    });

    // cuando el formulario se envíe, rellenar el campo hidden autor_nombre con la lista coma-separated (fallback)
    document.getElementById('form-libro-edit').addEventListener('submit', function () {
        updateFallbackField();
    });

    // actualiza el campo hidden 'autor_nombre' con valores actuales (coma separated)
    function updateFallbackField() {
        const inputs = authorsWrapper.querySelectorAll('input[name="autor_nombres[]"]');
        const vals = [];
        inputs.forEach(i => {
            if (i.value && i.value.trim() !== '') vals.push(i.value.trim());
        });
        autorNombreHidden.value = vals.join(', ');
    }

    // actualizar fallback cada vez que se cambia un input
    authorsWrapper.addEventListener('input', updateFallbackField);

    // inicializar fallback con valores actuales al cargar
    updateFallbackField();
});
</script>
@endpush

@endsection
