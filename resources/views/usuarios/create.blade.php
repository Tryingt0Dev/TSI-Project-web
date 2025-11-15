@extends('layouts.app')

@section('title', 'Crear Usuario')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h5 mb-0">
            <i class="bi bi-person-plus-fill me-2 text-primary"></i> Crear Usuario
        </h1>

        <a href="{{ route('usuarios.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            {{-- Mensajes de validación --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Mensaje de éxito opcional --}}
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('usuarios.store') }}" method="POST" class="row g-3">
                @csrf

                <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Apellido</label>
                    <input type="text" name="apellido" class="form-control" required value="{{ old('apellido') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">RUT</label>
                    <input type="text" name="rut" class="form-control" required value="{{ old('rut') }}">
                    <div class="form-text">Formato sin puntos ni guión (p. ej. 12345678K).</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Correo</label>
                    <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Rol</label>
                    <select name="rol" class="form-select" required>
                        <option value="0" {{ old('rol') === '0' ? 'selected' : '' }}>Administrador</option>
                        <option value="1" {{ old('rol', '1') === '1' ? 'selected' : '' }}>Bibliotecario</option>
                    </select>
                    <div class="form-text">0 = Administrador (acceso a Usuarios), 1 = Bibliotecario.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>

                <div class="col-12 d-flex gap-2 justify-content-end mt-2">
                    <a href="{{ route('usuarios.index') }}" class="btn btn-light">Cancelar</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// -------------------------
// FUNCIONES DE VALIDACIÓN RUT
// -------------------------

function limpiarRut(rut) {
    return rut.replace(/[^0-9kK]/g, '').toUpperCase();
}

function formatearRut(rut) {
    rut = limpiarRut(rut);

    if (rut.length <= 1) return rut;

    let cuerpo = rut.slice(0, -1);
    let dv = rut.slice(-1);

    return cuerpo + '-' + dv;
}

function validarDV(cuerpo, dv) {
    let suma = 0;
    let multiplo = 2;

    for (let i = cuerpo.length - 1; i >= 0; i--) {
        suma += multiplo * cuerpo[i];
        multiplo = multiplo < 7 ? multiplo + 1 : 2;
    }

    let dvEsperado = 11 - (suma % 11);
    
    if (dvEsperado === 11) dvEsperado = '0';
    else if (dvEsperado === 10) dvEsperado = 'K';
    else dvEsperado = dvEsperado.toString();

    return dvEsperado === dv.toUpperCase();
}

// -------------------------
// APLICAR AL INPUT
// -------------------------
document.addEventListener("DOMContentLoaded", function () {
    const inputRut = document.querySelector("input[name='rut']");

    // Formatear mientras escribe
    inputRut.addEventListener("input", function () {
        let rutFormateado = formatearRut(this.value);
        this.value = rutFormateado;
    });

    // Validar al enviar el formulario
    const form = inputRut.closest("form");

    form.addEventListener("submit", function (e) {
        let rut = inputRut.value;

        if (!/^[0-9]+-[0-9K]$/i.test(rut)) {
            e.preventDefault();
            alert("El RUT debe tener el formato 12345678-9 o 123456789-K");
            return;
        }

        let [cuerpo, dv] = rut.split('-');

        if (!validarDV(cuerpo, dv)) {
            e.preventDefault();
            alert("El RUT ingresado no es válido");
        }
    });
});
</script>

@endsection
