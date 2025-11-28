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
    public function index(Request $request) // Mostrar listado de préstamos (paginado).
    {
        $prestamos = Prestamo::with(['alumno', 'user', 'copias'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('prestamos.index', compact('prestamos'));
    }

    public function create() // Mostrar formulario de creación.
    {
        $copias = Copia::with('libro')
            ->where('estado', 'Disponible') // traer solo copias disponibles
            ->get();

        $alumnos = Alumno::orderBy('nombre_alumno')->get();

        return view('prestamos.create', compact('copias', 'alumnos'));
    }

    public function store(Request $request) // Guarda el prestamo y asocia las copias (1 o mas)
    {
        $validated = $request->validate([
            'id_usuario' => 'required|exists:users,id',
            'rut_alumno' => 'required|exists:alumnos,rut_alumno',
            'copias' => 'required|array|min:1',
            'copias.*' => 'required|integer|exists:copia,id_copia',
            'fecha_devolucion_prevista' => 'nullable|date|after_or_equal:today',
        ]);

        // Verificar disponibilidad de cada copia antes de comenzar transacción
        $copiasIds = array_map('intval', $validated['copias']);
        $copiasModels = Copia::whereIn('id_copia', $copiasIds)->lockForUpdate()->get();

        // comprobar que todas existan y estén disponibles
        if ($copiasModels->count() !== count($copiasIds)) {
            throw ValidationException::withMessages(['copias' => 'Alguna copia no existe.']);
        }
        foreach ($copiasModels as $c) {
            $estado = strtolower(trim((string) ($c->estado ?? '')));
            if ($estado === 'Prestado') {
                throw ValidationException::withMessages(['copias' => "La copia {$c->id_copia} no está disponible."]);
            }
        }

        DB::transaction(function () use ($validated, $copiasModels, &$prestamo) {
            $prestamo = Prestamo::create([ // Crea registro del préstamo
                'id_usuario' => auth()->id(),
                'rut_alumno' => $validated['rut_alumno'],
                'fecha_prestamo' => now(),
                'fecha_devolucion_prevista' => $validated['fecha_devolucion_prevista'] ?? null,
                'estado' => 'activo',
            ]);

            // asocia copias y marcar cada copia como prestada
            $attach = [];
            foreach ($copiasModels as $c) {
                // actualiza el estado de la copia en la tabla principal
                $c->estado = 'Prestado';
                $c->save();
                // actualiza el estado de la copia en el pivote 
                $attach[$c->id_copia] = [
                    'estado' => 'Prestado',
                    'fecha_prestamo' => now(),
                ];
            }
            $prestamo->copias()->attach($attach);
        });
    }

    public function updateCopia(Request $request, $idPrestamo, $idCopia) // deja actualizar individualmente las copias para entregarlas
    {
        $prestamo = Prestamo::findOrFail($idPrestamo);
        $nuevoEstado = $request->input('estado');

        DB::transaction(function () use ($prestamo, $idCopia, $nuevoEstado) {
            
            $copia = Copia::findOrFail($idCopia);
            $copia->estado = $nuevoEstado;
            $copia->save();

            $copia_prestamo = ['estado' => $nuevoEstado];
            if ($nuevoEstado === 'Disponible') {
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
        return $prestamo->copias->every(fn($copia) => $copia->estado === 'Disponible');
    }

    public function finalizar(Request $request, $idPrestamo)
    {
    $prestamo = Prestamo::findOrFail($idPrestamo);

    $request->validate([
        'observaciones' => 'required|string|max:1000',
    ]);

    $prestamo->estado = 'Devuelto';
    $prestamo->fecha_devolucion_real = now();
    $prestamo->observaciones = $request->observaciones;
    $prestamo->save();

    return redirect()->route('prestamos.index')->with('success', 'Préstamo devuelto con comentario registrado');
    }

    public function destroy($id)
    {
        $prestamo = Prestamo::findOrFail($id);

        DB::transaction(function () use ($prestamo) {
            
            foreach ($prestamo->copias as $copia) {
                $copia->estado = 'Disponible';
                $copia->save();
            }

            $prestamo->copias()->detach();

            $prestamo->delete();
        });

        return redirect()->route('prestamos.index')->with('success', 'Préstamo eliminado correctamente.');
    }

}

    