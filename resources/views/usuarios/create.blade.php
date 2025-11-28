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

                    {{-- Input visible para el usuario (formato con guion) --}}
                    <input type="text" id="rut_display" class="form-control" 
                        value="{{ old('rut_display', isset($user) ? (substr($user->rut,0,-1).'-'.substr($user->rut,-1)) : '') }}"
                        placeholder="12345678-9">

                    {{-- Input oculto que realmente se envía al servidor SIN puntos ni guion --}}
                    <input type="hidden" name="rut" id="rut" value="{{ old('rut') }}">

                    <div class="form-text">Formato sin puntos ni guión al enviar (p. ej. 12345678K). Se mostrará con guion por legibilidad.</div>
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
@endsection
    <script>
        // ---------- Helpers ----------
        function limpiarRutRaw(text) {
            // devuelve sólo números y K (mayúscula)
            return (text || '').replace(/[^0-9kK]/g, '').toUpperCase();
        }

        function formatearConGuion(text) {
            const raw = limpiarRutRaw(text);
            if (raw.length <= 1) return raw;
            const cuerpo = raw.slice(0, -1);
            const dv = raw.slice(-1);
            return cuerpo + '-' + dv;
        }

        function calcularDVValido(cuerpo, dv) {
            // cuerpo: sólo dígitos en string; dv: un caracter (0-9 o K)
            if (!/^[0-9]+$/.test(cuerpo)) return false;
            let suma = 0;
            let multiplo = 2;
            for (let i = cuerpo.length - 1; i >= 0; i--) {
                suma += multiplo * parseInt(cuerpo.charAt(i), 10);
                multiplo = multiplo < 7 ? multiplo + 1 : 2;
            }
            let dvEsperado = 11 - (suma % 11);
            if (dvEsperado === 11) dvEsperado = '0';
            else if (dvEsperado === 10) dvEsperado = 'K';
            else dvEsperado = String(dvEsperado);
            return dvEsperado === dv.toUpperCase();
        }

        // ---------- DOM wiring ----------
        document.addEventListener('DOMContentLoaded', function () {
            const inputDisplay = document.getElementById('rut_display');
            const inputHidden = document.getElementById('rut');
            if (!inputDisplay || !inputHidden) return;

            // Si hay un value ya en hidden (old), sincronizamos la vista
            if (inputHidden.value) {
                inputDisplay.value = formatearConGuion(inputHidden.value);
            }

            // Formatear mientras escribe (simple, puede alterar caret en casos complejos)
            inputDisplay.addEventListener('input', function () {
                const pos = this.selectionStart;
                const formatted = formatearConGuion(this.value);
                this.value = formatted;
                // no intentamos mantener caret perfecto (ok para la mayoría de usos)
            });

            // Interceptar submit del formulario y validar antes de enviar
            const form = inputDisplay.closest('form');
            if (!form) return;

            form.addEventListener('submit', function (e) {
                // 1) Tomar valor visible y limpiar para el envío
                const display = inputDisplay.value || '';
                const raw = limpiarRutRaw(display);

                // 2) Validación básica formato + DV
                if (raw.length < 2) {
                    e.preventDefault();
                    alert('Ingrese un RUT válido (ej. 12345678-9).');
                    return;
                }

                const cuerpo = raw.slice(0, -1);
                const dv = raw.slice(-1);

                if (!calcularDVValido(cuerpo, dv)) {
                    e.preventDefault();
                    alert('RUT inválido: el dígito verificador no coincide.');
                    return;
                }

                // 3) Poner el valor limpio en el campo oculto que se enviará al servidor
                inputHidden.value = raw; // sin puntos ni guion, en mayúsculas si DV es K

                // El formulario puede continuar y enviar input[name="rut"] con el valor correcto
            });
        });
    </script>




