<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Genero;
use Illuminate\Support\Str;

class GeneroController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255'
        ]);

        $nombre = trim($request->nombre);
        if ($nombre === '') {
            return response()->json(['message' => 'Nombre de género inválido.'], 422);
        }

        // Buscamos case-insensitive para no duplicar
        $genero = Genero::whereRaw('LOWER(nombre) = ?', [Str::lower($nombre)])->first();

        if (! $genero) {
            $genero = Genero::create([
                'nombre' => $nombre
            ]);
        }

        return response()->json([
            'id' => $genero->id_genero,
            'nombre' => $genero->nombre
        ], 201);
    }
}
