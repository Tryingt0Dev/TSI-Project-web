@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Detalle del préstamo #{{ $prestamo->id_prestamo }}</h3>

    {{-- Información del alumno --}}
    <p><strong>Alumno:</strong> {{ $prestamo->alumno->nombre_alumno }} {{ $prestamo->alumno->apellido_alumno }}</p>
    <p><strong>Fecha préstamo:</strong> {{ $prestamo->fecha_prestamo->format('d/m/Y') }}</p>
    <p><strong>Fecha devolución prevista:</strong> {{ $prestamo->fecha_devolucion_prevista?->format('d/m/Y') ?? 'No definida' }}</p>

    <hr>

    {{-- Listado de copias --}}
    <h5>Copias asociadas</h5>
    @foreach($prestamo->copias as $copia)
        <div class="card mb-2">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <strong>ID {{ $copia->id_copia }}</strong> - {{ $copia->libro->titulo ?? 'Libro sin título' }}
                    <span class="badge bg-secondary">{{ $copia->pivot->estado }}</span>
                </div>

                {{-- Formulario para actualizar estado de la copia --}}
                <form method="POST" action="{{ route('prestamos.updateCopia', [$prestamo->id_prestamo, $copia->id_copia]) }}">
                    @csrf
                    @method('PATCH')

                    <select name="estado" class="form-select form-select-sm d-inline-block w-auto me-2">
                        <option value="Prestado" {{ $copia->pivot->estado === 'Prestado' ? 'selected' : '' }}>Prestado</option>
                        <option value="Disponible" {{ $copia->pivot->estado === 'Disponible' ? 'selected' : '' }}>Disponible</option>
                    </select>

                    <button type="submit" class="btn btn-sm btn-outline-primary">Actualizar</button>
                </form>
            </div>
        </div>
    @endforeach

    <a href="{{ route('prestamos.index') }}" class="btn btn-outline-secondary mt-3">Volver al listado</a>
</div>
@endsection