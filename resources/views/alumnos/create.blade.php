@extends('layouts.app')

@section('title','Crear Alumno')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Crear Alumno</h2>
        <a href="{{ route('alumnos.index') }}" class="btn btn-light">Volver</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('alumnos.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">RUT (puede incluir DV o no)</label>
                    <input name="rut_alumno" value="{{ old('rut_alumno') }}" class="form-control" placeholder="Ej: 20991384-4 o 20991384" required>
                    <div class="form-text">Puedes ingresar con o sin DV; si no lo pones se calculará automáticamente.</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre</label>
                        <input name="nombre_alumno" value="{{ old('nombre_alumno') }}" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Apellido</label>
                        <input name="apellido_alumno" value="{{ old('apellido_alumno') }}" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Fecha de registro</label>
                        <input type="date" name="fecha_registro" value="{{ now()->toDateString() }}" class="form-control" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Atrasos</label>
                        <input type="number" name="atrasos" min="0" value="{{ old('atrasos', 0) }}" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Permiso préstamo</label>
                        <select name="permiso_prestamo" class="form-select">
                            <option value="">(auto calculado)</option>
                            <option value="1" {{ old('permiso_prestamo') === '1' ? 'selected' : '' }}>Tiene permiso</option>
                            <option value="0" {{ old('permiso_prestamo') === '0' ? 'selected' : '' }}>No tiene permiso</option>
                        </select>
                        <div class="form-text">Si dejas vacío, se calcula automáticamente según atrasos (<=3 permite).</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('alumnos.index') }}" class="btn btn-light me-2">Cancelar</a>
                    <button class="btn btn-primary">Crear alumno</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
