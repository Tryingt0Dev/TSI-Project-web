@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
<div class="container">
    <h1 class="mb-4">Editar Usuario</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST">
        @csrf @method('PUT')

        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="name" class="form-control" value="{{ $usuario->name }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Apellido</label>
            <input type="text" name="apellido" class="form-control" value="{{ $usuario->apellido }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">RUT</label>
            <input type="text" name="rut" class="form-control" value="{{ $usuario->rut }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Correo</label>
            <input type="email" name="email" class="form-control" value="{{ $usuario->email }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Rol</label>
            <select name="rol" class="form-select" required>
                <option value="0" {{ $usuario->rol == 0 ? 'selected' : '' }}>Administrador</option>
                <option value="1" {{ $usuario->rol == 1 ? 'selected' : '' }}>Bibliotecario</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Nueva Contraseña (opcional)</label>
            <input type="password" name="password" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Confirmar Contraseña</label>
            <input type="password" name="password_confirmation" class="form-control">
        </div>

        <button class="btn btn-primary">Actualizar</button>
        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
