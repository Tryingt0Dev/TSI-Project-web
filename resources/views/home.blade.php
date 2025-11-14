@extends('layouts.app')

@section('title', 'Catálogo de Libros')

@section('content')
<div class="container py-4">

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
                           placeholder="Título o ISBN...">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Autor</label>
                    <select name="autor" class="form-select">
                        <option value="">-- Todos los autores --</option>
                        @foreach($autores as $autor)
                            <option value="{{ $autor->id }}" {{ request('autor') == $autor->id ? 'selected' : '' }}>
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
                            <option value="{{ $genero->id }}" {{ request('genero') == $genero->id ? 'selected' : '' }}>
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

                    <div class="card-body d-flex flex-column">

                        {{-- Título --}}
                        <h5 class="card-title fw-bold text-truncate" title="{{ $libro->titulo }}">
                            {{ $libro->titulo }}
                        </h5>

                        {{-- Info --}}
                        <p class="card-text mb-3 text-muted small">
                            <strong>Autor:</strong> {{ $libro->autor->nombre ?? 'Desconocido' }} <br>
                            <strong>Género:</strong> {{ $libro->genero->nombre ?? 'N/A' }}
                        </p>

                        {{-- Badges stock --}}
                        <div class="mt-auto">
                            <span class="badge bg-primary">Total: {{ $libro->stock_total }}</span>

                            @if($libro->stock_disponible > 0)
                                <span class="badge bg-success">Disponible: {{ $libro->stock_disponible }}</span>
                            @else
                                <span class="badge bg-danger">Sin stock</span>
                            @endif
                        </div>

                    </div>
                </div>
            </div>

        @empty
            <p class="text-center text-muted">No se encontraron libros con los filtros aplicados.</p>
        @endforelse
    </div>

    {{-- Paginación --}}
    <div class="d-flex justify-content-center mt-4">
        {{ $libros->withQueryString()->links() }}
    </div>

</div>

{{-- Estilo para efectos hover --}}
<style>
    .book-card {
        transition: transform .15s ease, box-shadow .2s ease;
    }

    .book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    }
</style>
@endsection
