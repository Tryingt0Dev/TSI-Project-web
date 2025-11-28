<?php

namespace App\Http\Controllers;

use App\Models\Copia;
use Illuminate\Http\Request;
use App\Models\Ubicacion;
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
        ]);

        $copia = Copia::create($data);

        return response()->json($copia, Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $copia = Copia::with('libro')->findOrFail($id);
        return response()->json($copia);
    }

    public function update(Request $request, Copia $copia)
    {
        // Validación básica (permitimos nuevo_estado como string opcional)
        $validated = $request->validate([
            'id_ubicacion' => 'nullable|exists:ubicaciones,id',
            'estado' => 'nullable|string|max:50',
            'nuevo_estado' => 'nullable|string|max:50',
            'estante' => 'nullable|string|max:100',
            'seccion' => 'nullable|string|max:100',
        ]);

        // --- Manejo de estado ---
        if (!empty($validated['nuevo_estado'])) {
            $copia->estado = $validated['nuevo_estado'];
        } elseif (array_key_exists('estado', $validated)) {
            $copia->estado = $validated['estado'] ?: null;
        }
        // --- FIN estado ---

        // --- Manejo de ubicacion ---
        if (!empty($validated['estante']) || !empty($validated['seccion'])) {
            if (!empty($validated['id_ubicacion'])) {
                $ubic = Ubicacion::find($validated['id_ubicacion']);
                if ($ubic) {
                    $ubic->estante = $validated['estante'] ?? $ubic->estante;
                    $ubic->seccion = $validated['seccion'] ?? $ubic->seccion;
                    $ubic->save();
                    $copia->id_ubicacion = $ubic->id;
                }
            } else {
                $ubic = Ubicacion::create([
                    'estante' => $validated['estante'] ?? null,
                    'seccion' => $validated['seccion'] ?? null,
                ]);
                $copia->id_ubicacion = $ubic->id;
            }
        } else {
            if (array_key_exists('id_ubicacion', $validated)) {
                $copia->id_ubicacion = $validated['id_ubicacion'] ?: null;
            }
        }
        // --- FIN ubicacion ---

        $copia->save();

        // Recalcular stock si aplicable
        if ($copia->libro && method_exists($copia->libro, 'recalcularStock')) {
            $copia->libro->recalcularStock();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Copia actualizada correctamente',
                'copia' => $copia->load('ubicacion'),
            ]);
        }

        return redirect()->back()->with('success', 'Copia actualizada correctamente');
    }


    public function destroy($id)
    {
        $copia = Copia::findOrFail($id);
        $copia->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}