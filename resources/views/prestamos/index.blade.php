@extends('layouts.app')

@section('title', 'Lista de Prestamos')

@section('content')
<div class="container">

        {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4">
            <i class="bi bi-receipt text-primary me-2"></i>
            Gestión de Prestamos (solo visual)
        </h1>

        <a href="{{ route('prestamos.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i> Crear Prestamo
        </a>
    </div>

    {{-- Tabla dentro de tarjeta --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Id prestamo</th>
                            <th>Id copia</th>
                            <th>Fecha inicio</th>
                            <th>Fecha limite</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($prestamos as $prestamo)
                            <tr>
                                <td class="fw-semibold">{{ $prestamo->id_prestamo }}</td>
                                <td>{{ $prestamo->id_copia }}</td>
                                <td class="text-muted">{{ $prestamo->fecha_inicio }}</td>
                                <td>{{ $prestamo->fecha_limite }}</td>

                                {{-- Badge de rol --}}
                                <td>
                                    @if($prestamo->entregado == 0)
                                        <span class="badge bg-secondary">Pendiente</span>
                                    @elseif($prestamo->entregado == 1)
                                        <span class="badge bg-success">Devuelto</span>
                                    @else
                                        <span class="badge bg-danger">Perdido</span>
                                    @endif
                                </td>

                                {{-- Acciones --}}
                                <td class="text-end">

                                    {{-- Editar --}}
                                    <a href="{{ route('prestamos.edit', $prestamo->id_prestamo) }}"
                                       class="btn btn-sm btn-outline-primary me-1"
                                       data-bs-toggle="tooltip"
                                       title="Editar prestamo">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    {{-- Eliminar --}}
                                    <form action="{{ route('prestamos.destroy', $prestamo->id_prestamo) }}" method="POST" style="display:inline"
                                        onsubmit="return confirm('¿Confirmas que quieres eliminar el prestamo: {{ addslashes($prestamo->id_prestamo) }} ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>

                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No se encontraron prestamos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>
    </div>

    {{-- Paginación --}}
    <div class="d-flex justify-content-center mt-4">
        {{ $prestamos->links() }}
    </div>

</div>
@endsection