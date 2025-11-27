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
            <label for="usuario" class="form-label">Usuario</label>
            <input type="text" class="form-control" id="usuario" value="{{ Auth::user()->name }} {{ Auth::user()->apellido }}" readonly>
        </div>

        <div class="mb-3">
            <label for="rut_alumno" class="form-label">Rut alumno</label>
            <select name="rut_alumno" id="rut_alumno" class="form-select" required>
                <option value="">-- Seleccione un alumno --</option>
                @foreach($alumnos as $alumno)
                    <option value="{{ $alumno->rut_alumno }}" 
                        @if(old('rut_alumno') == $alumno->rut_alumno) selected @endif>
                        {{ $alumno->rut_alumno }} - {{ $alumno->nombre_alumno }} {{ $alumno->apellido_alumno }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="id_copia" class="form-label">Nombre y Copia del Libro</label>
            <select name="id_copia" id="id_copia" class="form-select">
                <option value="">-- Seleccione una copia --</option>
                @foreach($copias as $copia)
                    <option value="{{ $copia->id_copia }}" @if($copia->estado !== 'Disponible') disabled @endif @if(old('copia_id') == $copia->id_copia && $copia->estado === 'Disponible') selected @endif>
                    {{ $copia->libro->titulo ?? 'Sin título' }} - {{ $copia->id_copia }}
                    @if($copia->estado !== 'Disponible') (No disponible) @endif
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="fecha_limite" class="form-label">Fecha limite</label>
            <input type="date" class="form-control" id="fecha_limite" name="fecha_limite" min="{{ date('Y-m-d') }}">
        </div>

        <button type="submit" class="btn btn-success">Guardar</button>
    </form>
</div>
@endsection