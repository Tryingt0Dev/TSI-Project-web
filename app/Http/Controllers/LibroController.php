<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Autor;
use App\Models\Ubicacion;
use App\Models\Libro;
use App\Models\Genero;
use App\Models\Copia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema; // <-- IMPORTA Schema

class LibroController extends Controller
{
    public function index(Request $request)
    {
        $query = Libro::with(['autores', 'genero'])
            ->withCount([
                'copias',
                'copias as copias_disponibles_count' => function ($q) {
                    $q->where(function($sub) {
                        $sub->whereNull('estado')->orWhere('estado', '<>', 'prestado');
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

        // FILTRAR POR AUTOR (many-to-many)
        if ($request->filled('autor')) {
            $autorId = $request->autor;
            $query->whereHas('autores', function($qb) use ($autorId) {
                $qb->where('autores.id_autor', $autorId);
            });
        }

        if ($request->filled('genero')) {
            $query->where('id_genero', $request->genero);
        }

        $libros = $query->paginate(12);

        return view('libros.index', [
            'libros'   => $libros,
            'autores'  => Autor::all(),
            'generos'  => Genero::all(),
        ]);
    }

    public function create()
    {
        $autores = Autor::all();
        $ubicaciones = Ubicacion::all();
        $generos_literarios = Genero::all();

        return view('libros.create', compact('autores', 'ubicaciones', 'generos_literarios'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'isbn_libro'      => 'required|string|unique:libros,isbn_libro',
            'titulo'          => 'required|string|max:255',
            'fecha_publicacion'=> 'nullable|date',
            'editorial'       => 'nullable|string|max:255',
            'genero_nombre'   => 'nullable|string|max:255',
            'autor_nombres'   => 'nullable|array',
            'autor_nombres.*' => 'nullable|string|max:255',
            'autor_nombre'    => 'nullable|string|max:255',
            'num_copias'      => 'nullable|integer|min:0',
            'id_ubicacion'    => 'nullable|exists:ubicaciones,id_ubicacion',
        ]);

        // Resolver/crear genero (nombre o select)
        $generoId = null;
        if (!empty($request->genero_nombre)) {
            $genero = Genero::firstOrCreate(['nombre' => $request->genero_nombre]);
            $generoId = $genero->id_genero;
        } elseif ($request->filled('genero_id')) {
            $generoId = $request->genero_id;
        }

        $numCopias = (int) $request->input('num_copias', 0);
        $idUbicacion = $request->input('id_ubicacion', null);

        DB::transaction(function () use ($request, $generoId, $numCopias, $idUbicacion, &$libro) {
            $libro = new Libro();
            $libro->isbn_libro = $request->isbn_libro;
            $libro->titulo = $request->titulo;
            $libro->fecha_publicacion = $request->fecha_publicacion;
            $libro->editorial = $request->input('editorial');
            $libro->id_genero = $generoId;

            // --- Asignar SOLO si las columnas existen en la BD ---
            if (Schema::hasColumn('libros', 'id_autor')) {
                // ponemos explícitamente null si quieres mantener la columna legacy
                $libro->id_autor = null;
            }

            if (Schema::hasColumn('libros', 'id_ubicacion')) {
                $libro->id_ubicacion = $idUbicacion !== null ? $idUbicacion : null;
            }

            // Guardamos el libro
            $libro->save();

            // AUTORES: aceptar array o comma separated
            $autorNames = [];
            if ($request->filled('autor_nombres') && is_array($request->autor_nombres)) {
                foreach ($request->autor_nombres as $name) {
                    $name = trim($name);
                    if ($name !== '') $autorNames[] = $name;
                }
            }
            if (empty($autorNames) && $request->filled('autor_nombre')) {
                $parts = array_filter(array_map('trim', explode(',', $request->autor_nombre)));
                foreach ($parts as $p) if ($p !== '') $autorNames[] = $p;
            }

            $autorIds = [];
            foreach ($autorNames as $an) {
                $autor = Autor::firstOrCreate(['nombre' => $an]);
                $autorIds[] = $autor->id_autor;
            }

            // Si viene un autor_id legacy suelto, lo añadimos también (sin duplicados)
            if ($request->filled('autor_id')) {
                $legacyId = $request->autor_id;
                if ($legacyId && !in_array($legacyId, $autorIds, true)) {
                    $autorIds[] = (int) $legacyId;
                }
            }

            if (!empty($autorIds)) {
                $libro->autores()->sync($autorIds);
            }

            // Crear copias solicitadas (si no se indicó ubicacion, las copias tendrán id_ubicacion NULL)
            for ($i = 0; $i < $numCopias; $i++) {
                // determinar campo correcto en la tabla copia:
                if (Schema::hasColumn('copia', 'id_ubicacion')) {
                    Copia::create([
                        'id_libro_interno' => $libro->id_libro_interno,
                        'estado' => 'Disponible',
                        'id_ubicacion' => $idUbicacion !== null ? $idUbicacion : null,
                    ]);
                } else {
                    // si la tabla copia usa otro nombre (ej: id_ubicaciones) acomodamos
                    $campo = Schema::hasColumn('copia', 'id_ubicaciones') ? 'id_ubicaciones' : null;
                    if ($campo) {
                        Copia::create([
                            'id_libro_interno' => $libro->id_libro_interno,
                            'estado' => 'Disponible',
                            $campo => $idUbicacion !== null ? $idUbicacion : null,
                        ]);
                    } else {
                        // crear sin ubicación si no existe ninguna columna relacionada
                        Copia::create([
                            'id_libro_interno' => $libro->id_libro_interno,
                            'estado' => 'Disponible',
                        ]);
                    }
                }
            }

            // Recalcular stock
            if (method_exists($libro, 'recalcularStock')) {
                $libro->recalcularStock();
            } else {
                $libro->stock_total = $libro->copias()->count();
                $libro->stock_disponible = $libro->copias()->where('estado','Disponible')->count();
                $libro->save();
            }
        });

        return redirect()->route('libros.index')->with('success', 'Libro agregado correctamente');
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
        $libro->load(['autores', 'genero', 'copias']);
        $ubicaciones = Ubicacion::all();
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
    public function update(Request $request, $id)
    {
        $libro = Libro::findOrFail($id);

        $validated = $request->validate([
            'isbn_libro' => ['required','string','max:50', Rule::unique('libros','isbn_libro')->ignore($libro->id_libro_interno,'id_libro_interno')],
            'titulo' => 'required|string|max:150',
            'autor_nombre' => 'nullable|string|max:150',
            'autor_id' => 'nullable|exists:autores,id_autor',
            'genero_id' => 'nullable|exists:generos,id_genero',
            'genero_nombre' => 'nullable|string|max:100',
            'fecha_publicacion' => 'nullable|date',
            'stock_total' => 'sometimes|integer|min:0',
            'stock_disponible' => 'sometimes|integer|min:0',
        ]);

        // Autor
        if (! empty($request->autor_nombre)) {
            $autor = Autor::firstOrCreate(['nombre' => $request->autor_nombre]);
            $autorId = $autor->id_autor;
        } else {
            $autorId = $request->autor_id;
        }

        // Genero
        if (! empty($request->genero_nombre)) {
            $genero = Genero::firstOrCreate(['nombre' => $request->genero_nombre]);
            $generoId = $genero->id_genero;
        } else {
            $generoId = $request->genero_id;
        }

        $libro->update([
            'isbn_libro' => $validated['isbn_libro'],
            'titulo' => $validated['titulo'],
            'id_genero' => $generoId,
            'fecha_publicacion' => $validated['fecha_publicacion'] ?? null,
        ]);

        // Si vienen autores múltiples (campo autor_nombres[] o autor_nombre comma)
        $autorNames = [];
        if ($request->filled('autor_nombres') && is_array($request->autor_nombres)) {
            foreach ($request->autor_nombres as $name) {
                $name = trim($name);
                if ($name !== '') $autorNames[] = $name;
            }
        }
        if (empty($autorNames) && $request->filled('autor_nombre')) {
            $autorNames = array_filter(array_map('trim', explode(',', $request->autor_nombre)));
        }
        if (!empty($autorNames)) {
            $autorIds = [];
            foreach ($autorNames as $an) {
                $a = Autor::firstOrCreate(['nombre' => $an]);
                $autorIds[] = $a->id_autor;
            }
            $libro->autores()->sync($autorIds);
        } elseif ($autorId) {
            // si solo viene un autor_id legacy, mantenerlo en pivot
            $libro->autores()->syncWithoutDetaching([$autorId]);
        }

        $libro->recalcularStock();

        return redirect()->route('libros.index')->with('success', 'Libro actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $libro = Libro::findOrFail($id);
        $libro->delete(); // soft delete
        return redirect()->route('libros.index')->with('success','Libro eliminado correctamente');
    }

    public function catalogo(Request $request)
    {
        $query = Libro::with(['autores', 'genero']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($sub) use ($q) {
                $sub->where('titulo', 'like', "%$q%")
                    ->orWhere('isbn_libro', 'like', "%$q%");
            });
        }

        if ($request->filled('autor')) {
            $autorId = $request->autor;
            $query->whereHas('autores', function($qb) use ($autorId) {
                $qb->where('autores.id_autor', $autorId);
            });
        }

        if ($request->filled('genero')) {
            $query->where('id_genero', $request->genero);
        }

        $libros = $query->paginate(12);

        return view('home', [
            'libros' => $libros,
            'autores' => Autor::all(),
            'generos' => Genero::all(),
        ]);
    }
}
