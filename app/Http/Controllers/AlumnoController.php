<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class AlumnoController extends Controller
{
    /**
     * Mostrar listado con búsqueda, filtros y paginación.
     */
    public function index(Request $request)
    {
        $q = $request->query('q');
        $permiso = $request->query('permiso'); // '', '1', '0'
        $min_atrasos = $request->query('min_atrasos');

        $query = Alumno::query();

        // Búsqueda por nombre, apellido o RUT
        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->where('nombre_alumno', 'LIKE', "%{$q}%")
                    ->orWhere('apellido_alumno', 'LIKE', "%{$q}%")
                    ->orWhere('rut_alumno', 'LIKE', "%{$q}%");
            });
        }

        // Filtro permiso_préstamo (si se seleccionó)
        if ($permiso !== null && $permiso !== '') {
            $query->where('permiso_prestamo', (bool) $permiso);
        }

        // Filtro mínimo de atrasos
        if ($min_atrasos !== null && $min_atrasos !== '') {
            $query->where('atrasos', '>=', (int)$min_atrasos);
        }

        // Ordena por fecha de registro (últimos primero)
        $alumnos = $query->orderByDesc('fecha_registro')->paginate(15)->withQueryString();

        return view('alumnos.index', compact('alumnos', 'q', 'permiso', 'min_atrasos'));
    }

    /**
     * Mostrar formulario de creación.
     */
    public function create()
    {
        return view('alumnos.create');
    }

    /**
     * Guardar nuevo alumno.
     */
    public function store(Request $request)
    {
        // Validación básica (longitudes generosas, unicidad la manejamos manualmente)
        $data = $request->validate([
            'rut_alumno' => ['required','string','max:50'],
            'nombre_alumno' => ['required','string','max:60'],
            'apellido_alumno' => ['required','string','max:60'],
            'fecha_registro' => ['nullable','date'],
            'atrasos' => ['nullable','integer','min:0'],
        ]);

        // 1) Normalizar input: quitar puntos y espacios
        $inputRut = strtoupper(trim($data['rut_alumno']));
        $inputRut = str_replace(['.', ' '], '', $inputRut); // elimina puntos/espacios

        // 2) Separar número y DV según reglas:
        // - Si tiene guion: "12345678-9" -> num=12345678, dv=9
        // - Si NO tiene guion: se **asume** que el último carácter es DV (según tu petición)
        $numPart = null;
        $dvPart = null;

        if (strpos($inputRut, '-') !== false) {
            // Formato con guion
            [$numPart, $dvPart] = explode('-', $inputRut, 2);
            $numPart = preg_replace('/\D/', '', $numPart);
            $dvPart = $dvPart !== '' ? strtoupper(preg_replace('/[^0-9Kk]/', '', $dvPart)) : null;
        } else {
            // Sin guion: tomar último carácter como DV (puede ser número o K)
            // Ej: "209913844" => numPart="20991384", dvPart="4"
            $clean = preg_replace('/\s+/', '', $inputRut);
            $len = mb_strlen($clean);
            if ($len < 2) {
                return back()->withInput()->withErrors(['rut_alumno' => 'Formato de RUT inválido.']);
            }
            $dvPart = strtoupper(mb_substr($clean, -1));
            $numPart = preg_replace('/\D/', '', mb_substr($clean, 0, $len - 1));
        }

        // Validaciones básicas
        if (!$numPart || !preg_match('/^\d+$/', $numPart)) {
            return back()->withInput()->withErrors(['rut_alumno' => 'Formato de RUT inválido (número incorrecto).']);
        }
        if (!$dvPart || !preg_match('/^[0-9K]$/i', $dvPart)) {
            return back()->withInput()->withErrors(['rut_alumno' => 'Formato de DV inválido.']);
        }

        // 3) Calcular DV y comparar (si DV provisto)
        $dvCalc = \App\Models\User::calcRutDv($numPart);
        if (strtoupper($dvPart) !== strtoupper($dvCalc)) {
            // DV no coincide: devolvemos error (no inventamos ni sobreescribimos)
            return back()->withInput()->withErrors(['rut_alumno' => 'Dígito verificador (DV) incorrecto para el RUT ingresado.']);
        }

        // 4) Formar RUT final con guion, preservando exactamente el DV proporcionado.
        $finalRut = $numPart . '-' . strtoupper($dvPart);

        // 5) Normalizar para buscar duplicados (sin puntos ni guion)
        $normalizedToCompare = preg_replace('/\D/', '', $finalRut); // ej: 209913844 o con K al final (K queda sin cambio)

        // Buscar existente (incluye soft-deleted)
        $existing = \App\Models\Alumno::withTrashed()
            ->whereRaw("REPLACE(REPLACE(rut_alumno, '.', ''), '-', '') = ?", [$normalizedToCompare])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                // Restaurar y actualizar
                $existing->restore();
                $existing->nombre_alumno = $data['nombre_alumno'];
                $existing->apellido_alumno = $data['apellido_alumno'];
                $existing->fecha_registro = $data['fecha_registro'] ?? ($existing->fecha_registro ?? now());
                $existing->atrasos = $data['atrasos'] ?? ($existing->atrasos ?? 0);
                $existing->save();

                return redirect()->route('alumnos.index')
                    ->with('success', 'Alumno restaurado y actualizado correctamente.');
            } else {
                return back()->withInput()->withErrors(['rut_alumno' => 'Ya existe un alumno con ese RUT.']);
            }
        }

        // 6) Crear nuevo alumno (con try/catch por si hay otro error)
        try {
            $alumno = \App\Models\Alumno::create([
                'rut_alumno' => $finalRut,
                'nombre_alumno' => $data['nombre_alumno'],
                'apellido_alumno' => $data['apellido_alumno'],
                'fecha_registro' => $data['fecha_registro'] ?? now(),
                'atrasos' => $data['atrasos'] ?? 0,
            ]);
        } catch (\Illuminate\Database\QueryException $ex) {
            \Log::error('Error guardando alumno: ' . $ex->getMessage(), [
                'trace' => $ex->getTraceAsString(),
                'input' => $data,
                'finalRut' => $finalRut,
            ]);
            return back()->withInput()->withErrors(['rut_alumno' => 'Error al guardar alumno en la base de datos. Revisa logs.']);
        }

        return redirect()->route('alumnos.index')->with('success', 'Alumno creado correctamente.');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(string $rut_alumno)
    {
        $alumno = Alumno::findOrFail($rut_alumno);
        return view('alumnos.edit', compact('alumno'));
    }

    /**
     * Actualizar alumno
     *
     * Nota: NO se permite cambiar el RUT aquí (clave primaria). Si necesitas permitirlo,
     * házmelo saber para manejar la migración/rehashing adecuado.
     */
    public function update(Request $request, string $rut_alumno)
    {
        $alumno = Alumno::findOrFail($rut_alumno);

        $data = $request->validate([
            'nombre_alumno' => ['required','string','max:60'],
            'apellido_alumno' => ['required','string','max:60'],
            'fecha_registro' => ['nullable','date'],
            'atrasos' => ['required','integer','min:0'],
            'permiso_prestamo' => ['sometimes','boolean'],
        ]);

        // Asegurar que el permiso venga como booleano
        $data['permiso_prestamo'] = $request->has('permiso_prestamo') ? (bool)$request->input('permiso_prestamo') : false;

        // No permitimos cambiar el rut en update (si quieres habilitarlo, lo tratamos aparte)
        $alumno->update($data);

        return redirect()->route('alumnos.index')->with('success', 'Alumno actualizado correctamente.');
    }

    /**
     * Soft delete (no borrar físicamente).
     */
    public function destroy(string $rut_alumno)
    {
        $alumno = Alumno::findOrFail($rut_alumno);

        // Opcional: validar que no tenga préstamos activos antes de inactivar
        // if ($alumno->prestamos()->whereNull('fecha_devolucion_real')->exists()) {
        //     return redirect()->back()->with('error', 'El alumno tiene préstamos activos.');
        // }

        $alumno->delete(); // soft delete
        return redirect()->route('alumnos.index')->with('success', 'Alumno eliminado (soft delete).');
    }
}
