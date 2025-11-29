@extends('layouts.app')

@section('title', 'Lista de Prestamos')

@section('content')
<div class="container">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4">
            <i class="bi bi-receipt text-primary me-2"></i>
            Gestión de Préstamos
        </h1>

        <a href="{{ route('prestamos.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i> Crear Préstamo
        </a>
    </div>

    {{-- Filtros: fecha (desde-hasta), rut, nombre --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('prestamos.index') }}" class="row g-2 align-items-end">
                <input type="hidden" name="per_page" value="15">

                <div class="col-md-3">
                    <label class="form-label small text-muted">Fecha desde</label>
                    <input type="date" name="fecha_from" value="{{ request('fecha_from') }}" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Fecha hasta</label>
                    <input type="date" name="fecha_to" value="{{ request('fecha_to') }}" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">RUT alumno</label>
                    <input type="text" name="rut" value="{{ request('rut') }}" class="form-control" placeholder="Ej: 12345678-9">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Nombre alumno</label>
                    <input type="text" name="nombre_alumno" value="{{ request('nombre_alumno') }}" class="form-control" placeholder="Nombre o apellido">
                </div>

                <div class="col-12 col-md-2 mt-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                </div>

                <div class="col-12 col-md-2 mt-2">
                    <a href="{{ route('prestamos.index') }}" class="btn btn-outline-secondary w-100">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID Prestamo</th>
                            <th>Alumno (RUT / Nombre)</th>
                            <th>Copias</th>
                            <th>Fecha inicio</th>
                            <th>Fecha límite</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($prestamos as $prestamo)
                            <tr>
                                <td class="fw-semibold">{{ $prestamo->id_prestamo }}</td>

                                <td>
                                    {{-- Mostrar rut y nombre si existen --}}
                                    {{ $prestamo->rut_alumno ?? '-' }}
                                    @if(isset($prestamo->alumno))
                                        / {{ $prestamo->alumno->nombre_alumno ?? '' }} {{ $prestamo->alumno->apellido_alumno ?? '' }}
                                    @else
                                        @if(!empty($prestamo->nombre_alumno))
                                            / {{ $prestamo->nombre_alumno }}
                                        @endif
                                    @endif
                                </td>

                                <td>
                                    {{-- Si usas pivot prestamo_copia y relación copias, listarlas --}}
                                    @if($prestamo->copias && $prestamo->copias->count())
                                        @foreach($prestamo->copias as $c)
                                            <span class="badge bg-light text-dark me-1">ID {{ $c->id_copia ?? $c->id }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="text-muted">{{ optional($prestamo->fecha_prestamo)->format('Y-m-d H:i') ?? ($prestamo->fecha_prestamo ?? '-') }}</td>
                                <td>{{ optional($prestamo->fecha_devolucion_prevista)->format('Y-m-d') ?? ($prestamo->fecha_devolucion_prevista ?? '-') }}</td>

                                <td>
                                    @php
                                        $estado = strtolower($prestamo->estado ?? ($prestamo->entregado == 1 ? 'devuelto' : 'pendiente'));
                                    @endphp

                                    @if(in_array($estado, ['activo','pendiente','1','0']) && ($prestamo->entregado ?? null) != 1)
                                        <span class="badge bg-secondary">Pendiente</span>
                                    @elseif(in_array($estado, ['devuelto','devuelto','1']) || ($prestamo->entregado ?? null) == 1)
                                        <span class="badge bg-success">Devuelto</span>
                                    @else
                                        <span class="badge bg-danger text-white">Perdido</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center">
                                        <a href="{{ route('prestamos.detalle', $prestamo->id_prestamo) }}" class="btn btn-sm btn-outline-secondary me-1" title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <a href="{{ route('prestamos.show', $prestamo->id_prestamo) }}" 
                                            class="btn btn-sm btn-outline-warning me-1" 
                                            title="Actualizar copias">
                                            <i class="bi bi-journal-check"></i>
                                        </a>

                                        <form action="{{ route('prestamos.destroy', $prestamo->id_prestamo) }}" method="POST" class="d-inline-block ms-1"
                                              onsubmit="return confirm('¿Confirmas que quieres eliminar el préstamo #{{ $prestamo->id_prestamo }} ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No se encontraron préstamos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    {{-- Paginación (forzar mantener querystring) --}}
    <div class="mt-7 d-flex justify-content-center  ">
        {{ $prestamos->withQueryString()->links('pagination::bootstrap-5') }}
    </div>

</div>
@endsection
