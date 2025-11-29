@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Finalizar préstamo #{{ $prestamo->id_prestamo }}</h3>

    <p><strong>Alumno:</strong> {{ $prestamo->alumno->nombre_alumno }} {{ $prestamo->alumno->apellido_alumno }}</p>
    <p><strong>Fecha préstamo:</strong> {{ $prestamo->fecha_prestamo->format('d/m/Y') }}</p>
    <p><strong>Fecha devolución prevista:</strong> {{ $prestamo->fecha_devolucion_prevista?->format('d/m/Y') ?? 'No definida' }}</p>

    <form method="POST" action="{{ route('prestamos.finalizar', $prestamo->id_prestamo) }}">
        @csrf

        <div class="mb-3">
            <label for="observaciones" class="form-label">Observaciones</label>
            <textarea name="observaciones" id="observaciones" rows="4" class="form-control" required></textarea>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('prestamos.show', $prestamo->id_prestamo) }}" class="btn btn-outline-secondary">Volver</a>
            <button type="submit" class="btn btn-success">Finalizar préstamo</button>
        </div>
    </form>
</div>
@endsection