<?php

namespace Database\Factories;

use App\Models\Libro;
use App\Models\Ubicacion;
use App\Models\Genero;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LibroFactory extends Factory
{
    protected $model = Libro::class;

    public function definition()
    {
        // fallback values (faker)
        $fallback = [
            'titulo' => $this->faker->sentence(3),
            'isbn_libro' => $this->faker->unique()->isbn13(),
            'editorial' => $this->faker->company(),
            'fecha_publicacion' => $this->faker->date(),
            'imagen' => null,
        ];

        // small random query to fetch plausible books
        $q = $this->faker->words(2, true);

        try {
            $res = Http::timeout(6)->get('https://openlibrary.org/search.json', [
                'q' => $q,
                'limit' => 8,
            ]);

            if ($res->ok() && !empty($res->json('docs'))) {
                $docs = $res->json('docs');
                // choose a doc that preferably contains ISBN
                $chosen = null;
                foreach ($docs as $d) {
                    if (!empty($d['isbn'])) { $chosen = $d; break; }
                }
                if (!$chosen) $chosen = $docs[0];

                $titulo = $chosen['title'] ?? $fallback['titulo'];

                // pick isbn prefer 13
                $isbn = '';
                if (!empty($chosen['isbn']) && is_array($chosen['isbn'])) {
                    foreach ($chosen['isbn'] as $i) {
                        $clean = preg_replace('/[^0-9Xx]/', '', (string)$i);
                        if (strlen($clean) === 13) { $isbn = $clean; break; }
                        if (!$isbn) $isbn = $clean;
                    }
                }

                // get editions for publisher/date if we have a work key
                $editorial = $fallback['editorial'];
                $fecha = $fallback['fecha_publicacion'];
                $imagen = null;

                $workKey = $chosen['key'] ?? null;
                if ($workKey) {
                    $ed = Http::timeout(6)->get("https://openlibrary.org{$workKey}/editions.json", ['limit' => 5]);
                    if ($ed->ok()) {
                        $entries = $ed->json('entries') ?? [];
                        $edition = null;
                        foreach ($entries as $en) {
                            if ((!empty($en['isbn_13']) || !empty($en['isbn_10'])) && !empty($en['publishers'])) {
                                $edition = $en;
                                break;
                            }
                        }
                        if (!$edition && !empty($entries)) $edition = $entries[0];

                        if (!empty($edition)) {
                            if (!empty($edition['publishers']) && is_array($edition['publishers'])) {
                                $editorial = $edition['publishers'][0] ?? $editorial;
                            }
                            if (!empty($edition['publish_date'])) {
                                if (preg_match('/\d{4}/', $edition['publish_date'], $m)) {
                                    $fecha = $m[0] . '-01-01';
                                } else {
                                    $fecha = $fallback['fecha_publicacion'];
                                }
                            } elseif (!empty($chosen['first_publish_year'])) {
                                $fecha = $chosen['first_publish_year'] . '-01-01';
                            }
                            if (!empty($edition['isbn_13']) && is_array($edition['isbn_13'])) {
                                $isbn = $edition['isbn_13'][0];
                            } elseif (!empty($edition['isbn_10']) && is_array($edition['isbn_10'])) {
                                $isbn = $edition['isbn_10'][0];
                            }
                        }
                    }
                }

                if ($isbn) {
                    $imagen = "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg";
                } elseif (!empty($chosen['cover_i'])) {
                    $imagen = "https://covers.openlibrary.org/b/id/{$chosen['cover_i']}-L.jpg";
                }

                $result = [
                    'titulo' => $titulo,
                    'isbn_libro' => $isbn ?: $fallback['isbn_libro'],
                    'editorial' => $editorial ?: $fallback['editorial'],
                    'fecha_publicacion' => $fecha ?: $fallback['fecha_publicacion'],
                    'imagen' => $imagen,
                ];
            } else {
                $result = $fallback;
            }
        } catch (\Throwable $e) {
            $result = $fallback;
        }

        // assign FK: pick existing Ubicacion and Genero if present
        $ubicacion = Ubicacion::inRandomOrder()->first();
        $genero = Genero::inRandomOrder()->first();

        // if missing, leave null (your seeder should ensure these exist first)
        $id_ubicacion = $ubicacion ? $ubicacion->id_ubicacion : null;
        $id_genero = $genero ? $genero->id_genero : null;

        return [
            'titulo' => Str::limit($result['titulo'], 150),
            'isbn_libro' => substr((string)($result['isbn_libro'] ?? $this->faker->unique()->isbn13()), 0, 17),
            'editorial' => $result['editorial'] ?? $this->faker->company(),
            'fecha_publicacion' => $result['fecha_publicacion'] ?? null,
            'stock_total' => $this->faker->numberBetween(1, 20),
            'stock_disponible' => $this->faker->numberBetween(0, 10),
            'imagen' => $result['imagen'] ?? null,
            'id_ubicacion' => $id_ubicacion,
            'id_genero' => $id_genero,
        ];
    }
}
