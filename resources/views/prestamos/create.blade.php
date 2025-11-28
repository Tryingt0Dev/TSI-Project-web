@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Registrar nuevo préstamo</h1>

    {{-- Mensajes de error --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Ups!</strong> Hubo problemas con los datos ingresados:
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('prestamos.store') }}" method="POST">
        @csrf

        {{-- Usuario (oculto: se toma de auth()->id en el controller) --}}
        <input type="hidden" name="id_usuario" value="{{ auth()->id() }}">

        {{-- Alumno --}}
        <div class="mb-3">
            <label for="rut_alumno" class="form-label">Alumno</label>
            <select name="rut_alumno" id="rut_alumno" class="form-select" required>
                <option value="">-- Selecciona un alumno --</option>
                @foreach($alumnos as $alumno)
                    <option value="{{ $alumno->rut_alumno }}"
                        {{ old('rut_alumno') == $alumno->rut_alumno ? 'selected' : '' }}>
                        {{ $alumno->rut_alumno }} - {{ $alumno->nombre_alumno }} {{ $alumno->apellido_alumno }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Copias disponibles --}}
        <div class="col-12">
            <label class="form-label">Copias a prestar</label>

            <div id="copias-wrapper" class="mb-2">
                @php
                // Preferir old inputs si existen, si no dejar vacío
                $oldCopias = old('copias', []);
                @endphp

                @if(!empty($oldCopias))
                @foreach($oldCopias as $copiaId)
                @php
                    $copia = $copias->firstWhere('id_copia', $copiaId);
                @endphp
                @if($copia)
                    <div class="input-group mb-2 copia-item">
                        <input type="text" class="form-control" 
                               value="ID {{ $copia->id_copia }} - {{ $copia->libro->titulo ?? 'Libro sin título' }}" 
                               readonly>
                        <input type="hidden" name="copias[]" value="{{ $copia->id_copia }}">
                        <button type="button" class="btn btn-outline-danger btn-remove-copia" title="Eliminar copia">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                @endif
                @endforeach
                @endif
            </div>

            <div class="d-flex w-100 mb-2">
                <select id="copia_select_existing" class="form-select me-2">
                <option value="">-- Selecciona copia disponible --</option>
                @foreach($copias as $copia)
                <option value="{{ $copia->id_copia }}">
                    ID {{ $copia->id_copia }} - {{ $copia->libro->titulo ?? 'Libro sin título' }}
                </option>
                @endforeach
                </select>
                <button type="button" id="btn-add-copia" class="btn btn-sm btn-outline-success">Agregar</button>
            </div>

            <small class="text-muted d-block mb-2">
                Selecciona una o varias copias disponibles.
            </small>
        </div>

        {{-- Fecha límite de devolución --}}
        @php
            $hoy = now()->format('Y-m-d');
        @endphp

        <div class="mb-3">
            <label for="fecha_devolucion_prevista" class="form-label">Fecha límite de devolución</label>
            <input type="date" name="fecha_devolucion_prevista" id="fecha_devolucion_prevista"
            class="form-control"
            min="{{ $hoy }}"
            value="{{ old('fecha_devolucion_prevista') }}">
        </div>

        {{-- Botones --}}
        <div class="d-flex justify-content-between">
            <a href="{{ route('prestamos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Registrar préstamo</button>
        </div>
    </form>
</div>

{{-- script para agregar varias copias --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.getElementById('copias-wrapper');
    const select = document.getElementById('copia_select_existing');
    const btnAdd = document.getElementById('btn-add-copia');

    btnAdd.addEventListener('click', () => {
        const selectedId = select.value;
        const selectedText = select.options[select.selectedIndex].text;

        if (!selectedId) return;

        // Verificar si ya existe una copia con ese ID
        const alreadyExists = [...wrapper.querySelectorAll('input[type="hidden"]')]
            .some(input => input.value === selectedId);

        if (alreadyExists) {
            alert(`La copia ID ${selectedId} ya fue seleccionada.`);
            return;
        }

        // Crear el bloque visual
        const div = document.createElement('div');
        div.classList.add('input-group', 'mb-2', 'copia-item');
        div.innerHTML = `
            <input type="text" class="form-control" value="${selectedText}" readonly>
            <input type="hidden" name="copias[]" value="${selectedId}">
            <button type="button" class="btn btn-outline-danger btn-remove-copia" title="Eliminar copia">
                <i class="bi bi-x-lg"></i>
            </button>
        `;
        wrapper.appendChild(div);
        select.value = '';
    });

    wrapper.addEventListener('click', (e) => {
        if (e.target.closest('.btn-remove-copia')) {
            e.target.closest('.copia-item').remove();
        }
    });
});
</script>

@endsection