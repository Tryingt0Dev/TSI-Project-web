@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3>Préstamos</h3>

        <div>
            <a href="{{ route('prestamos.index') }}" class="btn btn-outline-secondary btn-sm me-1 {{ request('estado') ? '' : 'active' }}">Todos</a>
            <a href="{{ route('prestamos.index', array_merge(request()->except('page'), ['estado' => 'activo'])) }}" class="btn btn-outline-primary btn-sm me-1 {{ request('estado') === 'activo' ? 'active' : '' }}">Pendientes</a>
            <a href="{{ route('prestamos.index', array_merge(request()->except('page'), ['estado' => 'devuelto'])) }}" class="btn btn-outline-success btn-sm me-1 {{ request('estado') === 'devuelto' ? 'active' : '' }}">Devueltos</a>
            <a href="{{ route('prestamos.index', array_merge(request()->except('page'), ['estado' => 'vencido'])) }}" class="btn btn-outline-warning btn-sm {{ request('estado') === 'vencido' ? 'active' : '' }}">Vencidos</a>
        </div>
    </div>

    {{-- filtros existentes (fecha_from, fecha_to, rut, nombre_alumno, per_page) --}}
    <form class="row g-3 mb-4" method="GET" action="{{ route('prestamos.index') }}">
        <div class="col-auto">
            <input type="date" class="form-control" name="fecha_from" value="{{ request('fecha_from') }}" placeholder="Desde">
        </div>
        <div class="col-auto">
            <input type="date" class="form-control" name="fecha_to" value="{{ request('fecha_to') }}" placeholder="Hasta">
        </div>
        <div class="col-auto">
            <input type="text" class="form-control" name="rut" placeholder="RUT" value="{{ request('rut') }}">
        </div>
        <div class="col-auto">
            <input type="text" class="form-control" name="nombre_alumno" placeholder="Nombre alumno" value="{{ request('nombre_alumno') }}">
        </div>
        <div class="col-auto">
            <select name="per_page" class="form-select">
                @foreach([10,25,50,100] as $n)
                    <option value="{{ $n }}" {{ request('per_page',10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" type="submit">Filtrar</button>
        </div>
        <div class="col-auto">
            <a href="{{ route('prestamos.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Nuevo préstamo
            </a>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Alumno</th>
                        <th>Usuario</th>
                        <th>Copias </th>
                        <th>Fecha préstamo</th>
                        <th>Fecha prevista</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prestamos as $p)
                    <tr id="prestamo-row-{{ $p->getKey() }}">
                        <td class="align-middle">{{ $p->getKey() }}</td>
                        <td class="align-middle">{{ $p->alumno->rut_alumno ?? 'N/A' }} - {{ $p->alumno->nombre_alumno ?? ($p->alumno->nombre ?? '') }}</td>
                        <td class="align-middle">{{ $p->user->name ?? 'Sistema' }}</td>
                        <td class="align-middle">
                            @foreach($p->copias as $c)
                                <div>Cop {{ $c->getKey() }} — {{ $c->pivot->estado ?? '' }}</div>
                            @endforeach
                        </td>
                        <td class="align-middle">{{ optional($p->fecha_prestamo)->format('Y-m-d') }}</td>
                        <td class="align-middle">{{ optional($p->fecha_devolucion_prevista)->format('Y-m-d') }}</td>
                        <td class="align-middle">
                            @if($p->estado === 'activo')
                                <span class="badge bg-primary">Pendiente</span>
                            @elseif($p->estado === 'devuelto')
                                <span class="badge bg-success">Devuelto</span>
                            @elseif($p->estado === 'vencido')
                                <span class="badge bg-warning text-dark">Vencido</span>
                            @else
                                <span class="badge bg-secondary">{{ $p->estado }}</span>
                            @endif
                        </td>
                        <td class="text-end align-middle">
                            <div class="d-inline-flex gap-1">
                                {{-- Ver detalle (usa tu ruta personalizada) --}}
                                <a href="{{ route('prestamos.detalle', $p->getKey()) }}" class="btn btn-sm btn-outline-secondary" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>

                                {{-- Editar (resource edit). Deshabilitado si ya está devuelto --}}
                                

                                {{-- Marcar devuelto (solo si no está devuelto) --}}
                                @if($p->estado !== 'devuelto')
                                    <button class="btn btn-sm btn-success btn-mark-returned"
                                            data-id="{{ $p->getKey() }}"
                                            data-update-url="{{ route('prestamos.update.estado', $p->getKey()) }}"
                                            title="Marcar devuelto">
                                        <i class="bi bi-arrow-counterclockwise"></i>Marcar Devuelto
                                    </button>
                                @else
                                    <span class="text-muted align-self-center px-2">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-3">No hay préstamos</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $prestamos->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

{{-- Modal confirm --}}
<div class="modal fade" id="confirmReturnModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formMarkReturned" method="POST">
      @csrf
      @method('PATCH')
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirmar devolución</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>¿Deseas marcar este préstamo como <strong>devuelto</strong>? Esta acción actualizará el estado del préstamo y de las copias asociadas.</p>
          <input type="hidden" id="prestamo_id" name="prestamo_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">Sí, marcar devuelto</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('confirmReturnModal');
    const bsModal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('formMarkReturned');
    let currentUrl = '';
    let currentId = null;

    document.querySelectorAll('.btn-mark-returned').forEach(btn => {
        btn.addEventListener('click', function () {
            currentId = btn.dataset.id;
            currentUrl = btn.dataset.updateUrl;
            document.getElementById('prestamo_id').value = currentId;
            form.action = currentUrl;
            bsModal.show();
        });
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!currentUrl) return;

        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const token = tokenMeta ? tokenMeta.getAttribute('content') : '';

        const fd = new FormData();
        fd.set('_method', 'PATCH');
        fd.set('estado', 'devuelto');

        try {
            const res = await fetch(currentUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: fd
            });

            const json = await res.json().catch(()=>null);

            if (!res.ok) throw new Error((json && json.message) ? json.message : 'Error servidor');

            // actualizar UI: badge y quitar botón
            const row = document.getElementById('prestamo-row-' + currentId);
            if (row) {
                const estadoCell = row.querySelector('td:nth-child(7)');
                if (estadoCell) estadoCell.innerHTML = '<span class="badge bg-success">Devuelto</span>';

                const actionCell = row.querySelector('td:last-child');
                if (actionCell) {
                    // reemplazar acciones por ver + edit(disabled) + placeholder
                    actionCell.innerHTML = `
                      <div class="d-inline-flex gap-1">
                        <a href="#" class="btn btn-sm btn-outline-secondary" title="Ver detalle"><i class="bi bi-eye"></i></a>
                        <button class="btn btn-sm btn-outline-primary" disabled title="Editar"><i class="bi bi-pencil-square"></i></button>
                        <span class="text-muted align-self-center px-2">—</span>
                      </div>
                    `;
                }
            }

            bsModal.hide();

            // toast
            const toastHtml = `<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
                <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                  <div class="d-flex">
                    <div class="toast-body">Préstamo marcado como devuelto</div>
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
            alert('No se pudo actualizar el estado: ' + (err.message || 'Revisa la consola'));
        }
    });
});
</script>
@endpush
