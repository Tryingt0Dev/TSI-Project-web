@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
<div class="container" style="max-width: 700px;">

    {{-- Header --}}
    <div class="d-flex align-items-center mb-4">
        <h1 class="h4 mb-0">
            <i class="bi bi-person-gear text-primary me-2"></i>
            Editar Usuario
        </h1>
    </div>

    {{-- Errores --}}
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            <h6 class="fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Errores en el formulario:</h6>
            <ul class="mb-0 ms-3">
                @foreach ($errors->all() as $error)
                    <li class="small">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Card del formulario --}}
    <div class="card shadow-sm">
        <div class="card-body">

            <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Nombre --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $usuario->name) }}" required>
                </div>

                {{-- Apellido --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Apellido</label>
                    <input type="text" name="apellido" class="form-control"
                           value="{{ old('apellido', $usuario->apellido) }}" required>
                </div>

                {{-- RUT --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">RUT</label>
                    <input type="text" name="rut" class="form-control"
                           value="{{ old('rut', $usuario->rut) }}" required>
                </div>

                {{-- Email --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Correo</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email', $usuario->email) }}" required>
                </div>

                {{-- Rol --}}
                @php $editingSelf = auth()->check() && auth()->id() === $usuario->id; @endphp
                <div class="mb-3">
                    <label for="rol" class="form-label">Rol</label>
                    <select name="rol" id="rol" class="form-select" @if($editingSelf) disabled @endif>
                        <option value="0" {{ $usuario->rol == 0 ? 'selected' : '' }}>Administrador</option>
                        <option value="1" {{ $usuario->rol == 1 ? 'selected' : '' }}>Bibliotecario</option>
                    </select>

                    {{-- Si el input está deshabilitado (editingSelf), añadimos hidden para que el valor llegue al servidor --}}
                    @if($editingSelf)
                        <input type="hidden" name="rol" value="{{ $usuario->rol }}">
                        <div class="form-text text-muted">No puedes quitarte el rol administrador a ti mismo desde aquí.</div>
                    @endif

                    @error('rol')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                {{-- Contraseña --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nueva Contraseña <span class="text-muted small">(opcional)</span></label>
                    <input type="password" name="password" class="form-control">
                </div>

                {{-- Confirmación --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>

                {{-- Botones --}}
                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left-circle me-1"></i> Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Actualizar Usuario
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

{{-- Estilo opcional --}}

@endsection
