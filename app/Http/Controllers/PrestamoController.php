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

class PrestamoController extends Controller
{
    public function index(){
    $prestamos = Prestamo::paginate(10);
    return view('prestamos.index', compact('prestamos'));
    }

    public function create(){
    $copias = Copia::with('libro')->get();
    $alumnos = Alumno::all();
    
    return view('prestamos.create', compact('copias','alumnos'));
    }

    public function destroy($id)
    {
        $prestamo = Prestamo::findOrFail($id);
        $prestamo->delete();
        return redirect()->route('prestamos.index')->with('success', 'Préstamo eliminado correctamente.'); 
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_copia' => 'required|exists:copia,id_copia',
            'fecha_prestamo' => 'required|date|after_or_equal:today',
            'fecha_limite' => 'required|date|after:fecha_limite',
            'rut_alumno' => 'required|string|max:20',
        ]);
        Prestamo::create([
            'id_copia' => $request->copia_id,
            'fecha_inicio' => now(),
            'fecha_limite' => $request->fecha_limite,
            'id_usuario' => auth()->id(),
            'rut_alumno' => $request->rut_alumno,
        ]);
        $copia = Copia::findOrFail($request->id_copia);
        $copia->estado = 'Prestada';
        $copia->save();
        dd($request->all());

        return redirect()->route('prestamos.index')->with('success', 'Préstamo registrado correctamente.');
    }

    /**
     * Mostrar detalle de un préstamo
     */
    public function show($id)
    {
        $prestamo = Prestamo::with(['alumno', 'user', 'copias.libro'])->findOrFail($id);
        return view('prestamos.show', compact('prestamo'));
    }

    /**
     * Marcar una copia como devuelta (por préstamo).
     * Ruta sugerida: PATCH /prestamos/{prestamo}/devolver/{copia}
     */
    public function devolverCopia(Request $request, $idPrestamo, $idCopia)
    {
        $prestamo = Prestamo::findOrFail($idPrestamo);

        // verificar que la copia está asociada a este préstamo
        $pivot = DB::table('prestamo_copia')
            ->where('id_prestamo', $prestamo->id_prestamo)
            ->where('id_copia', $idCopia)
            ->first();

        if (! $pivot) {
            return back()->with('error', 'La copia no pertenece a este préstamo.');
        }

        DB::transaction(function () use ($prestamo, $idCopia) {
            // actualizar pivot
            DB::table('prestamo_copia')->where('id_prestamo', $prestamo->id_prestamo)
                ->where('id_copia', $idCopia)
                ->update([
                    'devuelto' => true,
                    'fecha_devolucion_real' => now(),
                    'updated_at' => now(),
                ]);

            // actualizar estado de la copia en su tabla
            Copia::where('id_copia', $idCopia)->update(['estado' => 'disponible']);

            // si todas las copias del préstamo ya están devueltas, marcar préstamo como devuelto
            $restantes = DB::table('prestamo_copia')
                ->where('id_prestamo', $prestamo->id_prestamo)
                ->where('devuelto', false)
                ->count();

            if ($restantes === 0) {
                $prestamo->estado = 'devuelto';
                $prestamo->fecha_devolucion_real = now();
                $prestamo->save();
            }
        });

        return back()->with('success', 'Copia devuelta correctamente.');
    }

    /**
     * Eliminar (soft) un préstamo.
     */
    public function destroy($id)
    {
        $prestamo = Prestamo::findOrFail($id);

        // antes de soft-delete, si el préstamo está activo y tiene copias no devueltas,
        // devolverlas virtualmente o evitar delete según tu política. Aquí permitimos borrar
        // pero devolvemos las copias asociadas (las marcamos disponibles).
        DB::transaction(function () use ($prestamo) {
            // obtener copias activas (no devueltas)
            $copiasActivas = DB::table('prestamo_copia')
                ->where('id_prestamo', $prestamo->id_prestamo)
                ->where('devuelto', false)
                ->pluck('id_copia');

            if ($copiasActivas->isNotEmpty()) {
                // marcar copias como disponibles
                Copia::whereIn('id_copia', $copiasActivas->toArray())->update(['estado' => 'disponible']);

                // marcar pivot como devuelto
                DB::table('prestamo_copia')
                    ->where('id_prestamo', $prestamo->id_prestamo)
                    ->whereIn('id_copia', $copiasActivas->toArray())
                    ->update(['devuelto' => true, 'fecha_devolucion_real' => now(), 'updated_at' => now()]);
            }

            $prestamo->delete(); // soft delete
        });

        return redirect()->route('prestamos.index')->with('success', 'Préstamo eliminado correctamente.');
    }
}
}
