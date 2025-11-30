@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="container">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4">
            <i class="bi bi-people-fill text-primary me-2"></i>
            Gestión de Usuarios
        </h1>

        <a href="{{ route('usuarios.create') }}" class="btn btn-success">
            <i class="bi bi-person-plus-fill me-1"></i> Agregar Usuario
        </a>
    </div>

    {{-- Mensajes --}}
    @if(session('success'))
        <div class="alert alert-success shadow-sm">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabla dentro de tarjeta --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>RUT</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($usuarios as $usuario)
                            <tr>
                                <td class="fw-semibold">{{ $usuario->name }}</td>
                                <td>{{ $usuario->apellido }}</td>
                                <td>{{ $usuario->rut_con_dv ?? $usuario->rut ?? 'N/A' }}</td>
                                <td>{{ $usuario->email }}</td>

                                {{-- Badge de rol --}}
                                <td>
                                    @if($usuario->rol == 0)
                                        <span class="badge bg-primary">Administrador</span>
                                    @else
                                        <span class="badge bg-secondary">Bibliotecario</span>
                                    @endif
                                </td>

                                {{-- Acciones --}}
                                <td class="text-end">

                                    {{-- Editar --}}
                                    <a href="{{ route('usuarios.edit', $usuario->id) }}"
                                       class="btn btn-sm btn-outline-primary me-1"
                                       data-bs-toggle="tooltip"
                                       title="Editar usuario">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    {{-- Eliminar --}}
                                    <form action="{{ route('usuarios.destroy', $usuario->id) }}" method="POST" style="display:inline"
                                        onsubmit="return confirm('¿Confirmas que quieres eliminar al usuario: {{ addslashes($usuario->name.' '.$usuario->apellido) }} ?');">
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
                                    No se encontraron usuarios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>
    </div>

    {{-- Paginación --}}
    <div class="mt-7 d-flex justify-content-center  ">
        {{ $usuarios->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
