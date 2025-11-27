<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\Copia;
use App\Models\Alumno;
use Illuminate\Http\Request;

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

}