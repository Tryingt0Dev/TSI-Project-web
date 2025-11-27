@extends('layouts.app')

@section('title', 'Alumnos')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Alumnos</h2>
        <!-- puedes añadir un botón "Crear alumno" si lo permites -->
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Buscador + filtros -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('alumnos.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small text-muted">Buscar (RUT, nombre, apellido)</label>
                    <input type="text" name="q" value="{{ old('q', $q ?? '') }}" class="form-control" placeholder="Buscar...">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Permiso préstamos</label>
                    <select name="permiso" class="form-select">
                        <option value="">-- Todos --</option>
                        <option value="1" {{ (isset($permiso) && $permiso === '1') ? 'selected' : '' }}>Con permiso</option>
                        <option value="0" {{ (isset($permiso) && $permiso === '0') ? 'selected' : '' }}>Sin permiso</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted">Min. Atrasos</label>
                    <input type="number" name="min_atrasos" min="0" value="{{ old('min_atrasos', $min_atrasos ?? '') }}" class="form-control">
                </div>

                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary"><i class="bi bi-search me-1"></i> Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>RUT</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Registro</th>
                            <th>Atrasos</th>
                            <th>Permiso préstamo</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alumnos as $alumno)
                            <tr>
                                <td>{{ $alumno->rut_alumno }}</td>
                                <td>{{ $alumno->nombre_alumno }}</td>
                                <td>{{ $alumno->apellido_alumno }}</td>
                                <td>{{ optional($alumno->fecha_registro)->format('Y-m-d') }}</td>
                                <td>{{ $alumno->atrasos }}</td>
                                <td>
                                    @if($alumno->permiso_prestamo)
                                        <span class="badge bg-success">Sí</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('alumnos.edit', $alumno->rut_alumno) }}" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>

                                    <form action="{{ route('alumnos.destroy', $alumno->rut_alumno) }}" method="POST" class="d-inline-block delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-delete">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No hay alumnos para mostrar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <div class="mt-7 d-flex justify-content-center  ">
        {{ $alumnos->links('pagination::bootstrap-5') }}
    </div>
</div>

@push('scripts')
<script>
    // Confirmar eliminación
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (confirm('¿Eliminar este alumno? (No se borrará de la base de datos, solo se inactivará)')) {
                    this.submit();
                }
            });
        });
    });
</script>
@endpush

@endsection
