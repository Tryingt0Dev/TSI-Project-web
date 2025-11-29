<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\Copia;
use App\Models\Alumno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrestamoController extends Controller
{
    public function index(Request $request)
    {
        $query = Prestamo::with(['alumno', 'user', 'copias'])->orderBy('created_at', 'desc');

        // filtro por estado (activo|devuelto|vencido)
        if ($request->filled('estado')) {
            $estadoFiltro = strtolower($request->estado);
            $query->where('estado', $estadoFiltro);
        }

        if ($request->filled('fecha_from')) {
            $query->whereDate('fecha_prestamo', '>=', $request->fecha_from);
        }

        if ($request->filled('fecha_to')) {
            $query->whereDate('fecha_devolucion_prevista', '<=', $request->fecha_to);
        }

        if ($request->filled('rut')) {
            $query->where('rut_alumno', 'like', '%' . $request->rut . '%');
        }

        if ($request->filled('nombre_alumno')) {
            $query->whereHas('alumno', function ($q) use ($request) {
                $q->where('nombre_alumno', 'like', '%' . $request->nombre_alumno . '%')
                  ->orWhere('apellido_alumno', 'like', '%' . $request->nombre_alumno . '%');
            });
        }

        $perPage = $request->input('per_page', 10);
        $prestamos = $query->paginate($perPage)->appends($request->all());

        return view('prestamos.index', compact('prestamos'));
    }

    public function detalle($id)
    {
        $prestamo = Prestamo::with(['alumno', 'copias.libro'])->findOrFail($id);
        return view('prestamos.detalle', compact('prestamo'));
    }

    public function show($id)
    {
        $prestamo = Prestamo::with(['alumno', 'copias.libro'])->findOrFail($id);
        if ($prestamo->estado === 'devuelto') {
            return redirect()->route('prestamos.index')->with('error', 'Este préstamo ya fue devuelto y no puede abrirse para edición.');
        }
        return view('prestamos.show', compact('prestamo'));
    }

    public function create()
    {
        // traer solo copias disponibles: normalizamos comparación en minúscula
        $copias = Copia::with('libro')
            ->whereRaw('LOWER(estado) = ?', ['disponible'])
            ->get();

        $alumnos = Alumno::orderBy('nombre_alumno')->get();

        return view('prestamos.create', compact('copias', 'alumnos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_usuario' => 'required|exists:users,id',
            'rut_alumno' => 'required|exists:alumnos,rut_alumno',
            'copias' => 'required|array|min:1',
            'copias.*' => 'required|integer|exists:copia,id_copia',
            'fecha_devolucion_prevista' => 'nullable|date|after_or_equal:today',
        ]);

        // convertir a ints
        $copiasIds = array_map('intval', $validated['copias']);
        $copiasModels = Copia::whereIn('id_copia', $copiasIds)->lockForUpdate()->get();

        if ($copiasModels->count() !== count($copiasIds)) {
            throw ValidationException::withMessages(['copias' => 'Alguna copia no existe.']);
        }

        // comprobar disponibilidad: comparar en minúscula
        foreach ($copiasModels as $c) {
            $estado = strtolower(trim((string) ($c->estado ?? '')));
            if ($estado === 'prestado') {
                throw ValidationException::withMessages(['copias' => "La copia {$c->id_copia} no está disponible."]);
            }
        }

        DB::transaction(function () use ($validated, $copiasModels, &$prestamo) {
            $prestamo = Prestamo::create([
                'id_usuario' => auth()->id(),
                'rut_alumno' => $validated['rut_alumno'],
                'fecha_prestamo' => now(),
                'fecha_devolucion_prevista' => $validated['fecha_devolucion_prevista'] ?? null,
                'estado' => 'activo',
            ]);

            $attach = [];
            foreach ($copiasModels as $c) {
                // marcar la copia como prestado (normalizado en minúscula)
                $c->estado = 'prestado';
                $c->save();
                // actualizar pivote con estados en minúscula
                $attach[$c->id_copia] = [
                    'estado' => 'prestado',
                    'fecha_prestamo' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            $prestamo->copias()->attach($attach);
        });

        return redirect()->route('prestamos.index')->with('success', 'Préstamo registrado correctamente.');
    }

    // actualizar estado de una copia específica dentro de un préstamo (ej: devolver una copia)
    public function updateCopia(Request $request, $idPrestamo, $idCopia)
    {
        $prestamo = Prestamo::findOrFail($idPrestamo);
        $nuevoEstado = strtolower(trim((string) $request->input('estado')));

        DB::transaction(function () use ($prestamo, $idCopia, $nuevoEstado) {
            $copia = Copia::findOrFail($idCopia);
            $copia->estado = $nuevoEstado === 'disponible' ? 'disponible' : $nuevoEstado;
            $copia->save();

            $copia_prestamo = ['estado' => $nuevoEstado];
            if ($nuevoEstado === 'disponible') {
                $copia_prestamo['fecha_devolucion_real'] = now();
            }

            $prestamo->copias()->updateExistingPivot($idCopia, $copia_prestamo);
        });

        if ($this->todasDevueltas($prestamo->id_prestamo)) {
            return redirect()->route('prestamos.comentario', $prestamo->id_prestamo);
        }

        return back()->with('success', "La copia $idCopia fue actualizada a $nuevoEstado");
    }

    protected function todasDevueltas($idPrestamo)
    {
        $prestamo = Prestamo::with('copias')->findOrFail($idPrestamo);
        // considerar copia devuelta si su estado en tabla 'copia' es 'disponible' (minúscula)
        return $prestamo->copias->every(fn($copia) => strtolower((string)$copia->estado) === 'disponible');
    }

    // Nuevo: actualizar solo el estado global del préstamo (marcar devuelto, vencido, etc.)
    public function updateEstado(Request $request, Prestamo $prestamo)
    {
        $data = $request->validate([
            'estado' => 'required|string|in:activo,devuelto,vencido',
        ]);

        $nuevoEstado = strtolower($data['estado']);

        DB::transaction(function() use ($prestamo, $nuevoEstado) {
            // actualizar prestamo
            $prestamo->estado = $nuevoEstado;
            if ($nuevoEstado === 'devuelto') {
                $prestamo->fecha_devolucion_real = now();
            }
            $prestamo->save();

            // actualizar pivot y copias: si devuelto => marcar copias como disponible
            if (method_exists($prestamo, 'copias')) {
                foreach ($prestamo->copias as $copia) {
                    // actualizar pivot
                    $prestamo->copias()->updateExistingPivot(
                        $copia->getKey(),
                        ['estado' => $nuevoEstado === 'devuelto' ? 'disponible' : $nuevoEstado, 'updated_at' => now()]
                    );

                    // actualizar tabla copia
                    if ($nuevoEstado === 'devuelto') {
                        $copia->estado = 'disponible';
                        $copia->save();
                    }
                }
            }
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado',
                'prestamo' => $prestamo->fresh('copias'),
            ]);
        }

        return redirect()->route('prestamos.index')->with('success', 'Estado actualizado.');
    }

    public function finalizar(Request $request, $idPrestamo)
    {
        $prestamo = Prestamo::findOrFail($idPrestamo);

        $request->validate([
            'observaciones' => 'required|string|max:1000',
        ]);

        $prestamo->estado = 'devuelto';
        $prestamo->fecha_devolucion_real = now();
        $prestamo->observaciones = $request->observaciones;
        $prestamo->save();

        return redirect()->route('prestamos.index')->with('success', 'Préstamo devuelto con comentario registrado');
    }

    public function comentario($id)
    {
        $prestamo = Prestamo::with(['alumno', 'copias.libro'])->findOrFail($id);

        // Verifica que todas las copias estén devueltas
        if (!$this->todasDevueltas($prestamo->id_prestamo)) {
            return redirect()->route('prestamos.show', $id)
                ->with('error', 'No puedes finalizar el préstamo hasta que todas las copias estén devueltas.');
        }

        return view('prestamos.comentario', compact('prestamo'));
    }

    public function destroy($id)
    {
        $prestamo = Prestamo::findOrFail($id);

        DB::transaction(function () use ($prestamo) {
            foreach ($prestamo->copias as $copia) {
                $copia->estado = 'disponible';
                $copia->save();
            }

            $prestamo->copias()->detach();

            $prestamo->delete();
        });

        return redirect()->route('prestamos.index')->with('success', 'Préstamo eliminado correctamente.');
    }
}