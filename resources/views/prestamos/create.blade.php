@extends('layouts.app')

@section('title','Registrar préstamo')
@section('content')
<div class="container">
    <h1 class="mb-4">Registrar nuevo préstamo</h1>

    {{-- Mensajes de error --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Ups!</strong> Hubo problemas con los datos ingresados:
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Success flash --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form id="formPrestamo" action="{{ route('prestamos.store') }}" method="POST" novalidate>
        @csrf

        {{-- Usuario (oculto: se toma de auth()->id en el controller) --}}
        <input type="hidden" name="id_usuario" value="{{ auth()->id() }}">

        {{-- Alumno --}}
        <div class="mb-3">
            <label for="rut_alumno" class="form-label">Alumno</label>
            <select name="rut_alumno" id="rut_alumno" class="form-select" required>
                <option value="">-- Selecciona un alumno --</option>
                @foreach($alumnos as $alumno)
                    <option value="{{ $alumno->rut_alumno }}"
                        {{ old('rut_alumno') == $alumno->rut_alumno ? 'selected' : '' }}>
                        {{ $alumno->rut_alumno }} - {{ $alumno->nombre_alumno }} {{ $alumno->apellido_alumno }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">Selecciona el alumno que recibirá las copias.</div>
        </div>

        {{-- Copias disponibles --}}
        <div class="mb-3">
            <label class="form-label">Copias a prestar <span id="copias-count" class="badge bg-secondary">0</span></label>

            <div id="copias-wrapper" class="mb-2">
                {{-- se rellenará con las copias seleccionadas (inputs hidden name="copias[]") --}}
                @php $oldCopias = old('copias', []); @endphp
                @if(!empty($oldCopias) && $copias->count())
                    @foreach($oldCopias as $copiaId)
                        @php $c = $copias->firstWhere('id_copia', $copiaId); @endphp
                        @if($c)
                            <div class="input-group mb-2 copia-item">
                                <input type="text" class="form-control" value="ID {{ $c->id_copia }} — {{ $c->libro->titulo ?? 'Sin título' }}" readonly>
                                <input type="hidden" name="copias[]" value="{{ $c->id_copia }}">
                                <button type="button" class="btn btn-outline-danger btn-remove-copia" title="Eliminar copia">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>

            <div class="d-flex w-100 mb-2">
                <select id="copia_select_existing" class="form-select me-2" aria-label="Seleccionar copia disponible">
                    <option value="">-- Selecciona copia disponible --</option>
                    @foreach($copias as $copia)
                        <option value="{{ $copia->id_copia }}">
                            ID {{ $copia->id_copia }} — {{ $copia->libro->titulo ?? 'Libro sin título' }}
                        </option>
                    @endforeach
                </select>

                <button type="button" id="btn-add-copia" class="btn btn-sm btn-outline-success">Agregar</button>
            </div>

            <div class="form-text text-muted">
                Selecciona una o varias copias disponibles. Puedes quitar una copia seleccionada con el botón rojo.
            </div>

            {{-- error display para copias (server side) --}}
            @error('copias')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        {{-- Fecha límite de devolución --}}
        @php $hoy = now()->format('Y-m-d'); @endphp
        <div class="mb-3">
            <label for="fecha_devolucion_prevista" class="form-label">Fecha límite de devolución</label>
            <input type="date" name="fecha_devolucion_prevista" id="fecha_devolucion_prevista"
                   class="form-control"
                   min="{{ $hoy }}"
                   value="{{ old('fecha_devolucion_prevista') }}">
            @error('fecha_devolucion_prevista')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        {{-- Botones --}}
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('prestamos.index') }}" class="btn btn-outline-secondary">Cancelar</a>

            <div>
                <button type="button" id="btnConfirmSubmit" class="btn btn-primary">
                    <i class="bi bi-check2-circle"></i> Registrar préstamo
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Modal de confirmación (Bootstrap) --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar préstamo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Vas a crear un préstamo y las copias seleccionadas se marcarán como <strong>prestado</strong>.</p>
        <p class="mb-0"><strong>Copias seleccionadas:</strong> <span id="confirm-copias-count">0</span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button id="modal-submit" type="button" class="btn btn-primary">Sí, crear préstamo</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('copias-wrapper');
    const select = document.getElementById('copia_select_existing');
    const btnAdd = document.getElementById('btn-add-copia');
    const countBadge = document.getElementById('copias-count');
    const form = document.getElementById('formPrestamo');
    const btnConfirm = document.getElementById('btnConfirmSubmit');
    const confirmModalEl = document.getElementById('confirmModal');
    const confirmModal = new bootstrap.Modal(confirmModalEl);
    const confirmCopiasCount = document.getElementById('confirm-copias-count');
    const modalSubmit = document.getElementById('modal-submit');

    // helper: actualizar contador
    function updateCount() {
        const n = wrapper.querySelectorAll('input[name="copias[]"]').length;
        countBadge.textContent = n;
        confirmCopiasCount.textContent = n;
    }

    // init counter
    updateCount();

    // Añadir copia seleccionada
    btnAdd.addEventListener('click', function () {
        const selectedId = select.value;
        const selectedText = select.options[select.selectedIndex]?.text || '';

        if (!selectedId) {
            alert('Selecciona una copia disponible primero.');
            return;
        }

        // verificar duplicado
        const exists = Array.from(wrapper.querySelectorAll('input[name="copias[]"]'))
            .some(i => i.value === selectedId);

        if (exists) {
            alert('La copia ya fue añadida.');
            select.value = '';
            return;
        }

        // crear elemento
        const div = document.createElement('div');
        div.className = 'input-group mb-2 copia-item';
        div.innerHTML = `
            <input type="text" class="form-control" value="${selectedText}" readonly>
            <input type="hidden" name="copias[]" value="${selectedId}">
            <button type="button" class="btn btn-outline-danger btn-remove-copia" title="Eliminar copia">
                <i class="bi bi-x-lg"></i>
            </button>
        `;
        wrapper.appendChild(div);
        select.value = '';
        updateCount();
    });

    // eliminar copia (delegation)
    wrapper.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-copia');
        if (!btn) return;
        const item = btn.closest('.copia-item');
        item?.remove();
        updateCount();
    });

    // Prevent submit if no copias selected
    btnConfirm.addEventListener('click', function () {
        const n = wrapper.querySelectorAll('input[name="copias[]"]').length;
        if (n === 0) {
            alert('Debes seleccionar al menos una copia para crear el préstamo.');
            return;
        }
        // abrir modal de confirmación
        confirmModal.show();
    });

    // Cuando confirmes en modal, realmente submit
    modalSubmit.addEventListener('click', function () {
        // puede añadirse lógica adicional (por ejemplo bloquear boton)
        form.submit();
    });

    // permitir enviar con Enter en select
    select.addEventListener('keyup', function (e){
        if (e.key === 'Enter') btnAdd.click();
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnConfirm = document.getElementById('btnConfirmSubmit');
    const form = document.getElementById('formPrestamo');
    const fechaInput = document.getElementById('fecha_devolucion_prevista');

    btnConfirm.addEventListener('click', function (e) {
        // Evitamos que el botón dispare envío automático
        e.preventDefault();

        // Validar fecha
        if (!fechaInput.value) {
            alert('Debe seleccionar una fecha límite de devolución antes de registrar el préstamo.');
            fechaInput.focus();
            return; // no envía el formulario
        }

        // Si pasa la validación, enviamos el formulario
        form.submit();
    });
});
</script>
@endpush

@endsection
