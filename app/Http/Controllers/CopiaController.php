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
            'id_libro_interno' => 'required|exists:libros,id_libro_interno',
            // aceptamos texto libre aquí, lo normalizamos antes de guardar
            'estado' => ['nullable','string','max:50'],
            'id_ubicacion' => 'nullable|exists:ubicaciones,id_ubicacion',
        ]);

        // Normalizar estado (prioriza 'estado' input)
        $rawEstado = $request->input('estado') ?? null;
        $data['estado'] = Copia::normalizeEstado($rawEstado);

        $copia = Copia::create($data);

        if ($copia->libro && method_exists($copia->libro, 'recalcularStock')) {
            $copia->libro->recalcularStock();
        }

        return response()->json($copia->load('ubicacion'), Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $copia = Copia::with('libro','ubicacion')->findOrFail($id);
        return response()->json($copia);
    }

    public function update(Request $request, Copia $copia)
    {
        $validated = $request->validate([
            'id_ubicacion' => 'nullable|exists:ubicaciones,id_ubicacion',
            // aceptamos texto libre y nuevo_estado libre, luego normalizamos
            'estado' => ['nullable','string','max:50'],
            'nuevo_estado' => ['nullable','string','max:50'],
            'estante' => 'nullable|string|max:100',
            'seccion' => 'nullable|string|max:100',
        ]);

        // --- Manejo de estado con normalización: prioridad a nuevo_estado ---
        $rawEstado = $request->input('nuevo_estado') ?? $request->input('estado') ?? null;
        $normalized = Copia::normalizeEstado($rawEstado);
        $copia->estado = $normalized; // ahora $normalized es la etiqueta exacta que la DB espera


        // --- Manejo de ubicacion: actualizar existente o crear nueva ---
        if (!empty($validated['estante']) || !empty($validated['seccion'])) {
            if (!empty($validated['id_ubicacion'])) {
                $ubic = Ubicacion::where('id_ubicacion', $validated['id_ubicacion'])->first();
                if ($ubic) {
                    $ubic->estante = $validated['estante'] ?? $ubic->estante;
                    $ubic->seccion = $validated['seccion'] ?? $ubic->seccion;
                    $ubic->save();
                    $copia->id_ubicacion = $ubic->id_ubicacion;
                }
            } else {
                $ubic = Ubicacion::create([
                    'estante' => $validated['estante'] ?? null,
                    'seccion' => $validated['seccion'] ?? null,
                ]);
                $copia->id_ubicacion = $ubic->id_ubicacion;
            }
        } else {
            if (array_key_exists('id_ubicacion', $validated)) {
                $copia->id_ubicacion = $validated['id_ubicacion'] ?: null;
            }
        }

        $copia->save();

        if ($copia->libro && method_exists($copia->libro, 'recalcularStock')) {
            $copia->libro->recalcularStock();
        }

        $copia->load('ubicacion');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Copia actualizada correctamente',
                'copia' => $copia,
            ]);
        }

        return redirect()->back()->with('success', 'Copia actualizada correctamente');
    }

    public function destroy($id)
    {
        $copia = Copia::findOrFail($id);
        $copia->delete();

        if ($copia->libro && method_exists($copia->libro, 'recalcularStock')) {
            $copia->libro->recalcularStock();
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
