@extends('layouts.app')

@section('title', 'Lista de Libros')

@section('content')
<div class="container">
    <h1 class="mb-4">Lista de Libros</h1>

    {{-- Bootstrap Toast para mensajes de éxito --}}
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
                var toast = new bootstrap.Toast(toastEl, { delay: 3000 }); // 3 segundos
                toast.show();
            });
        </script>
    @endif
    <div class="mb-3">
        <form method="GET" action="{{ route('libros.index') }}" class="row mb-4 g-2">
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
            <a href="{{ route('libros.create') }}" class="btn btn-primary mb-3">Agregar Libro</a>
        </form>
       
    </div>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Editorial</th>
                <th>Autor</th>
                <th>Género</th>
                <th>Stock</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($libros as $libro)
                <tr>
                    <td>{{ $libro->titulo }}</td>
                    <td>{{ $libro->editorial }}</td>
                    <td>{{ $libro->autor->nombre ?? $libro->id_detalle_autores }}</td>
                    <td>{{ $libro->genero->nombre ?? $libro->genero_literario }}</td>
                    <td>{{ $libro->stock_disponible }}/{{ $libro->stock_total }}</td>
                    <td class="d-flex gap-2">
                        <a href="{{ route('libros.edit', $libro->id) }}" class="btn btn-sm btn-warning">Editar</a>

                        <form action="{{ route('libros.destroy', $libro->id) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar este libro?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <p class="text-center">No se encontraron libros con los filtros aplicados.</p>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
