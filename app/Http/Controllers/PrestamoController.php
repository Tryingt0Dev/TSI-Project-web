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
    /**
     * Mostrar listado de préstamos (paginado).
     */
    public function index(Request $request)
    {
        // cargamos relaciones útiles (alumno, user, copias)
        $prestamos = Prestamo::with(['alumno', 'user', 'copias'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('prestamos.index', compact('prestamos'));
    }

    /**
     * Mostrar formulario de creación.
     * Traemos solo copias disponibles para seleccionar.
     */
    public function create()
    {
        // traer copias disponibles (no prestadas)
        $copias = Copia::with('libro')
            ->where(function($q){
                $q->whereNull('estado')->orWhere('estado', '<>', 'prestado')->orWhere('estado','<>','Prestada');
            })
            ->get();

        $alumnos = Alumno::orderBy('nombre_alumno')->get();

        return view('prestamos.create', compact('copias', 'alumnos'));
    }

    /**
     * Guardar préstamo y asociar copias (puede ser 1 o varias).
     * Espera:
     *  - rut_alumno
     *  - copias -> array de id_copia
     *  - fecha_devolucion_prevista (opcional)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rut_alumno' => 'required|string|exists:alumnos,rut_alumno',
            'copias' => 'required|array|min:1',
            'copias.*' => 'required|integer|exists:copia,id_copia',
            'fecha_devolucion_prevista' => 'nullable|date|after_or_equal:today',
            'observaciones' => 'nullable|string|max:1000',
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
            if ($estado === 'prestado' || $estado === 'prestada') {
                throw ValidationException::withMessages(['copias' => "La copia {$c->id_copia} no está disponible."]);
            }
        }

        DB::transaction(function () use ($validated, $copiasModels, &$prestamo) {
            // crear registro del préstamo
            $prestamo = Prestamo::create([
                'user_id' => auth()->id(),
                'rut_alumno' => $validated['rut_alumno'],
                'fecha_prestamo' => now(),
                'fecha_devolucion_prevista' => $validated['fecha_devolucion_prevista'] ?? null,
                'estado' => 'activo',
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // asociar copias y marcar cada copia como prestada
            $attach = [];
            foreach ($copiasModels as $c) {
                // actualizar estado de la copia
                $c->estado = 'prestado';
                $c->save();

                $attach[$c->id_copia] = [
                    'fecha_asignacion' => now(),
                    'devuelto' => false,
                ];
            }

            $prestamo->copias()->attach($attach);
        });

    }
    
}

    