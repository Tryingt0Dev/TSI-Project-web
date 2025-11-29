@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Detalle del préstamo #{{ $prestamo->id_prestamo }}</h3>

    {{-- Información del alumno --}}
    <p><strong>Alumno:</strong> {{ $prestamo->alumno->nombre_alumno }} {{ $prestamo->alumno->apellido_alumno }}</p>
    <p><strong>Fecha préstamo:</strong> {{ $prestamo->fecha_prestamo->format('d/m/Y') }}</p>
    <p><strong>Fecha devolución prevista:</strong> {{ $prestamo->fecha_devolucion_prevista?->format('d/m/Y') ?? 'No definida' }}</p>
    <p><strong>Fecha devolución real:</strong> {{ $prestamo->fecha_devolucion_real?->format('d/m/Y') ?? 'Pendiente' }}</p>
    <p><strong>Estado:</strong> 
        @if($prestamo->estado === 'devuelto')
            <span class="badge bg-success">Devuelto</span>
        @else
            <span class="badge bg-warning">{{ ucfirst($prestamo->estado) }}</span>
        @endif
    </p>

    <hr>

    {{-- Listado de copias --}}
    <h5>Copias asociadas</h5>
    @foreach($prestamo->copias as $copia)
        <div class="card mb-2">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <strong>ID {{ $copia->id_copia }}</strong> - {{ $copia->libro->titulo ?? 'Libro sin título' }}
                </div>
                <span class="badge bg-secondary">{{ $copia->pivot->estado }}</span>
            </div>
        </div>
    @endforeach

    <hr>

    {{-- Comentario / Observaciones --}}
    <h5>Observaciones</h5>
    @if($prestamo->observaciones)
        <p class="border rounded p-3 bg-light">{{ $prestamo->observaciones }}</p>
    @else
        <p class="text-muted">No se registraron observaciones.</p>
    @endif

    <div class="mt-3">
        <a href="{{ route('prestamos.index') }}" class="btn btn-outline-secondary">Volver al listado</a>
    </div>
</div>
@endsection