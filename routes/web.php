<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LibroController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\Auth\LoginController;
use App\Models\Libro;

Route::get('/', function () {
    return redirect('/home');
});

// Login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Home
Route::get('/home', function () {
    $libros = Libro::with(['autor', 'genero'])->get();
    return view('home', compact('libros'));
})->middleware('auth');

// Libros
Route::resource('libros', LibroController::class);
Route::get('/buscar-libro', [LibroController::class, 'buscarLibro'])->name('buscar-libro');

// Usuarios

Route::get('/usuarios', [UsuarioController::class, 'index'])->middleware('auth');

// Prestamos

Route::get('/prestamos', [PrestamoController::class, 'index'])->middleware('auth');