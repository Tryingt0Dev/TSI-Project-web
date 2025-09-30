@extends('layouts.app')

@section('title', 'Catálogo de Libros')

@section('content')
<div class="container py-4">
    <h1 class="welc mb-4 text-center">Bienvenid@ a la Biblioteca</h1>

    
    <form method="GET" action="{{ route('home') }}" class="row mb-4 g-2">
        <div class="col-md-4">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                   placeholder="Buscar por título o ISBN...">
        </div>

        <div class="col-md-3">
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
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    
    <div class="row row-cols-1 row-cols-md-4 g-4">
        @forelse($libros as $libro)
            <div class="col">
                <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden hover-scale">
                    <img src="https://covers.openlibrary.org/b/isbn/{{ $libro->isbn_libro }}-L.jpg"
                         class="card-img-top"
                         alt="{{ $libro->titulo }}"
                         onerror="this.src='/images/no_cover.png'">

                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-truncate" title="{{ $libro->titulo }}">{{ $libro->titulo }}</h5>
                        <p class="card-text mb-2">
                            <strong>Autor:</strong> {{ $libro->autor->nombre ?? 'Desconocido' }}<br>
                            <strong>Edición:</strong> {{ $libro->edicion ?? 'N/A' }}<br>
                            <strong>Género:</strong> {{ $libro->genero->nombre ?? 'N/A' }}
                        </p>

                        <div class="mt-auto">
                            <span class="badge bg-primary me-1">Total: {{ $libro->stock_total }}</span>
                            <span class="badge bg-success">Disponible: {{ $libro->stock_disponible }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center">No se encontraron libros con los filtros aplicados.</p>
        @endforelse
    </div>

    
    <div class="d-flex justify-content-center mt-4">
        {{ $libros->withQueryString()->links() }}
    </div>
</div>
@endsection
