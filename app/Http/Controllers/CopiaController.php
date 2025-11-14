<?php

namespace App\Http\Controllers;

use App\Models\Copia;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CopiaController extends Controller
{
    public function index()
    {
        $copias = Copia::with('libro')->paginate(20);
        return response()->json($copias);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_libro_interno' => 'required|exists:libro,id_libro_interno',
            'estado' => 'nullable|string',
            'ubicacion' => 'nullable|string',
        ]);

        $copia = Copia::create($data);

        return response()->json($copia, Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $copia = Copia::with('libro')->findOrFail($id);
        return response()->json($copia);
    }

    public function update(Request $request, $id)
    {
        $copia = Copia::findOrFail($id);

        $data = $request->validate([
            'estado' => 'nullable|string',
            'ubicacion' => 'nullable|string',
        ]);

        $copia->update($data);

        return response()->json($copia);
    }

    public function destroy($id)
    {
        $copia = Copia::findOrFail($id);
        $copia->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}