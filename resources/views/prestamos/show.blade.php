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

{{-- Listado de copias y observaciones --}}
<form action="{{ route('prestamos.updateCopiasYComentario', $prestamo->id_prestamo) }}" method="POST">
    @csrf
    @method('PUT')

    <h5>Copias asociadas</h5>
    @foreach($prestamo->copias as $copia)
        <div class="card mb-2">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <strong>ID {{ $copia->id_copia }}</strong> - {{ $copia->libro->titulo ?? 'Libro sin título' }}
                    <span class="badge bg-secondary">{{ $copia->pivot->estado }}</span>
                </div>

                {{-- Selector de estado de la copia --}}
                <select name="copias[{{ $copia->id_copia }}]" 
                        class="form-select form-select-sm d-inline-block w-auto me-2">
                    <option value="Prestado" {{ $copia->pivot->estado === 'Prestado' ? 'selected' : '' }}>Prestado</option>
                    <option value="Disponible" {{ $copia->pivot->estado === 'Disponible' ? 'selected' : '' }}>Disponible</option>
                </select>
            </div>
        </div>
    @endforeach

    {{-- Observaciones --}}
    <h5>Observaciones</h5>
    <textarea name="observaciones" class="form-control mb-3" rows="3">{{ old('observaciones', $prestamo->observaciones) }}</textarea>

    {{-- Botones de acción --}}
    <div class="d-flex justify-content-start">
        <button type="submit" class="btn btn-success btn-lg me-2">Guardar y Volver</button>
        <a href="{{ route('prestamos.detalle', $prestamo->id_prestamo) }}" class="btn btn-secondary btn-lg">Cancelar</a>
    </div>
</form>

</div>
@endsection