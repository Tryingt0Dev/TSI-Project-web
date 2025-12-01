<?php

namespace Database\Factories;

use App\Models\Libro;
use App\Models\Ubicacion;
use App\Models\Genero;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LibroFactory extends Factory
{
    protected $model = Libro::class;

    // Cache interno para no pedir a OpenLibrary en cada ejecución del factory
    protected static $buffer = [];
    protected static $bufferIndex = 0;

    public function definition()
    {
        // fallback values
        $fallback = [
            'titulo' => $this->faker->sentence(3),
            'isbn_libro' => $this->faker->unique()->isbn13(),
            'editorial' => $this->faker->company(),
            'fecha_publicacion' => $this->faker->date(),
            'imagen' => null,
        ];

        // -------------------------------------------------------------
        // 1) Intentar reutilizar resultados bufferizados para ahorrar API
        // -------------------------------------------------------------
        if (!empty(self::$buffer) && self::$bufferIndex < count(self::$buffer)) {
            $result = self::$buffer[self::$bufferIndex++];
        } else {
            // recargar buffer
            self::$buffer = $this->fetchBooksFromOpenLibrary();
            self::$bufferIndex = 0;

            if (!empty(self::$buffer)) {
                $result = self::$buffer[self::$bufferIndex++];
            } else {
                // si la API falló, fallback
                $result = $fallback;
            }
        }

        // Normalizar campos / garantizar ISBN no vacío
        $isbnRaw = (string) ($result['isbn_libro'] ?? '');
        $isbnRaw = preg_replace('/[^0-9Xx]/', '', $isbnRaw);
        if ($isbnRaw === '' || strlen($isbnRaw) < 8) {
            // fallback: generar un ISBN seguro
            $isbnRaw = $this->faker->unique()->isbn13();
        }

        // Evitar colisión con BD: si existe, generar alternative (no persistida aún)
        if (\App\Models\Libro::where('isbn_libro', $isbnRaw)->exists()) {
            // el factory no debe crear duplicados: generar uno nuevo con faker
            $isbnRaw = $this->faker->unique()->isbn13();
        }

        // FK asignadas
        $ubicacion = Ubicacion::inRandomOrder()->first();
        $genero = Genero::inRandomOrder()->first();

        $id_ubicacion = $ubicacion ? $ubicacion->id_ubicacion : null;
        $id_genero = $genero ? $genero->id_genero : null;

        return [
            'titulo' => Str::limit($result['titulo'] ?? $fallback['titulo'], 150),
            'isbn_libro' => substr((string)$isbnRaw, 0, 17),
            'editorial' => $result['editorial'] ?? $fallback['editorial'],
            'fecha_publicacion' => $result['fecha_publicacion'] ?? $fallback['fecha_publicacion'],
            'stock_total' => $this->faker->numberBetween(1, 20),
            'stock_disponible' => $this->faker->numberBetween(0, 10),
            'imagen' => $result['imagen'] ?? null,
            'id_ubicacion' => $id_ubicacion,
            'id_genero' => $id_genero,
        ];
    }

    /**
     * Descarga libros de OpenLibrary con sleep y retry.
     * Retorna array de items con keys: titulo,isbn_libro,editorial,fecha_publicacion,imagen
     */
    protected function fetchBooksFromOpenLibrary()
    {
        // idiomas: 'spa' 70% (7/10), el resto repartido
        $lang = $this->faker->randomElement([
            'spa','spa','spa','spa','spa','spa','spa', // 7
            'eng','eng', // 2
            'jpn', // 1
            'rus', // 1
        ]);

        $params = [
            // query: usamos subject:novel y language para priorizar novelas en ese idioma
            'q' => "subject:novel language:$lang",
            'limit' => 12,
        ];

        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                // Espera leve entre requests para evitar rate-limit / bloqueos
                sleep(1);

                // usar withoutVerifying para entornos Windows locales con problemas SSL
                $res = Http::withoutVerifying()->timeout(8)->get('https://openlibrary.org/search.json', $params);

                if (!$res->ok()) {
                    throw new \Exception("Bad status: ".$res->status());
                }

                $docs = $res->json('docs', []);

                if (empty($docs)) {
                    throw new \Exception("No docs received");
                }

                $buffer = [];

                foreach ($docs as $doc) {
                    $title = $doc['title'] ?? null;
                    if (!$title) continue;

                    // seleccionar isbn (preferir 13)
                    $isbn = null;
                    if (!empty($doc['isbn']) && is_array($doc['isbn'])) {
                        foreach ($doc['isbn'] as $i) {
                            $clean = preg_replace('/[^0-9Xx]/', '', (string)$i);
                            if (strlen($clean) === 13) { $isbn = $clean; break; }
                            if (!$isbn) $isbn = $clean;
                        }
                    }

                    $editorial = null;
                    if (!empty($doc['publisher']) && is_array($doc['publisher'])) {
                        $editorial = $doc['publisher'][0];
                    } elseif (!empty($doc['publisher_name']) && is_array($doc['publisher_name'])) {
                        $editorial = $doc['publisher_name'][0];
                    }

                    $fecha = !empty($doc['first_publish_year']) ? ($doc['first_publish_year'].'-01-01') : null;

                    // Cover: preferir b/id (cover_i) o isbn cover
                    $cover = null;
                    if (!empty($doc['cover_i'])) {
                        $cover = "https://covers.openlibrary.org/b/id/{$doc['cover_i']}-L.jpg";
                    } elseif ($isbn) {
                        // usar isbn cover si no hay cover_i
                        $cover = "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg";
                    }

                    // formatear imagen para que siempre sea null o URL 'b/id' o 'b/isbn'
                    $buffer[] = [
                        'titulo' => $title,
                        'isbn_libro' => $isbn ?: Str::random(12),
                        'editorial' => $editorial ?: 'Editorial sin info',
                        'fecha_publicacion' => $fecha ?: null,
                        'imagen' => $cover ?: null,
                    ];
                }

                if (!empty($buffer)) {
                    Log::info("LibroFactory: buffer cargado con ".count($buffer)." libros (lang={$lang})");
                    return $buffer;
                }

            } catch (\Throwable $e) {
                Log::warning("LibroFactory intento ".($attempt+1)." fallo: ".$e->getMessage());
                // backoff
                sleep(2);
            }

            $attempt++;
        }

        return []; // fallback, factory usará fallback automático
    }
}
