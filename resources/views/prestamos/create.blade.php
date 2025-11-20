@extends('layouts.app')

@section('title', 'Crear Préstamo')

@section('content')
<div class="container">
    <h1 class="h4 mb-4">
        <i class="bi bi-calendar-check text-primary me-2"></i>
        Crear Préstamo (solo visual)
    </h1>

    <form action="{{ route('prestamos.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="usuario_id" class="form-label">Usuario</label>
            <input type="text" class="form-control" id="usuario_id" name="usuario_id">
        </div>

        <div class="mb-3">
            <label for="libro_id" class="form-label">Libro</label>
            <input type="text" class="form-control" id="libro_id" name="libro_id">
        </div>

        <div class="mb-3">
            <label for="fecha_prestamo" class="form-label">Fecha de Préstamo</label>
            <input type="date" class="form-control" id="fecha_prestamo" name="fecha_prestamo">
        </div>

        <button type="submit" class="btn btn-success">Guardar</button>
    </form>
</div>
@endsection