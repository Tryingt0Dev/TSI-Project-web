@extends('layouts.app')

@section('title', 'Editar Alumno')

@section('content')
<div class="container py-4">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">Editar Alumno: {{ $alumno->rut_alumno }}</h4>

            <form action="{{ route('alumnos.update', $alumno->rut_alumno) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre_alumno" class="form-control" value="{{ old('nombre_alumno', $alumno->nombre_alumno) }}" required>
                        @error('nombre_alumno')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Apellido</label>
                        <input type="text" name="apellido_alumno" class="form-control" value="{{ old('apellido_alumno', $alumno->apellido_alumno) }}" required>
                        @error('apellido_alumno')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Fecha de registro</label>
                        <input type="date" name="fecha_registro" class="form-control" value="{{ old('fecha_registro', $alumno->fecha_registro?->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Retrasos</label>
                        <input type="number" name="retrasos" min="0" class="form-control" value="{{ old('retrasos', $alumno->retrasos) }}">
                    </div>

                    <div class="col-md-4">
                        <div class="form-check" style="margin-top: 28px;">
                            <input class="form-check-input" type="checkbox" id="permiso_prestamo" name="permiso_prestamo" value="1" {{ old('permiso_prestamo', $alumno->permiso_prestamo) ? 'checked' : '' }}>
                            <label class="form-check-label" for="permiso_prestamo">Permiso para préstamos</label>
                        </div>
                    </div>

                    <div class="col-12 text-end mt-2">
                        <a href="{{ route('alumnos.index') }}" class="btn btn-secondary me-2">Cancelar</a>
                        <button class="btn btn-primary">Guardar cambios</button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection
