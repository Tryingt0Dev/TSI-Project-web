<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlumnoController extends Controller
{
    /**
     * Mostrar listado con búsqueda, filtros y paginación.
     */
    public function index(Request $request)
    {
        $q = $request->query('q');
        $permiso = $request->query('permiso'); // '', '1', '0'
        $min_retrasos = $request->query('min_retrasos');

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

        // Filtro mínimo de retrasos
        if ($min_retrasos !== null && $min_retrasos !== '') {
            $query->where('retrasos', '>=', (int)$min_retrasos);
        }

        // Ordena por fecha de registro (últimos primero)
        $alumnos = $query->orderByDesc('fecha_registro')->paginate(15)->withQueryString();

        return view('alumnos.index', compact('alumnos', 'q', 'permiso', 'min_retrasos'));
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
     */
    public function update(Request $request, string $rut_alumno)
    {
        $alumno = Alumno::findOrFail($rut_alumno);

        $data = $request->validate([
            'nombre_alumno' => ['required','string','max:60'],
            'apellido_alumno' => ['required','string','max:60'],
            'fecha_registro' => ['nullable','date'],
            'retrasos' => ['required','integer','min:0'],
            'permiso_prestamo' => ['sometimes','boolean'],
        ]);

        // Asegurar que el permiso venga como booleano
        $data['permiso_prestamo'] = $request->has('permiso_prestamo') ? (bool)$request->input('permiso_prestamo') : false;

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
        // if ($alumno->prestamos()->where('entregado', false)->exists()) {
        //     return redirect()->back()->with('error', 'El alumno tiene préstamos activos.');
        // }

        $alumno->delete(); // soft delete
        return redirect()->route('alumnos.index')->with('success', 'Alumno eliminado (soft delete).');
    }
}
