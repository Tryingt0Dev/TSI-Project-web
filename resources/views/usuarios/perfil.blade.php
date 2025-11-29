@extends('layouts.app')

@section('title', 'Mi Perfil')

@section('content')
<div class="container py-4">

    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person-circle me-2"></i>Mi Perfil
                    </h5>
                </div>

                <div class="card-body">

                    <div class="text-center mb-4">
                        <i class="bi bi-person-circle text-primary" style="font-size: 4rem"></i>
                        <h4 class="mt-2">{{ $user->name }} {{ $user->apellido }}</h4>
                        <span class="badge bg-dark">
                            {{ $user->rol == 0 ? 'Administrador' : 'Bibliotecario' }}
                        </span>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="fw-semibold text-muted">Correo electrónico</label>
                        <div class="form-control bg-light">{{ $user->email }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-semibold text-muted">Usuario ID</label>
                        <div class="form-control bg-light">{{ $user->id }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-semibold text-muted">Fecha de creación</label>
                        <div class="form-control bg-light">{{ $user->created_at->format('Y-m-d H:i') }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-semibold text-muted">Última actualización</label>
                        <div class="form-control bg-light">{{ $user->updated_at->format('Y-m-d H:i') }}</div>
                    </div>

                    <div class="text-end mt-4">
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>

</div>
@endsection
