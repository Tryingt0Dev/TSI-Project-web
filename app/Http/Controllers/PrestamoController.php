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
    public function index(Request $request) // Portada de los prestamos
    {
        $query = Prestamo::with(['alumno', 'user', 'copias'])->orderBy('created_at', 'desc');

        if ($request->filled('fecha_from')) { $query->whereDate('fecha_prestamo', '>=', $request->fecha_from);}

        if ($request->filled('fecha_to')) { $query->whereDate('fecha_devolucion_prevista', '<=', $request->fecha_to);}

        if ($request->filled('rut')) { $query->where('rut_alumno', 'like', '%' . $request->rut . '%');}

        if ($request->filled('nombre_alumno')) { $query->whereHas('alumno', function ($q) use ($request) {
                $q->where('nombre_alumno', 'like', '%' . $request->nombre_alumno . '%')
                ->orWhere('apellido_alumno', 'like', '%' . $request->nombre_alumno . '%');});}

        $perPage = $request->input('per_page', 10);
        $prestamos = $query->paginate($perPage)->appends($request->all());

        return view('prestamos.index', compact('prestamos'));
    }

    public function detalle($id) // Vista para ver los detalles del prestamo
    {
        $prestamo = Prestamo::with(['alumno', 'copias.libro'])->findOrFail($id);
        return view('prestamos.detalle', compact('prestamo'));
    }

    public function show($id) // Vista para poder editar el prestamo (solo cuando aun no este devuelto)
    {
        $prestamo = Prestamo::with(['alumno', 'copias.libro'])->findOrFail($id);
        if ($prestamo->estado === 'devuelto') { return redirect()->route('prestamos.index')->with('error', 'Este préstamo ya fue devuelto y no puede abrirse para edición.');}
        return view('prestamos.show', compact('prestamo'));
    }

    public function create() // Muestra formulario de creación.
    {
        $copias = Copia::with('libro')
            ->where('estado', 'Disponible')->get(); // traer solo copias disponibles

        $alumnos = Alumno::orderBy('nombre_alumno')->get();

        return view('prestamos.create', compact('copias', 'alumnos'));
    }

    public function store(Request $request) // Crea el prestamo y asocia las copias (1 o mas)
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
        return redirect()->route('prestamos.index')->with('success', 'Préstamo registrado correctamente.');
    }

    public function destroy($id) // borra el prestamo (sigue en uso su clave primaria)
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

    public function entregarTodas($idPrestamo) // entrega todas las copias del prestamo (el prestamo queda Devuelto)
    {
        $prestamo = Prestamo::with('copias')->findOrFail($idPrestamo);

        DB::transaction(function () use ($prestamo) {
            foreach ($prestamo->copias as $copia) {
                $copia->estado = 'Disponible';
                $copia->save();

                $copia_prestamo = [
                    'estado' => 'Disponible',
                    'fecha_devolucion_real' => now(),
                ];
                $prestamo->copias()->updateExistingPivot($copia->id_copia, $copia_prestamo);
            }

            // Si todas están devueltas, marcamos el préstamo como devuelto
            if ($prestamo->copias->every(fn($c) => $c->estado === 'Disponible')) {
                $prestamo->estado = 'Devuelto';
                $prestamo->fecha_devolucion_real = now();
                $prestamo->save();
            }
        });

        return redirect()->route('prestamos.index')->with('success', 'Todas las copias fueron entregadas y están disponibles.');
    }

    public function updateCopiasYComentario(Request $request, $idPrestamo) // Para poder entregar las copias con mas detalle y agregar un comentario
    {
        $prestamo = Prestamo::findOrFail($idPrestamo);

        DB::transaction(function () use ($request, $prestamo) {
            // Actualizar copias
            foreach ($request->input('copias', []) as $idCopia => $estado) {
                $copia = Copia::findOrFail($idCopia);
                $copia->estado = $estado;
                $copia->save();

                $pivotData = ['estado' => $estado];
                if ($estado === 'Disponible') {
                    $pivotData['fecha_devolucion_real'] = now();
                }
                $prestamo->copias()->updateExistingPivot($idCopia, $pivotData);
            }

            // Actualizar observaciones
            $prestamo->observaciones = trim($request->input('observaciones'));
            $prestamo->save();

            // Si todas están devueltas, marcamos el préstamo como devuelto
            if ($prestamo->copias->every(fn($c) => $c->estado === 'Disponible')) {
                $prestamo->estado = 'Devuelto';
                $prestamo->fecha_devolucion_real = now();
                $prestamo->save();
            }
        });

        return redirect()->route('prestamos.detalle', $prestamo->id_prestamo)->with('success', 'Copias y observaciones actualizadas correctamente.');
    }

}

    