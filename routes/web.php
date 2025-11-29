<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LibroController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CopiaController;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\GeneroController;
use App\Models\Libro;

Route::get('/', function () {
    return redirect('/home');
});

// Login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout');

// Home
Route::get('/home', function () {
    $libros = Libro::with(['autor', 'genero'])->get();
    return view('home', compact('libros'));
})->middleware('auth');
Route::get('/home', [LibroController::class, 'catalogo'])
    ->name('home')
    ->middleware('auth');
// Libros
Route::resource('libros', LibroController::class);
Route::get('/buscar-libro', [LibroController::class, 'buscarLibro'])->name('buscar-libro');
Route::get('/libros/{libro}/detalle', [LibroController::class, 'detalle'])
     ->name('libros.detalle');
Route::get('/api/libro/{id}/copias-disponibles', [LibroController::class, 'copiasDisponibles'])
    ->name('api.libro.copias-disponibles')
    ->middleware('auth');
// generos
Route::post('/api/generos', [GeneroController::class, 'store'])
    ->name('api.generos.store')
    ->middleware('auth');

// Usuarios

Route::middleware(['auth', CheckRole::class.':0'])->group(function () {
    Route::resource('usuarios', UsuarioController::class);
});
// Prestamos

Route::resource('prestamos', \App\Http\Controllers\PrestamoController::class);
Route::patch('/prestamos/{idPrestamo}/copias/{idCopia}', [PrestamoController::class, 'updateCopia'])->name('prestamos.updateCopia');
Route::get('/prestamos/{id}/comentario', [PrestamoController::class, 'comentario'])->name('prestamos.comentario');
Route::get('/prestamos/{id}/detalle', [PrestamoController::class, 'detalle'])->name('prestamos.detalle');
Route::post('/prestamos/{id}/finalizar', [PrestamoController::class, 'finalizar'])->name('prestamos.finalizar');

//alumnos
Route::middleware(['auth'])->group(function () {
    Route::resource('alumnos', AlumnoController::class)->except(['create','store','show']);
});