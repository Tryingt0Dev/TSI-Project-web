@extends('layouts.app')

@section('title', 'Lista de Libros')

@section('content')
<div class="container">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h4 mb-0">
            <i class="bi bi-journal-bookmark me-2 text-primary"></i> Lista de Libros
        </h1>

        <a href="{{ route('libros.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i> Agregar Libro
        </a>
    </div>

    {{-- Toast de éxito (si existe) --}}
    @if(session('success'))
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
            <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var toastEl = document.getElementById('successToast');
                if (toastEl) {
                    var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
                    toast.show();
                }
            });
        </script>
    @endif

    {{-- Filtros --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('libros.index') }}" class="row g-2">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Buscar</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Título o ISBN...">
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

    {{-- Tabla --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ISBN</th>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Género</th>
                            <th class="text-center">Stock total</th>
                            <th class="text-center">Stock disponible</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($libros as $libro)
                            <tr>
                                <td class="fw-semibold text-muted" style="min-width:130px;">{{ $libro->isbn_libro }}</td>

                                <td style="min-width:230px;">
                                    <div class="fw-semibold">{{ $libro->titulo }}</div>
                                </td>

                                <td>{{ $libro->autor->nombre ?? '-' }}</td>

                                <td>{{ $libro->genero->nombre ?? '-' }}</td>

                                {{-- Copias total (withCount fallback a stock_total) --}}
                                <td class="text-center fw-semibold">{{ $libro->copias_count ?? $libro->stock_total ?? 0 }}</td>

                                {{-- Copias disponibles --}}
                                <td class="text-center">
                                    @php
                                        $available = $libro->copias_disponibles_count ?? $libro->stock_disponible ?? 0;
                                    @endphp

                                    @if($available > 0)
                                        <span class="badge bg-success">{{ $available }}</span>
                                    @else
                                        <span class="badge bg-danger">Sin stock</span>
                                    @endif
                                </td>

                                {{-- Acciones --}}
                                <td class="text-end">
                                    <a href="{{ route('libros.edit', $libro->id_libro_interno) }}" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="tooltip" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <a href="{{ route('libros.detalle', $libro->id_libro_interno) }}" 
                                        class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="tooltip" title="Ver"><i class="bi bi-eye"></i>
                                    </a>

                                    <form action="{{ route('libros.destroy', $libro->id_libro_interno) }}" method="POST" style="display:inline"
                                        onsubmit="return confirm('¿Confirmas que quieres eliminar el libro: {{ addslashes($libro->titulo) }} ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No se encontraron libros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Paginación --}}
    <div class="mt-3 d-flex justify-content-center">
        {{ $libros->links() }}
    </div>
</div>

{{-- Modal global de confirmación (necesario para que btn-confirm funcione) --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmTitle">Confirmar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <p id="confirmText">¿Estás seguro de realizar esta acción?</p>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button id="confirmYes" type="button" class="btn btn-danger">Sí, eliminar</button>
      </div>
    </div>
  </div>
</div>

@endsection
