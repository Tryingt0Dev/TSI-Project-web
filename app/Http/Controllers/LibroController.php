<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Autor;
use App\Models\Ubicacion;
use App\Models\Libro;
use App\Models\GeneroLiterario;

class LibroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(){
    $libros = Libro::all();
    return view('libros.index', compact('libros'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $autores = Autor::all();
        $ubicaciones = Ubicacion::all();
        $generos_literarios = GeneroLiterario::all();

        return view('libros.create', compact('autores', 'ubicaciones', 'generos_literarios'));
    }
 
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'isbn_libro' => 'required|string|unique:libros,isbn_libro',
            'titulo' => 'required|string|max:255',
            'fecha_publicacion' => 'nullable|date',
            'editorial' => 'nullable|string|max:255',
            'genero_nombre' => 'nullable|string|max:255',
            'autor_nombre' => 'nullable|string|max:255',
            'stock_total' => 'required|integer|min:0',
            'stock_disponible' => 'required|integer|min:0',
        ]);

        
        $generoId = null;
        if (!empty($request->genero_nombre)) {
            $genero = GeneroLiterario::firstOrCreate(
                ['nombre' => $request->genero_nombre]
            );
            $generoId = $genero->id;
        }

       
        $autorId = null;
        if (!empty($request->autor_nombre)) {
            $autor = Autor::firstOrCreate(
                ['nombre' => $request->autor_nombre]
            );
            $autorId = $autor->id;
        }

        
        $libro = new Libro();
        $libro->isbn_libro = $request->isbn_libro;
        $libro->titulo = $request->titulo;
        $libro->fecha_publicacion = $request->fecha_publicacion;
        $libro->editorial = $request->input('editorial');
        $libro->stock_disponible = $request->stock_disponible;
        $libro->stock_total = $request->stock_total;
        $libro->genero_id = $generoId;
        $libro->autor_id = $autorId;

        $libro->save();

        return redirect()
            ->route('libros.index')
            ->with('success', 'Libro agregado correctamente');
    }
    
    /**
     * Busqueda en la api del isbn
     */
    public function buscarLibro(Request $request)
    {
        $query = $request->get('q');

        if (!$query) {
            return response()->json([]);
        }

        $url = "https://openlibrary.org/search.json?q=" . urlencode($query);
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        $resultados = [];

        if (!empty($data['docs'])) {
            foreach ($data['docs'] as $doc) {
                $resultados[] = [
                    'titulo' => $doc['title'] ?? '',
                    'autor' => isset($doc['author_name']) ? implode(', ', $doc['author_name']) : '',
                    'isbn' => $doc['isbn'][0] ?? '',
                    'anio' => $doc['first_publish_year'] ?? '',
                ];
            }
        }

        return response()->json($resultados);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return Libro::findOrFail($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $libro = Libro::findOrFail($id);
        $autores = Autor::all(); 
        $generos_literarios = GeneroLiterario::all();
        return view('libros.edit', compact('libro', 'autores', 'generos_literarios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $libro = Libro::findOrFail($id);

        $validated = $request->validate([
            'isbn_libro' => 'required|string|max:50|unique:libros,isbn_libro,' . $libro->id,
            'titulo' => 'required|string|max:150',
            'autor_id' => 'nullable|exists:autores,id',
            'autor_nombre' => 'nullable|string|max:150',
            'genero_id' => 'nullable|exists:generos_literarios,id',
            'genero_nombre' => 'nullable|string|max:100',
            'fecha_publicacion' => 'nullable|date',
            'stock_total' => 'required|integer|min:0',
            'stock_disponible' => 'required|integer|min:0',
        ]);

        
        if (!empty($request->autor_nombre)) {
            $autor = Autor::firstOrCreate(
                ['nombre' => $request->autor_nombre]
            );
            $autor_id = $autor->id;
        } else {
            $autor_id = $request->autor_id;
        }

        
        if (!empty($request->genero_nombre)) {
            $genero = GeneroLiterario::firstOrCreate(
                ['nombre' => $request->genero_nombre]
            );
            $genero_id = $genero->id;
        } else {
            $genero_id = $request->genero_id;
        }

        
        $libro->update([
            'isbn_libro' => $validated['isbn_libro'],
            'titulo' => $validated['titulo'],
            'autor_id' => $autor_id,
            'genero_id' => $genero_id,
            'fecha_publicacion' => $validated['fecha_publicacion'] ?? null,
            'stock_total' => $validated['stock_total'],
            'stock_disponible' => $validated['stock_disponible'],
        ]);

        return redirect()->route('libros.index')->with('success', 'Libro actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Libro::withTrashed()->where('id', $id)->forceDelete();

        
        return redirect()->route('libros.index')->with('success', 'Libro eliminado correctamente');
    }
}
