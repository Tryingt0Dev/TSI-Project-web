<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Autor;
use App\Models\Ubicacion;
use App\Models\Libro;
use App\Models\Genero;
use App\Models\Copia;
use Illuminate\Support\Facades\DB;

class LibroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Libro::with(['autor', 'genero'])
            ->withCount([
                'copias', 
                'copias as copias_disponibles_count' => function ($q) {
                    $q->where(function($sub) {
                        $sub->whereNull('estado')
                            ->orWhere('estado', '<>', 'prestado');
                    });
                },
            ]);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($sub) use ($q) {
                $sub->where('titulo', 'like', "%$q%")
                    ->orWhere('isbn_libro', 'like', "%$q%");
            });
        }

        if ($request->filled('autor')) {
            $query->where('autor_id', $request->autor);
        }

        if ($request->filled('genero')) {
            $query->where('genero_id', $request->genero);
        }

        $libros = $query->paginate(12);

        return view('libros.index', [
            'libros'   => $libros,
            'autores'  => \App\Models\Autor::all(),
            'generos'  => \App\Models\Genero::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $autores = Autor::all();
        $ubicaciones = Ubicacion::all();
        $generos_literarios = Genero::all();

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
            'num_copias' => 'nullable|integer|min:0',
            'id_ubicaciones' => 'nullable|exists:ubicaciones,id', // asegúrate que la tabla y PK se llamen así
        ]);

        // Resolver/crear genero y autor (igual que antes)
        $generoId = null;
        if (! empty($request->genero_nombre)) {
            $genero = Genero::firstOrCreate(['nombre' => $request->genero_nombre]);
            $generoId = $genero->id;
        }

        $autorId = null;
        if (! empty($request->autor_nombre)) {
            $autor = Autor::firstOrCreate(['nombre' => $request->autor_nombre]);
            $autorId = $autor->id;
        }

        $numCopias = (int) ($request->input('num_copias', 0));
        $idUbicaciones = $request->input('id_ubicaciones', null);

        
        DB::transaction(function () use ($request, $generoId, $autorId, $numCopias, $idUbicaciones, &$libro) {
            $libro = new Libro();
            $libro->isbn_libro = $request->isbn_libro;
            $libro->titulo = $request->titulo;
            $libro->fecha_publicacion = $request->fecha_publicacion;
            $libro->editorial = $request->input('editorial');
            $libro->genero_id = $generoId;
            $libro->autor_id = $autorId;

            // Guardamos el libro
            $libro->save();

            // Crear las copias solicitadas
            for ($i = 0; $i < $numCopias; $i++) {
                Copia::create([
                    'id_libro_interno' => $libro->id,
                    'estado' => 'disponible',
                    'id_ubicaciones' => $idUbicaciones,
                ]);
            }
            // Recalcular stock
            $libro->recalcularStock();
        });

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
    public function detalle(Libro $libro)
    {
        $libro->load(['autor', 'genero', 'copias']); // no cargamos ubicacion por cada copia aquí, lo pedimos después si quieres
        $ubicaciones = Ubicacion::all(); // lista para el select
        return view('libros.detalle', compact('libro', 'ubicaciones'));
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
        $generos_literarios = Genero::all();
        $ubicaciones = Ubicacion::all();

        return view('libros.edit', compact('libro', 'autores', 'generos_literarios', 'ubicaciones'));
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
            'stock_total' => 'sometimes|integer|min:0',
            'stock_disponible' => 'sometimes|integer|min:0',
        ]);

        // Resolver autor/genero 
        if (! empty($request->autor_nombre)) {
            $autor = Autor::firstOrCreate(['nombre' => $request->autor_nombre]);
            $autor_id = $autor->id;
        } else {
            $autor_id = $request->autor_id;
        }

        if (! empty($request->genero_nombre)) {
            $genero = Genero::firstOrCreate(['nombre' => $request->genero_nombre]);
            $genero_id = $genero->id;
        } else {
            $genero_id = $request->genero_id;
        }

        // Actualizar campos básicos
        $libro->update([
            'isbn_libro' => $validated['isbn_libro'],
            'titulo' => $validated['titulo'],
            'autor_id' => $autor_id,
            'genero_id' => $genero_id,
            'fecha_publicacion' => $validated['fecha_publicacion'] ?? null,
        ]);

        // Recalcular stock en caso de que se hayan cambiado cosas que afecten copias
        $libro->recalcularStock();

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

    public function catalogo(Request $request)
    {
        $query = Libro::with(['autor', 'genero']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($sub) use ($q) {
                $sub->where('titulo', 'like', "%$q%")
                    ->orWhere('isbn_libro', 'like', "%$q%");
            });
        }

        if ($request->filled('autor')) {
            $query->where('autor_id', $request->autor);
        }

        if ($request->filled('genero')) {
            $query->where('genero_id', $request->genero);
        }

        $libros = $query->paginate(12);

        return view('home', [
            'libros' => $libros,
            'autores' => Autor::all(),
            'generos' => Genero::all(),
        ]);
    }
}
