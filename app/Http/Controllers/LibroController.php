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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Alumno;

class LibroController extends Controller
{
    public function index(Request $request)
    {
        $query = Libro::with(['autores', 'genero'])
            ->withCount([
                'copias',
                'copias as copias_disponibles_count' => function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNull('estado')->orWhere('estado', '<>', 'prestado');
                    });
                },
            ]);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('titulo', 'like', "%$q%")
                    ->orWhere('isbn_libro', 'like', "%$q%");
            });
        }

        // FILTRAR POR AUTOR (many-to-many)
        if ($request->filled('autor')) {
            $autorId = $request->autor;
            $query->whereHas('autores', function ($qb) use ($autorId) {
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
            'isbn_libro'       => 'required|string|unique:libros,isbn_libro',
            'titulo'           => 'required|string|max:255',
            'fecha_publicacion'=> 'nullable|date',
            'editorial'        => 'nullable|string|max:255',
            'genero_nombre'    => 'nullable|string|max:255',
            'autor_nombres'    => 'nullable|array',
            'autor_nombres.*'  => 'nullable|string|max:255',
            'autor_nombre'     => 'nullable|string|max:255',
            'num_copias'       => 'nullable|integer|min:0',
            'id_ubicacion'     => 'nullable|exists:ubicaciones,id_ubicacion',
        ]);

        // Resolver/crear genero (nombre o select)
        $generoId = null;

        // 1) Si envían un genero_id, preferirlo (si existe realmente)
        if ($request->filled('genero_id')) {
            $g = Genero::find($request->genero_id);
            if ($g) {
                $generoId = $g->id_genero;
            }
        }

        // 2) Si no hay genero_id válido, intentar resolver por nombre (buscar case-insensitive)
        if (! $generoId && $request->filled('genero_nombre')) {
            $nombreGenero = trim($request->genero_nombre);
            if ($nombreGenero !== '') {
                // Buscar ignorando mayúsculas/minúsculas
                $genero = Genero::whereRaw('LOWER(nombre) = ?', [Str::lower($nombreGenero)])->first();

                if (! $genero) {
                    // crear nuevo género (si no existe)
                    $genero = Genero::create([
                        'nombre' => $nombreGenero,
                    ]);
                }

                if ($genero) {
                    $generoId = $genero->id_genero;
                }
            }
        }

        // 3) Si aún no hay generoId -> retornar con error claro al usuario
        if (! $generoId) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['genero' => 'No se pudo determinar el género. Selecciona o crea un género válido.']);
        }

        // Si tu tabla libros requiere id_genero NOT NULL, validar aquí
        if (is_null($generoId)) {
            return back()->withInput()->withErrors(['genero' => 'No se pudo determinar el género. Selecciona o crea un género válido.']);
        }

        $numCopias = (int) $request->input('num_copias', 0);
        $idUbicacion = $request->input('id_ubicacion', null);

        DB::beginTransaction();
        try {
            $libro = new Libro();
            $libro->isbn_libro = $request->isbn_libro;
            $libro->titulo = $request->titulo;
            $libro->fecha_publicacion = $request->fecha_publicacion ?: null;
            $libro->editorial = $request->input('editorial');
            $libro->id_genero = $generoId;

            // Asignar id_ubicacion solo si la columna existe
            if (Schema::hasColumn('libros', 'id_ubicacion') && $idUbicacion !== null) {
                $libro->id_ubicacion = $idUbicacion;
            }

            $libro->save();

            // AUTORES: aceptar array o comma separated y FILTRAR nulos/vacíos
            $autorNames = [];
            if ($request->filled('autor_nombres') && is_array($request->autor_nombres)) {
                foreach ($request->autor_nombres as $name) {
                    $name = trim((string) $name);
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
                $autorIds[] = $autor->{$autor->getKeyName()};
            }

            // Si viene un autor_id legacy, lo añadimos también (sin duplicados)
            if ($request->filled('autor_id')) {
                $legacyId = (int) $request->autor_id;
                if ($legacyId && !in_array($legacyId, $autorIds, true)) {
                    $autorIds[] = $legacyId;
                }
            }

            if (!empty($autorIds)) {
                $libro->autores()->sync($autorIds);
            }

            // Crear copias solicitadas (si no se indicó ubicacion, las copias tendrán id_ubicacion NULL)
            // IMPORTANTE: si num_copias es muy grande, podrías limitarlo por seguridad
            $maxCopias = 500; // ajustar según necesidad
            $numCopias = min($numCopias, $maxCopias);

            for ($i = 0; $i < $numCopias; $i++) {
                if (Schema::hasColumn('copia', 'id_ubicacion')) {
                    Copia::create([
                        'id_libro_interno' => $libro->id_libro_interno,
                        'estado' => 'Disponible',
                        'id_ubicacion' => $idUbicacion !== null ? $idUbicacion : null,
                    ]);
                } else {
                    $campo = Schema::hasColumn('copia', 'id_ubicaciones') ? 'id_ubicaciones' : null;
                    if ($campo) {
                        Copia::create([
                            'id_libro_interno' => $libro->id_libro_interno,
                            'estado' => 'Disponible',
                            $campo => $idUbicacion !== null ? $idUbicacion : null,
                        ]);
                    } else {
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
                $libro->stock_disponible = $libro->copias()->where('estado', 'Disponible')->count();
                $libro->save();
            }

            DB::commit();
            return redirect()->route('libros.index')->with('success', 'Libro agregado correctamente');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error creando libro: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return back()->withInput()->withErrors(['internal' => 'Ocurrió un error al crear el libro. Revisa logs.']);
        }
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
        // eager load
        $libro->load(['autores', 'genero']);

        // pasamos ubicaciones para el modal de edición de copias
        $ubicaciones = Ubicacion::all();

        // Para compatibilidad con vistas antiguas, pasamos un paginator vacío:
        // (no es pesado: sólo 10 registros; además tu JS usa la API paginada)
        $copias = Copia::where('id_libro_interno', $libro->id_libro_interno)
                        ->with('ubicacion')
                        ->orderBy('id_copia', 'asc')
                        ->paginate(10);

        // si quieres no cargar copias aquí y depender sólo de AJAX, puedes
        // enviar $copias = null; pero el paginator evita el Undefined variable.
        return view('libros.detalle', compact('libro', 'ubicaciones', 'copias'));
    }

    public function show($id)
    {
        return Libro::findOrFail($id);
    }

    public function edit($id)
    {
        $libro = Libro::findOrFail($id);
        $autores = Autor::all();
        $generos_literarios = Genero::all();
        $ubicaciones = Ubicacion::all();

        return view('libros.edit', compact('libro', 'autores', 'generos_literarios', 'ubicaciones'));
    }

    public function update(Request $request, $id)
    {
        $libro = Libro::findOrFail($id);

        $validated = $request->validate([
            'isbn_libro' => ['required', 'string', 'max:50', Rule::unique('libros', 'isbn_libro')->ignore($libro->id_libro_interno, 'id_libro_interno')],
            'titulo' => 'required|string|max:150',
            'autor_nombre' => 'nullable|string|max:150',
            'autor_id' => 'nullable|exists:autores,id_autor',
            'genero_id' => 'nullable|exists:generos,id_genero',
            'genero_nombre' => 'nullable|string|max:100',
            'fecha_publicacion' => 'nullable|date',
            'stock_total' => 'sometimes|integer|min:0',
            'stock_disponible' => 'sometimes|integer|min:0',
            // validaciones para nuevos campos de copias (si vienen)
            'new_copies_count' => 'nullable|integer|min:0|max:500',
            'new_copies_codes' => 'nullable|string|max:5000',
            'id_ubicacion' => 'nullable|exists:ubicaciones,id_ubicacion',
            'id_ubicaciones' => 'nullable|exists:ubicaciones,id_ubicacion',
        ]);

        // Autor
        if (!empty($request->autor_nombre)) {
            $autor = Autor::firstOrCreate(['nombre' => $request->autor_nombre]);
            $autorId = $autor->id_autor;
        } else {
            $autorId = $request->autor_id;
        }

        // Genero
        if (!empty($request->genero_nombre)) {
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

        // Autores múltiples
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
            $libro->autores()->syncWithoutDetaching([$autorId]);
        }

        //
        // --- NUEVAS COPIAS: procesar aquí si vienen datos en el formulario ---
        //
        $newCopiesCount = (int) $request->input('new_copies_count', 0);
        $newCopiesCodesRaw = (string) $request->input('new_copies_codes', '');
        $hasCodes = trim($newCopiesCodesRaw) !== '';

        // Determinar campo de ubicacion enviado (compatibilidad con vistas antiguas)
        $idUbicacionFromRequest = $request->input('id_ubicacion', $request->input('id_ubicaciones', null));

        // Si el usuario pide crear copias (cantidad > 0 o códigos) la ubicación es obligatoria
        if (($newCopiesCount > 0 || $hasCodes) && empty($idUbicacionFromRequest)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['id_ubicacion' => 'La ubicación es obligatoria cuando se crean copias nuevas. Por favor selecciona una ubicación para las copias.']);
        }

        // **VALIDACION ADICIONAL**: si se intentan crear copias (count>0 o codes no vacío), id_ubic es obligatorio
        if (($count > 0 || !empty($codes)) && empty($idUbic)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['id_ubicacion' => 'La ubicación es requerida al crear copias.']);
        }

        if ($count > 0 || !empty($codes)) {
            DB::beginTransaction();
            try {
                // crear primero copias con códigos especificados
                if (!empty($codes)) {
                    foreach ($codes as $code) {
                        $data = [
                            'id_libro_interno' => $libro->id_libro_interno,
                            'estado' => 'Disponible',
                        ];
                        // si existe la columna id_ubicacion en tabla copia, incluirla
                        if (Schema::hasColumn('copia', 'id_ubicacion')) {
                            $data['id_ubicacion'] = $idUbic !== null ? $idUbic : null;
                        } elseif (Schema::hasColumn('copia', 'id_ubicaciones')) {
                            $data['id_ubicaciones'] = $idUbic !== null ? $idUbic : null;
                        }
                        // si existe columna 'codigo' la añadimos (evita error si no existe)
                        if (Schema::hasColumn('copia', 'codigo')) {
                            $data['codigo'] = $code;
                        }
                        Copia::create($data);
                        $created++;
                    }
                }

                // crear copias por cantidad solicitada
                for ($i = 0; $i < $count; $i++) {
                    $data = [
                        'id_libro_interno' => $libro->id_libro_interno,
                        'estado' => 'Disponible',
                    ];
                    if (Schema::hasColumn('copia', 'id_ubicacion')) {
                        $data['id_ubicacion'] = $idUbic !== null ? $idUbic : null;
                    } elseif (Schema::hasColumn('copia', 'id_ubicaciones')) {
                        $data['id_ubicaciones'] = $idUbic !== null ? $idUbic : null;
                    }
                    // si existe columna 'codigo' generamos un código único para la copia
                    if (Schema::hasColumn('copia', 'codigo')) {
                        $data['codigo'] = 'C-' . strtoupper(uniqid());
                    }
                    Copia::create($data);
                    $created++;
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                \Log::error('Error creando copias al actualizar libro: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'libro_id' => $libro->id_libro_interno,
                    'request' => $request->all(),
                ]);
                return redirect()->back()->withInput()->withErrors(['new_copies' => 'Error al crear copias: ' . $e->getMessage()]);
            }
        }

        // Recalcular stock final
        if (method_exists($libro, 'recalcularStock')) {
            $libro->recalcularStock();
        } else {
            $libro->stock_total = $libro->copias()->count();
            $libro->stock_disponible = $libro->copias()->where('estado', 'Disponible')->count();
            $libro->save();
        }

        // flash informativo si se crearon copias
        if ($created > 0) {
            session()->flash('success', "Se crearon {$created} copias nuevas para este libro.");
        }

        return redirect()->route('libros.index')->with('success', 'Libro actualizado correctamente');
    }

    public function destroy($id)
    {
        $libro = Libro::findOrFail($id);
        $libro->delete(); // soft delete
        return redirect()->route('libros.index')->with('success', 'Libro eliminado correctamente');
    }

    public function catalogo(Request $request)
    {
        $query = Libro::with(['autores', 'genero'])
            ->withCount([
                'copias',
                'copias as copias_disponibles_count' => function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNull('estado')->orWhere('estado', '<>', 'prestado');
                    });
                },
            ]);

        // Búsqueda múltiple: título, ISBN, autor nombre, genero nombre
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('titulo', 'like', "%{$q}%")
                    ->orWhere('isbn_libro', 'like', "%{$q}%")
                    ->orWhereHas('autores', function ($qa) use ($q) {
                        $qa->where('nombre', 'like', "%{$q}%");
                    })
                    ->orWhereHas('genero', function ($gg) use ($q) {
                        $gg->where('nombre', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('autor')) {
            $autorId = $request->autor;
            $query->whereHas('autores', function ($qb) use ($autorId) {
                $qb->where('autores.id_autor', $autorId);
            });
        }

        if ($request->filled('genero')) {
            $query->where('id_genero', $request->genero);
        }

        $libros = $query->paginate(12)->withQueryString();

        // Datos auxiliares para selects / modal
        $autores = Autor::all();
        $generos = Genero::all();
        $alumnos = Alumno::all();

        return view('home', compact('libros', 'autores', 'generos', 'alumnos'));
    }

    /**
     * API: copias disponibles de un libro (JSON)
     */
    public function copiasDisponibles($id, Request $request)
    {
        try {
            $libro = Libro::findOrFail($id);

            $perPage = (int) $request->query('per_page', 10);
            $page = (int) $request->query('page', 1);

            $query = Copia::where('id_libro_interno', $libro->id_libro_interno)
                ->with('ubicacion')
                ->where(function ($q) {
                    $q->whereNull('estado')
                    ->orWhereNotIn('estado', ['prestado', 'Prestada', 'Prestado']);
                })
                ->orderBy('id_copia', 'asc');

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            // transformar items para JSON exactamente en el formato que usa tu JS
            $items = $paginator->getCollection()->map(function ($c) {
                return [
                    'id_copia'    => $c->id_copia ?? null,
                    'id_ubicacion'=> $c->id_ubicacion ?? null,
                    'estado'      => $c->estado ?? null,
                    'ubicacion'   => $c->ubicacion ? [
                        'estante' => $c->ubicacion->estante ?? null,
                        'seccion' => $c->ubicacion->seccion ?? null,
                    ] : null,
                    // URL para editar (por si el front no la recibe)
                    'update_url'  => route('copias.update', $c->id_copia ?? $c->id),
                ];
            });

            $payload = [
                'data' => $items,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ];

            return response()->json($payload, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Libro no encontrado'], 404);
        } catch (\Throwable $e) {
            \Log::error('Error en copiasDisponibles (paginated): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'libro_id' => $id,
                'request' => $request->all(),
            ]);
            return response()->json(['message' => 'Error interno al obtener copias'], 500);
        }
    }

}
