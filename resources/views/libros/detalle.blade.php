@extends('layouts.app')

@section('title', 'Detalle del Libro')

@section('content')
<div class="container">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h5 mb-0">
            <i class="bi bi-book text-primary me-2"></i>
            {{ $libro->titulo }}
        </h2>

        <a href="{{ route('libros.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver atrás
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4 text-center">
                    <img src="https://covers.openlibrary.org/b/isbn/{{ $libro->isbn_libro }}-L.jpg"
                         class="img-fluid rounded shadow-sm mb-3"
                         alt="Portada {{ $libro->titulo }}"
                         onerror="this.src='/images/no_cover.png'">

                    <div class="mt-2">
                        <span class="badge bg-primary me-1">Total: {{ $libro->stock_total ?? 0 }}</span>
                        <span class="badge bg-success">Disponible: {{ $libro->stock_disponible ?? 0 }}</span>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="mb-2"><strong>ISBN:</strong> {{ $libro->isbn_libro ?? 'N/A' }}</div>
                    <div class="mb-2"><strong>Título:</strong> {{ $libro->titulo }}</div>
                    <div class="mb-2"><strong>Autor:</strong> {{ $libro->autor->nombre ?? 'Desconocido' }}</div>
                    <div class="mb-2"><strong>Género:</strong> {{ $libro->genero->nombre ?? 'N/A' }}</div>
                    <div class="mb-2"><strong>Editorial:</strong> {{ $libro->editorial ?? 'N/A' }}</div>
                    <div class="mb-2"><strong>Fecha de publicación:</strong> {{ $libro->fecha_publicacion ?? 'N/A' }}</div>
                    <div class="mb-2"><strong>Stock total (columna):</strong> {{ $libro->stock_total ?? 0 }}</div>
                    <div class="mb-2"><strong>Stock disponible (columna):</strong> {{ $libro->stock_disponible ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Copias (si existen) --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <strong>Copias ({{ $libro->copias->count() }})</strong>
        </div>

        <div class="card-body p-0">
            @if($libro->copias->isEmpty())
                <div class="p-3 text-muted">No hay copias registradas para este libro.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID Copia</th>
                                <th>Estado</th>
                                <th>Ubicación</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($libro->copias as $copia)
                                <tr id="copia-row-{{ $copia->id_copia ?? $copia->id }}">
                                    <td class="align-middle">{{ $copia->id_copia ?? $copia->id }}</td>

                                    <td class="align-middle">
                                        @if(empty($copia->estado) || $copia->estado !== 'prestado')
                                            <span class="badge bg-success">Disponible</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Prestado</span>
                                        @endif
                                    </td>

                                    <td class="align-middle">
                                        @if($copia->ubicacion)
                                            Estante: <strong>{{ $copia->ubicacion->estante ?? '-' }}</strong>
                                            / Sección: <strong>{{ $copia->ubicacion->seccion ?? '-' }}</strong>
                                        @else
                                            <span class="text-muted">Sin ubicación</span>
                                        @endif
                                    </td>

                                    <td class="text-end align-middle">
                                        {{-- Editar (abre modal) --}}
                                        <button class="btn btn-sm btn-outline-primary btn-edit-copia"
                                            data-id="{{ $copia->id_copia ?? $copia->id }}"
                                            data-estado="{{ $copia->estado ?? '' }}"
                                            data-id_ubicaciones="{{ $copia->id_ubicaciones ?? '' }}"
                                            data-estante="{{ $copia->ubicacion->estante ?? '' }}"
                                            data-seccion="{{ $copia->ubicacion->seccion ?? '' }}"
                                            title="Editar copia">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        {{-- Ver copia (opcional) --}}
                                        @if(Route::has('copias.show'))
                                        <a href="{{ route('copias.show', $copia->id_copia ?? $copia->id) }}" class="btn btn-sm btn-outline-secondary ms-1" title="Ver copia">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <a href="{{ route('libros.index') }}" class="btn btn-light">
        <i class="bi bi-arrow-left me-1"></i> Volver atrás
    </a>

</div>

{{-- Modal para editar copia --}}
<div class="modal fade" id="editCopiaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formEditCopia" method="POST">
      @csrf
      @method('PATCH')
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Editar copia</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="copia_id" name="copia_id">

            <div class="mb-3">
                <label class="form-label">Estado</label>
                <select name="estado" id="estado" class="form-select">
                    <option value="">Disponible</option>
                    <option value="prestado">Prestado</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Ubicación (seleccionar existente)</label>
                <select name="id_ubicaciones" id="id_ubicaciones" class="form-select">
                    <option value="">-- Seleccionar ubicación --</option>
                    @if(isset($ubicaciones) && $ubicaciones->count())
                        @foreach($ubicaciones as $u)
                            <option value="{{ $u->id }}">{{ $u->estante }} / {{ $u->seccion }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">O crear/editar ubicación (estante)</label>
                <input type="text" id="estante" name="estante" class="form-control" placeholder="Estante">
            </div>

            <div class="mb-3">
                <label class="form-label">Sección</label>
                <input type="text" id="seccion" name="seccion" class="form-control" placeholder="Sección">
            </div>

            <div class="form-text text-muted">
                Si seleccionas una ubicación existente, los campos Estante/Sección actualizarán esa ubicación. Si no seleccionas ninguna y completas Estante/Sección, se creará una nueva ubicación.
            </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editButtons = document.querySelectorAll('.btn-edit-copia');
    const modalEl = document.getElementById('editCopiaModal');
    const bsModal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('formEditCopia');

    // base url segura para construir la ruta de actualización
    const baseUrl = "{{ url('copias') }}";

    editButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const id = btn.dataset.id;
            const estado = btn.dataset.estado || '';
            const id_ubicaciones = btn.dataset.id_ubicaciones || '';
            const estante = btn.dataset.estante || '';
            const seccion = btn.dataset.seccion || '';

            document.getElementById('copia_id').value = id;
            document.getElementById('estado').value = estado;
            document.getElementById('id_ubicaciones').value = id_ubicaciones;
            document.getElementById('estante').value = estante;
            document.getElementById('seccion').value = seccion;

            // set action url dinámicamente (ej: /copias/12)
            form.action = baseUrl + '/' + encodeURIComponent(id);
            bsModal.show();
        });
    });

    // submit por fetch (AJAX) con CSRF token
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const id = document.getElementById('copia_id').value;
        const action = form.action;
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const formData = new FormData(form);
        // Aseguramos _method=PATCH
        formData.set('_method', 'PATCH');

        try {
            const res = await fetch(action, {
                method: 'POST', // Laravel acepta POST + _method=PATCH
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const json = await res.json();

            if (!res.ok) throw new Error(json.message || 'Error en servidor');

            // actualizar fila UI: estado y ubicacion
            const row = document.getElementById('copia-row-' + id);
            if (row) {
                const ubic = json.copia.ubicacion ?? null;

                // actualizar estado badge (columna 2)
                const estadoCell = row.children[1];
                if (json.copia.estado && json.copia.estado === 'prestado') {
                    estadoCell.innerHTML = '<span class="badge bg-warning text-dark">Prestado</span>';
                } else {
                    estadoCell.innerHTML = '<span class="badge bg-success">Disponible</span>';
                }

                // actualizar ubicacion cell (columna 3)
                const ubicCell = row.children[2];
                if (ubic) {
                    ubicCell.innerHTML = `Estante: <strong>${ubic.estante ?? '-'}</strong> / Sección: <strong>${ubic.seccion ?? '-'}</strong>`;
                } else {
                    ubicCell.innerHTML = '<span class="text-muted">Sin ubicación</span>';
                }

                // actualizar atributos data- del boton editar
                const editBtn = row.querySelector('.btn-edit-copia');
                if (editBtn) {
                    editBtn.dataset.estado = json.copia.estado ?? '';
                    editBtn.dataset.id_ubicaciones = json.copia.id_ubicaciones ?? '';
                    editBtn.dataset.estante = ubic ? (ubic.estante ?? '') : '';
                    editBtn.dataset.seccion = ubic ? (ubic.seccion ?? '') : '';
                }
            }

            bsModal.hide();

            // toast sencillo de éxito
            const toastHtml = `<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
                <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                  <div class="d-flex">
                    <div class="toast-body">Copia actualizada correctamente</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                  </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', toastHtml);
            const toastEl = document.querySelector('.toast');
            new bootstrap.Toast(toastEl, { delay: 2500 }).show();
            setTimeout(()=> toastEl.remove(), 3500);

        } catch (err) {
            console.error(err);
            alert('Error al actualizar la copia: ' + (err.message || 'Comprueba la consola'));
        }
    });
});
</script>
@endpush

@endsection
