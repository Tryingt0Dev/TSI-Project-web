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
                    <div class="mb-2">
                        <strong>Autores:</strong>
                        {{ $libro->autores->pluck('nombre')->join(', ') ?: 'Desconocido' }}
                    </div>
                    <div class="mb-2"><strong>Género:</strong> {{ $libro->genero->nombre ?? 'N/A' }}</div>
                    <div class="mb-2"><strong>Editorial:</strong> {{ $libro->editorial ?? 'N/A' }}</div>
                    <div class="mb-2"><strong>Fecha de publicación:</strong> {{ $libro->fecha_publicacion ?? 'N/A' }}</div>
                    <div class="mb-2"><strong>Stock total (columna):</strong> {{ $libro->stock_total ?? 0 }}</div>
                    <div class="mb-2"><strong>Stock disponible (columna):</strong> {{ $libro->stock_disponible ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Copias (cargadas por AJAX) --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Copias</strong>
            <small id="copias-count" class="text-muted">{{ $libro->copias->count() }}</small>
        </div>

        <div class="card-body p-3">
            <div id="copias-container">
                <div id="copias-list" class="mb-3">
                    <div class="text-center text-muted py-4">Cargando copias…</div>
                </div>

                <nav id="copias-pagination" aria-label="Paginación de copias"></nav>
            </div>
        </div>
    </div>

    <a href="{{ route('libros.index') }}" class="btn btn-light">
        <i class="bi bi-arrow-left me-1"></i> Volver atrás
    </a>

</div>

{{-- Modal para editar copia (dinámico) --}}
<div class="modal fade" id="editCopiaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formEditCopia" method="POST">
      @csrf
      @method('PATCH')
      <div class="modal-content">
        <div class="modal-header d-flex align-items-center justify-content-between">
          <h5 class="modal-title">Editar copia</h5>

          <div class="btn-group" role="group" aria-label="Modo ubicacion/estado">
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnModoExistente">Seleccionar existente</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnModoCrear">Crear / Editar</button>
          </div>

          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">
            <input type="hidden" id="copia_id" name="copia_id">

            <div id="seccionExistente" class="mb-3">
                <label class="form-label">Estado (seleccionar existente)</label>
                <select name="estado" id="estado" class="form-select">
                    <option value="">Disponible</option>
                    <option value="prestado">Prestado</option>
                    @if(isset($estados) && $estados->count())
                        @foreach($estados as $e)
                            <option value="{{ $e->nombre }}">{{ $e->nombre }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div id="seccionCrearEstado" class="mb-3" style="display:none;">
                <label class="form-label">Crear/Editar Estado</label>
                <input type="text" id="nuevo_estado" name="nuevo_estado" class="form-control" placeholder="Ej: Reparación, Perdido...">
                <div class="form-text">Si completas este campo, se usará como estado de la copia.</div>
            </div>

            <div id="seccionUbicExistente" class="mb-3">
                <label class="form-label">Ubicación (seleccionar existente)</label>
                <select name="id_ubicacion" id="id_ubicacion" class="form-select">
                    <option value="">-- Seleccionar ubicación --</option>
                    @if(isset($ubicaciones) && $ubicaciones->count())
                        @foreach($ubicaciones as $u)
                            <option value="{{ $u->id_ubicacion }}">{{ $u->estante }} / {{ $u->seccion }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div id="seccionCrearUbic" style="display:none;">
                <div class="mb-3">
                    <label class="form-label">Estante (crear/editar)</label>
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
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Config
    const libroId = {{ json_encode($libro->id_libro_interno) }};
    const perPage = 10;
    const copiasListEl = document.getElementById('copias-list');
    const paginationEl = document.getElementById('copias-pagination');
    const copiasCountEl = document.getElementById('copias-count');

    // Modal elements
    const modalEl = document.getElementById('editCopiaModal');
    const bsModal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('formEditCopia');

    const seccionExistente = document.getElementById('seccionExistente');
    const seccionCrearEstado = document.getElementById('seccionCrearEstado');
    const seccionUbicExistente = document.getElementById('seccionUbicExistente');
    const seccionCrearUbic = document.getElementById('seccionCrearUbic');

    const btnModoExistente = document.getElementById('btnModoExistente');
    const btnModoCrear = document.getElementById('btnModoCrear');

    const baseCopiaUrl = "{{ url('copias') }}"; // /copias

    function activarModoExistente() {
        seccionExistente.style.display = '';
        seccionUbicExistente.style.display = '';
        seccionCrearEstado.style.display = 'none';
        seccionCrearUbic.style.display = 'none';
        btnModoExistente.classList.add('active');
        btnModoCrear.classList.remove('active');
    }
    function activarModoCrear() {
        seccionExistente.style.display = 'none';
        seccionUbicExistente.style.display = 'none';
        seccionCrearEstado.style.display = '';
        seccionCrearUbic.style.display = '';
        btnModoCrear.classList.add('active');
        btnModoExistente.classList.remove('active');
    }

    // Inicial
    activarModoExistente();
    btnModoExistente.addEventListener('click', activarModoExistente);
    btnModoCrear.addEventListener('click', activarModoCrear);

    // Load copias (AJAX paginado)
    async function loadCopias(page = 1) {
        copiasListEl.innerHTML = '<div class="text-center text-muted py-4">Cargando copias…</div>';
        paginationEl.innerHTML = '';

        try {
            const res = await fetch(`/libros/${encodeURIComponent(libroId)}/copias-disponibles?page=${page}&per_page=${perPage}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error('Error al cargar copias');
            const json = await res.json();

            renderCopias(json);
        } catch (err) {
            console.error(err);
            copiasListEl.innerHTML = '<div class="text-danger py-3">Error al cargar copias. Revisa logs.</div>';
        }
    }

    // Render tabla y paginación
    function renderCopias(payload) {
        const data = payload.data || [];
        const current = payload.current_page || 1;
        const last = payload.last_page || 1;
        const total = payload.total || 0;

        copiasCountEl.textContent = total;

        if (data.length === 0) {
            copiasListEl.innerHTML = '<div class="text-muted py-3">No hay copias disponibles.</div>';
            paginationEl.innerHTML = '';
            return;
        }

        // Construir tabla
        const table = document.createElement('table');
        table.className = 'table table-hover mb-0';
        table.innerHTML = `
            <thead class="table-light">
                <tr>
                    <th>ID Copia</th>
                    <th>Estado</th>
                    <th>Ubicación</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
        `;
        const tbody = document.createElement('tbody');

        data.forEach(c => {
            const tr = document.createElement('tr');
            tr.id = 'copia-row-' + (c.id_copia ?? '');
            const estado = c.estado ?? '';
            let estadoHtml = '';
            if (!estado) {
                estadoHtml = '<span class="badge bg-success">Disponible</span>';
            } else if (estado === 'prestado') {
                estadoHtml = '<span class="badge bg-warning text-dark">Prestado</span>';
            } else if (['perdido','dañado','roto'].includes(estado.toLowerCase())) {
                estadoHtml = `<span class="badge bg-danger">${estado}</span>`;
            } else {
                estadoHtml = `<span class="badge bg-secondary text-white">${estado}</span>`;
            }

            const ubicacionText = c.ubicacion ? ( (c.ubicacion.estante ?? '-') + ' / ' + (c.ubicacion.seccion ?? '-') ) : '<span class="text-muted">Sin ubicación</span>';

            tr.innerHTML = `
                <td class="align-middle">${c.id_copia ?? ''}</td>
                <td class="align-middle estado-cell">${estadoHtml}</td>
                <td class="align-middle ubic-cell">${c.ubicacion ? `Estante: <strong>${c.ubicacion.estante ?? '-'}</strong> / Sección: <strong>${c.ubicacion.seccion ?? '-'}</strong>` : '<span class="text-muted">Sin ubicación</span>'}</td>
                <td class="text-end align-middle">
                    <button class="btn btn-sm btn-outline-primary btn-edit-copia"
                        data-id="${c.id_copia ?? ''}"
                        data-update-url="${c.update_url ?? (baseCopiaUrl + '/' + encodeURIComponent(c.id_copia ?? ''))}"
                        data-estado="${c.estado ?? ''}"
                        data-id_ubicacion="${c.id_ubicacion ?? ''}"
                        data-estante="${c.ubicacion ? (c.ubicacion.estante ?? '') : ''}"
                        data-seccion="${c.ubicacion ? (c.ubicacion.seccion ?? '') : ''}"
                        title="Editar copia">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        table.appendChild(tbody);
        copiasListEl.innerHTML = '';
        copiasListEl.appendChild(table);

        // Paginación
        paginationEl.innerHTML = '';
        const ul = document.createElement('ul');
        ul.className = 'pagination justify-content-center mt-3';

        const createPageItem = (p, label = null, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = 'page-item' + (active ? ' active' : '') + (disabled ? ' disabled' : '');
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.innerText = label || p;
            a.addEventListener('click', function (e) { e.preventDefault(); if (!disabled) loadCopias(p); });
            li.appendChild(a);
            return li;
        };

        // Prev
        ul.appendChild(createPageItem(Math.max(1, current - 1), '«', false, current === 1));

        // simple range: muestra hasta 7 páginas centradas
        const start = Math.max(1, current - 3);
        const end = Math.min(last, current + 3);
        for (let p = start; p <= end; p++) {
            ul.appendChild(createPageItem(p, null, p === current));
        }

        // Next
        ul.appendChild(createPageItem(Math.min(last, current + 1), '»', false, current === last));

        paginationEl.appendChild(ul);

        // Asociar listeners a botones editar recién renderizados
        copiasListEl.querySelectorAll('.btn-edit-copia').forEach(btn => {
            btn.addEventListener('click', openEditModalFromButton);
        });
    }

    // Abrir modal y rellenar campos
    function openEditModalFromButton(evt) {
        const btn = evt.currentTarget;
        const id = btn.dataset.id || '';
        const estado = btn.dataset.estado || '';
        const id_ubicacion = btn.dataset.id_ubicacion || '';
        const estante = btn.dataset.estante || '';
        const seccion = btn.dataset.seccion || '';

        document.getElementById('copia_id').value = id;
        document.getElementById('estado').value = estado;
        document.getElementById('id_ubicacion').value = id_ubicacion;
        document.getElementById('estante').value = estante;
        document.getElementById('seccion').value = seccion;
        document.getElementById('nuevo_estado').value = '';

        form.action = btn.dataset.updateUrl || (baseCopiaUrl + '/' + encodeURIComponent(id));

        activarModoExistente();
        bsModal.show();
    }

    // submit del modal (AJAX)
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const copiaId = document.getElementById('copia_id').value;
        const action = form.action;
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const token = tokenMeta ? tokenMeta.getAttribute('content') : '';

        const formData = new FormData(form);
        formData.set('_method', 'PATCH');

        // Priorizar nuevo_estado si está en modo crear
        if (seccionCrearEstado.style.display !== 'none') {
            const nuevoEstado = document.getElementById('nuevo_estado').value.trim();
            if (nuevoEstado) {
                formData.set('nuevo_estado', nuevoEstado);
                formData.set('estado', nuevoEstado);
            }
        } else {
            const estadoSel = document.getElementById('estado').value;
            formData.set('estado', estadoSel);
        }

        // Ubicación: crear o seleccionar
        if (seccionCrearUbic.style.display !== 'none') {
            const est = document.getElementById('estante').value.trim();
            const sec = document.getElementById('seccion').value.trim();
            if (est || sec) {
                formData.set('id_ubicacion', '');
                formData.set('_crear_ubicacion', '1');
                formData.set('estante', est);
                formData.set('seccion', sec);
            }
        } else {
            const idU = document.getElementById('id_ubicacion').value;
            formData.set('id_ubicacion', idU);
        }

        try {
            const res = await fetch(action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const json = await res.json();

            if (!res.ok) throw new Error(json.message || 'Error al actualizar');

            // actualizar fila si existe
            const row = document.getElementById('copia-row-' + copiaId);
            if (row) {
                // estado
                const estadoVal = (json.copia && json.copia.estado) || formData.get('estado') || '';
                const estadoCell = row.querySelector('.estado-cell');
                let badgeHtml = '';
                if (!estadoVal) {
                    badgeHtml = '<span class="badge bg-success">Disponible</span>';
                } else if (estadoVal === 'prestado') {
                    badgeHtml = '<span class="badge bg-warning text-dark">Prestado</span>';
                } else if (['perdido','dañado','roto'].includes(estadoVal.toLowerCase())) {
                    badgeHtml = `<span class="badge bg-danger">${estadoVal}</span>`;
                } else {
                    badgeHtml = `<span class="badge bg-secondary text-white">${estadoVal}</span>`;
                }
                if (estadoCell) estadoCell.innerHTML = badgeHtml;

                // ubicacion
                const ubicCell = row.querySelector('.ubic-cell');
                const ubic = (json.copia && json.copia.ubicacion) || null;
                if (ubic) {
                    ubicCell.innerHTML = `Estante: <strong>${ubic.estante ?? '-'}</strong> / Sección: <strong>${ubic.seccion ?? '-'}</strong>`;
                } else {
                    ubicCell.innerHTML = '<span class="text-muted">Sin ubicación</span>';
                }

                // actualizar atributos del botón
                const editBtn = row.querySelector('.btn-edit-copia');
                if (editBtn && json.copia) {
                    editBtn.dataset.estado = json.copia.estado ?? '';
                    editBtn.dataset.id_ubicacion = json.copia.id_ubicacion ?? '';
                    editBtn.dataset.estante = (json.copia.ubicacion && json.copia.ubicacion.estante) ? json.copia.ubicacion.estante : '';
                    editBtn.dataset.seccion = (json.copia.ubicacion && json.copia.ubicacion.seccion) ? json.copia.ubicacion.seccion : '';
                    editBtn.dataset.updateUrl = editBtn.dataset.updateUrl || (baseCopiaUrl + '/' + encodeURIComponent(copiaId));
                }
            }

            // close modal
            bsModal.hide();

            // toast success
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

    // Carga inicial
    loadCopias(1);
});
</script>
@endpush
