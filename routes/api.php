<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LibroController;
use App\Http\Controllers\CopiaController;





Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//crud
Route::apiResource('libros', LibroController::class);

//copias
Route::resource('copias', CopiaController::class);