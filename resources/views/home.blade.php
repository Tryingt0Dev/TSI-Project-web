@extends('layouts.app')

@section('title', 'Catálogo de Libros')
{{-- CORRECT LINK TO CSS --}}
<link rel="stylesheet" href="{{ asset('css/home.css') }}">

@section('content')
<div class="container py-4">
    
    {{-- Small inline styles to keep card footer pinned to bottom and clamp text --}}
    <style>
      /* Ensure card body is column-flex so footer can be pushed to bottom */
      .book-card .book-card-body { display: flex; flex-direction: column; height: 100%; }
      .card-footer-book { margin-top: auto; display: flex; flex-direction: column; gap: .5rem; }

      /* clamp text (2 lines) */
      .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }

      /* optional: limit authors long text inside small element */
      .book-info { flex: 1 1 auto; }
    </style>

    {{-- Título elegante --}}
    <div class="text-center mb-4">
        <h1 class="fw-bold" style="color:#1f2937;">
            <i class="bi bi-book-half me-2 text-primary"></i>
            Bienvenid@ a la Biblioteca {{ Auth::user()->name }}!
        </h1>
        <p class="text-muted">Explora el catálogo y encuentra tu próximo libro favorito</p>
    </div>

    {{-- Card de filtros --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('home') }}" class="row g-2">

                <div class="col-md-4">
                    <label class="form-label small text-muted">Buscar</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                           placeholder="Título, ISBN, autor o género...">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Autor</label>
                    <select name="autor" class="form-select">
                        <option value="">-- Todos los autores --</option>
                        @foreach($autores as $autor)
                            <option value="{{ $autor->id_autor }}" {{ request('autor') == $autor->id_autor ? 'selected' : '' }}>
                                {{ $autor->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Género</label>
                    <select name="genero" class="form-select">
                        <option value="">-- Todos los géneros --</option>
                        @foreach($generos as $genero)
                            <option value="{{ $genero->id_genero }}" {{ request('genero') == $genero->id_genero ? 'selected' : '' }}>
                                {{ $genero->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel-fill me-1"></i> Filtrar
                    </button>
                </div>

            </form>
        </div>
    </div>
    {{-- Paginación --}}
    <div class="mt-7 d-flex justify-content-center  ">
        {{ $libros->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
    {{-- Grid de libros --}}
    <div class="row row-cols-1 row-cols-md-4 g-4">
        @forelse($libros as $libro)
            <div class="col">
                <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden book-card">

                    {{-- Imagen --}}
                    <img src="https://covers.openlibrary.org/b/isbn/{{ $libro->isbn_libro }}-L.jpg"
                         class="card-img-top"
                         alt="{{ $libro->titulo }}"
                         onerror="this.src='/images/no_cover.png'">

                    <div class="card-body book-card-body p-3">

                        {{-- Título --}}
                        <h5 class="card-title fw-bold line-clamp-2 mb-2" title="{{ $libro->titulo }}">
                            {{ $libro->titulo }}
                        </h5>

                        {{-- Info (flexible) --}}
                        <div class="book-info">
                            <p class="card-text mb-2 text-muted small line-clamp-2">
                                <strong>Autores:</strong> {{ $libro->autores->pluck('nombre')->join(', ') ?: 'Desconocido' }} <br>
                                <strong>Género:</strong> {{ $libro->genero->nombre ?? 'N/A' }}
                            </p>
                        </div>

                        {{-- Footer: badges + boton (siempre abajo) --}}
                        <div class="card-footer-book">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary">Total: {{ $libro->stock_total ?? 0 }}</span>
                                @if(($libro->stock_disponible ?? 0) > 0)
                                    <span class="badge bg-success">Disponible: {{ $libro->stock_disponible }}</span>
                                @else
                                    <span class="badge bg-danger">Sin stock</span>
                                @endif
                            </div>

                            <div class="d-flex align-items-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary btn-prestar"
                                        data-libro-id="{{ $libro->id_libro_interno }}"
                                        data-libro-titulo="{{ e($libro->titulo) }}">
                                    <i class="bi bi-hand-index-thumb me-1"></i> Prestar
                                </button>
                            </div>

                            <div class="d-flex align-items-center mt-2">
                                <div class="action-btns d-inline-flex align-items-center" role="group" aria-label="Acciones libro">
                                    <a href="{{ route('libros.edit', $libro->id_libro_interno) }}"
                                       class="btn btn-sm btn-outline-primary btn-action" data-bs-toggle="tooltip" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <a href="{{ route('libros.detalle', $libro->id_libro_interno) }}"
                                       class="btn btn-sm btn-outline-secondary btn-action mx-1" data-bs-toggle="tooltip" title="Ver">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div> {{-- card-footer-book --}}
                    </div> {{-- card-body --}}
                </div>
            </div>
        @empty
            <p class="text-center text-muted">No se encontraron libros con los filtros aplicados.</p>
        @endforelse
    </div>

    {{-- MODAL DE PRÉSTAMO --}}
    <div class="modal fade" id="modalPrestamo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
                <div class="modal-header bg-primary text-white rounded-top">
                    <h5 class="modal-title"><i class="bi bi-journal-arrow-up me-2"></i> Registrar Préstamo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form id="formPrestamo" action="{{ route('prestamos.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Administrador</label>
                            <input type="text" class="form-control"
                                value="{{ Auth::user()->name }} {{ Auth::user()->apellido }}"
                                readonly>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Alumno (RUT)</label>
                                <select name="rut_alumno" id="modal_rut_alumno" class="form-select" required>
                                    <option value="">Seleccione un alumno</option>
                                    @foreach($alumnos as $alumno)
                                        <option value="{{ $alumno->rut_alumno }}">
                                            {{ $alumno->rut_alumno }} — {{ $alumno->nombre_alumno }} {{ $alumno->apellido_alumno }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Copia del libro</label>
                                <select name="id_copia" id="modal_id_copia" class="form-select" required>
                                    <option value="">Seleccione una copia disponible</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fecha límite</label>
                                <input type="date" name="fecha_limite" id="modal_fecha_limite"
                                    class="form-control" min="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Observaciones (opcional)</label>
                                <textarea name="observaciones" id="modal_observaciones"
                                        class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3" id="modalLibroTitulo" style="display:none;"></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Guardar préstamo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-prestar').forEach(btn => {
            btn.addEventListener('click', async function () {
                const libroId = this.dataset.libroId ?? this.dataset.libro;
                const titulo = this.dataset.libroTitulo ?? this.dataset.titulo ?? '';

                if (!libroId) {
                    console.error('Falta el data-libro-id en el botón');
                    return;
                }

                const msg = document.getElementById('modalLibroTitulo');
                if (msg) { msg.style.display = 'block'; msg.textContent = "Libro seleccionado: " + (titulo || libroId); }

                const select = document.getElementById('modal_id_copia');
                if (select) {
                    select.innerHTML = `<option value="">Cargando copias...</option>`;
                    try {
                        const res = await fetch(`/api/libro/${libroId}/copias-disponibles`);
                        if (!res.ok) throw new Error('Error cargando copias: ' + res.status);
                        const copias = await res.json();
                        select.innerHTML = `<option value="">Seleccione una copia</option>`;
                        if (!Array.isArray(copias) || copias.length === 0) {
                            select.innerHTML += `<option disabled>No hay copias disponibles</option>`;
                        } else {
                            copias.forEach(c => {
                                const idC = c.id_copia ?? c.id;
                                const ubic = c.ubicacion ?? (c.ubicacion_nombre ?? 'Sin ubicación');
                                const opt = document.createElement('option');
                                opt.value = idC;
                                opt.textContent = `Copia #${idC} — ${ubic}`;
                                select.appendChild(opt);
                            });
                        }
                    } catch (err) {
                        console.error(err);
                        select.innerHTML = `<option value="">Error cargando copias</option>`;
                    }
                }

                const modalEl = document.getElementById('modalPrestamo');
                if (modalEl) new bootstrap.Modal(modalEl).show();
            });
        });
    });
    </script>
    @endpush

    {{-- Paginación --}}
    <div class="mt-7 d-flex justify-content-center  ">
        {{ $libros->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
