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
        // Validación
        $validated = $request->validate([
            'id_ubicaciones' => 'nullable|exists:ubicaciones,id',
            'estado' => 'nullable|string|max:50', // ejemplo: 'prestado' / 'disponible'
            // opcional: permitir editar estante/seccion si prefieres crear/editar ubicacion inline
            'estante' => 'nullable|string|max:100',
            'seccion' => 'nullable|string|max:100',
        ]);

        // Si el formulario envía estante/seccion y no id_ubicaciones, podemos crear/actualizar una ubicacion
        if (isset($validated['estante']) || isset($validated['seccion'])) {
            // Si se pasó id_ubicaciones, actualizamos esa ubicación; si no, creamos nueva
            if (!empty($validated['id_ubicaciones'])) {
                $ubic = Ubicacion::find($validated['id_ubicaciones']);
                if ($ubic) {
                    $ubic->estante = $validated['estante'] ?? $ubic->estante;
                    $ubic->seccion = $validated['seccion'] ?? $ubic->seccion;
                    $ubic->save();
                    $copia->id_ubicaciones = $ubic->id;
                }
            } else {
                // crear nueva ubicacion
                $ubic = Ubicacion::create([
                    'estante' => $validated['estante'] ?? null,
                    'seccion' => $validated['seccion'] ?? null,
                ]);
                $copia->id_ubicaciones = $ubic->id;
            }
        } else {
            // solo actualizar FK o estado
            if (array_key_exists('id_ubicaciones', $validated)) {
                $copia->id_ubicaciones = $validated['id_ubicaciones'];
            }
        }

        if (array_key_exists('estado', $validated)) {
            // normalizar estado (ejemplo: si checkbox-> disponible -> null / 'prestado')
            $copia->estado = $validated['estado'];
        }

        $copia->save();

        // Recalcular stock en libro (si tu modelo lo tiene)
        if ($copia->libro) {
            if (method_exists($copia->libro, 'recalcularStock')) {
                $copia->libro->recalcularStock();
            }
        }

        // Si es petición AJAX respondemos JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Copia actualizada correctamente',
                'copia' => $copia->load('ubicacion'),
            ]);
        }

        // fallback: redirigir
        return redirect()->back()->with('success', 'Copia actualizada correctamente');
    }

    public function destroy($id)
    {
        $copia = Copia::findOrFail($id);
        $copia->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}