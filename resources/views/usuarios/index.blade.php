@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="container">
    <h1 class="mb-4">Lista de Usuarios</h1>

    <a href="{{ route('usuarios.create') }}" class="btn btn-primary mb-3">Agregar Usuario</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>RUT</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->name }}</td>
                    <td>{{ $usuario->apellido }}</td>
                    <td>{{ $usuario->rut }}</td>
                    <td>{{ $usuario->email }}</td>
                    <td>
                        @if($usuario->rol == 0)
                            Administrador
                        @else
                            Bibliotecario
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('usuarios.edit', $usuario->id) }}" class="btn btn-sm btn-primary">Editar</a>

                        <button class="btn btn-sm btn-danger btn-confirm"
                            data-action="{{ route('usuarios.destroy', $usuario->id) }}"
                            data-method="DELETE"
                            data-title="Eliminar usuario"
                            data-text="¿Eliminar a {{ $usuario->name }}?">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $usuarios->links() }}
</div>
@endsection
