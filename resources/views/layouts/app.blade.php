<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>@yield('title', 'Biblioteca')</title>

    {{-- Bootstrap CSS (CDN) - colocamos antes para que Vite CSS lo pueda sobrescribir si hace falta --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    {{-- CSRF meta para JS --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Vite: inyecta JS y CSS (única llamada, ordenada) --}}
    @vite([
        'resources/js/app.js',
        'resources/css/app.css',
        'resources/css/style.css'
    ])

    @stack('head')
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm fixed-top w-100"
     style="background:#1f2937 !important; z-index: 1050;">

    <div class="container-fluid">

        {{-- LOGO --}}
        <a class="navbar-brand d-flex align-items-center text-white" href="{{ url('/') }}">
            <i class="bi bi-book-half fs-4 me-2 text-primary"></i>
            <span class="fw-bold">Biblioteca</span>
        </a>

        {{-- MENÚ MÓVIL --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- MENÚ --}}
        <div class="collapse navbar-collapse" id="navbarNav">

            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('home') ? 'active-nav' : '' }}" href="{{ url('/home') }}">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('libros') ? 'active-nav' : '' }}" href="{{ url('/libros') }}">Libros</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('alumnos*') ? 'active-nav' : '' }}" href="{{ route('alumnos.index') }}">
                        Alumnos
                    </a>
                </li>
                @auth
                    @if(Auth::user()->rol == 0)
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('usuarios') ? 'active-nav' : '' }}" href="{{ route('usuarios.index') }}">Usuarios</a>
                        </li>
                    @endif
                @endauth

                <li class="nav-item">
                    <a class="nav-link {{ request()->is('prestamos') ? 'active-nav' : '' }}" href="{{ url('/prestamos') }}">Préstamos</a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto">
                @guest
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Ingresar</a></li>
                @else
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-4 me-2 text-primary"></i>
                            <div class="d-none d-md-block text-start">
                                <div class="small fw-semibold">{{ Auth::user()->name }} {{ Auth::user()->apellido }}</div>
                                <div class="small text-secondary">{{ Auth::user()->rol == 0 ? 'Administrador' : 'Bibliotecario' }}</div>
                            </div>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><a class="dropdown-item" href="{{ route('usuario.perfil') }}">Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="#"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    Cerrar sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                @endguest
            </ul>

        </div>
    </div>
</nav>

<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>

<main class="container" style="padding: top 29px;">
    <a> <br></a>     
    @yield('content')
</main>

{{-- Confirm modal (reusable) --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center p-4">
        <i class="bi bi-exclamation-triangle display-5 text-warning mb-3"></i>
        <h5 id="confirmTitle">¿Estás seguro?</h5>
        <p id="confirmText" class="small text-muted">Esta acción no se puede deshacer.</p>
        <div class="d-flex justify-content-center gap-2 mt-3">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" id="confirmYes" class="btn btn-danger">Sí, eliminar</button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Bootstrap JS (CDN) --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

@stack('scripts')
</body>
</html>
