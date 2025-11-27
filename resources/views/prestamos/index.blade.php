@extends('layouts.app')

@section('title', 'Lista de Prestamos')

@section('content')
<div class="container">
        {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4">
            <i class="bi bi-receipt text-primary me-2"></i>
            Gestión de Prestamos (solo visual)
        </h1>

        <a href="{{ route('prestamos.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i> Crear Prestamo
        </a>
    </div>

</div>
@endsection