<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Libro;
use App\Models\Ubicacion;
use App\Models\Genero;
use Illuminate\Support\Str;

class LibroSeeder extends Seeder
{
    public function run(): void
    {
        // Asegurar que existan Ubicaciones y Generos
        if (Ubicacion::count() < 10) {
            Ubicacion::factory(20)->create();
        }
        if (Genero::count() < 10) {
            Genero::factory(12)->create();
        }

        // ----- LIBROS MANUALES: 14 ES, 2 EN, 2 JP, 2 RU -----
        $manualBooks = [
            // 14 español
            [
                'titulo' => 'Cien años de soledad',
                'isbn_libro' => '9780307474728',
                'editorial' => 'Editorial Sudamericana',
                'fecha_publicacion' => '1967-05-30',
                'imagen' => 'https://covers.openlibrary.org/b/id/15127400-L.jpg',
                'stock_total' => 5, 'stock_disponible' => 5
            ],
            [
                'titulo' => 'La sombra del viento',
                'isbn_libro' => '9788408160292',
                'editorial' => 'Planeta',
                'fecha_publicacion' => '2001-04-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/8590132-L.jpg',
                'stock_total' => 4, 'stock_disponible' => 4
            ],
            [
                'titulo' => 'Don Quijote de la Mancha',
                'isbn_libro' => '9788491050262',
                'editorial' => 'Espasa',
                'fecha_publicacion' => '1605-01-16',
                'imagen' => 'https://covers.openlibrary.org/b/id/8231856-L.jpg',
                'stock_total' => 6, 'stock_disponible' => 6
            ],
            [
                'titulo' => 'El amor en los tiempos del cólera',
                'isbn_libro' => '9780307389732',
                'editorial' => 'Alfaguara',
                'fecha_publicacion' => '1985-09-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/11554363-L.jpg',
                'stock_total' => 3, 'stock_disponible' => 3
            ],
            [
                'titulo' => 'La ciudad y los perros',
                'isbn_libro' => '9780143105666',
                'editorial' => 'Seix Barral',
                'fecha_publicacion' => '1963-01-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/9251236-L.jpg',
                'stock_total' => 2, 'stock_disponible' => 2
            ],
            [
                'titulo' => 'Rayuela',
                'isbn_libro' => '9786071607167',
                'editorial' => 'Sudamericana',
                'fecha_publicacion' => '1963-01-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/6979862-L.jpg',
                'stock_total' => 4, 'stock_disponible' => 3
            ],
            [
                'titulo' => 'El túnel',
                'isbn_libro' => '9788497592971',
                'editorial' => 'Alfaguara',
                'fecha_publicacion' => '1948-01-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/8542951-L.jpg',
                'stock_total' => 2, 'stock_disponible' => 1
            ],
            [
                'titulo' => 'Ficciones',
                'isbn_libro' => '9789507314504',
                'editorial' => 'Sudamericana',
                'fecha_publicacion' => '1944-01-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/8232001-L.jpg',
                'stock_total' => 3, 'stock_disponible' => 2
            ],
            [
                'titulo' => 'La casa de los espíritus',
                'isbn_libro' => '9780140157526',
                'editorial' => 'Plaza & Janés',
                'fecha_publicacion' => '1982-01-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/8231700-L.jpg',
                'stock_total' => 4, 'stock_disponible' => 4
            ],
            [
                'titulo' => 'El coronel no tiene quien le escriba',
                'isbn_libro' => '9780307387561',
                'editorial' => 'Alfaguara',
                'fecha_publicacion' => '1961-01-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/10157527-L.jpg',
                'stock_total' => 2, 'stock_disponible' => 2
            ],
            [
                'titulo' => 'Los detectives salvajes',
                'isbn_libro' => '9780307474971',
                'editorial' => 'Anagrama',
                'fecha_publicacion' => '1998-01-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/8231625-L.jpg',
                'stock_total' => 3, 'stock_disponible' => 3
            ],
            [
                'titulo' => 'El Aleph',
                'isbn_libro' => '9788491050231',
                'editorial' => 'Emecé',
                'fecha_publicacion' => '1949-01-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/8232319-L.jpg',
                'stock_total' => 3, 'stock_disponible' => 3
            ],
            [
                'titulo' => 'Nada',
                'isbn_libro' => '9788483094038',
                'editorial' => 'Anagrama',
                'fecha_publicacion' => '1947-01-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/8231657-L.jpg',
                'stock_total' => 2, 'stock_disponible' => 2
            ],
            [
                'titulo' => 'Tiempo de silencio',
                'isbn_libro' => '9788437609125',
                'editorial' => 'Seix Barral',
                'fecha_publicacion' => '1962-01-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/8231764-L.jpg',
                'stock_total' => 2, 'stock_disponible' => 1
            ],

            // 2 inglés
            [
                'titulo' => 'To Kill a Mockingbird',
                'isbn_libro' => '9780061120084',
                'editorial' => 'HarperCollins',
                'fecha_publicacion' => '1960-07-11',
                'imagen' => 'https://covers.openlibrary.org/b/id/9875290-L.jpg',
                'stock_total' => 4, 'stock_disponible' => 4
            ],
            [
                'titulo' => '1984',
                'isbn_libro' => '9780451524935',
                'editorial' => 'Secker & Warburg',
                'fecha_publicacion' => '1949-06-08',
                'imagen' => 'https://covers.openlibrary.org/b/id/15354179-L.jpg',
                'stock_total' => 3, 'stock_disponible' => 2
            ],

            // 2 japonés
            [
                'titulo' => '吾輩は猫である (I Am a Cat)',
                'isbn_libro' => '9784167112015',
                'editorial' => 'Iwanami Shoten',
                'fecha_publicacion' => '1905-01-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/7222246-L.jpg',
                'stock_total' => 2, 'stock_disponible' => 2
            ],
            [
                'titulo' => 'ノルウェイの森 (Norwegian Wood)',
                'isbn_libro' => '9784103534236',
                'editorial' => 'Kodansha',
                'fecha_publicacion' => '1987-09-04',
                'imagen' => 'https://covers.openlibrary.org/b/id/8224148-L.jpg',
                'stock_total' => 2, 'stock_disponible' => 2
            ],

            // 2 ruso
            [
                'titulo' => 'Война и мир (War and Peace)',
                'isbn_libro' => '9785170939488',
                'editorial' => 'The Russian Messenger',
                'fecha_publicacion' => '1869-01-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/10507269-L.jpg',
                'stock_total' => 3, 'stock_disponible' => 3
            ],
            [
                'titulo' => 'Преступление и наказание (Crime and Punishment)',
                'isbn_libro' => '9785170939495',
                'editorial' => 'The Russian Messenger',
                'fecha_publicacion' => '1866-01-01',
                'imagen' => 'https://covers.openlibrary.org/b/id/10507249-L.jpg',
                'stock_total' => 3, 'stock_disponible' => 3
            ],
        ];

        // Insertar libros manuales con id_ubicacion / id_genero asignados (sin duplicados)
        foreach ($manualBooks as $b) {
            $b['id_ubicacion'] = Ubicacion::inRandomOrder()->value('id_ubicacion');
            $b['id_genero'] = Genero::inRandomOrder()->value('id_genero');

            // usar updateOrCreate para evitar duplicados
            Libro::updateOrCreate(
                ['isbn_libro' => $b['isbn_libro']],
                [
                    'titulo' => Str::limit($b['titulo'], 150),
                    'editorial' => $b['editorial'] ?? 'Editorial sin info',
                    'fecha_publicacion' => $b['fecha_publicacion'] ?? null,
                    'imagen' => $b['imagen'] ?? null,
                    'stock_total' => $b['stock_total'] ?? 1,
                    'stock_disponible' => $b['stock_disponible'] ?? 1,
                    'id_ubicacion' => $b['id_ubicacion'] ?? null,
                    'id_genero' => $b['id_genero'] ?? null,
                ]
            );
        }

        // /// ----- LIBROS CREADOS POR FACTORY (evitar duplicados por ISBN) -----
        // $toCreate = 50;
        // $created = 0;
        // $attempts = 0;
        // $maxAttempts = $toCreate * 5;

        // while ($created < $toCreate && $attempts < $maxAttempts) {
        //     $attempts++;

        //     // make() para chequear sin persistir
        //     $candidate = Libro::factory()->make();

        //     // Normalizar isbn y truncar
        //     $isbn = (string) $candidate->isbn_libro;
        //     $isbn = preg_replace('/[^0-9Xx]/', '', $isbn);
        //     if ($isbn === '') {
        //         $isbn = (string) (mt_rand(1000000000000, 9999999999999));
        //     }
        //     $isbn = substr($isbn, 0, 17);

        //     if (Libro::where('isbn_libro', $isbn)->exists()) {
        //         // ya existe -> saltar
        //         continue;
        //     }

        //     // Persistir
        //     $data = $candidate->toArray();
        //     $data['isbn_libro'] = $isbn;
        //     $data['titulo'] = Str::limit($data['titulo'], 150);
        //     // asegúrate que id_ubicacion y id_genero existen o son null
        //     $data['id_ubicacion'] = $data['id_ubicacion'] ?? Ubicacion::inRandomOrder()->value('id_ubicacion');
        //     $data['id_genero'] = $data['id_genero'] ?? Genero::inRandomOrder()->value('id_genero');

        //     Libro::create($data);
        //     $created++;
        // }

        // // relacionar autores aleatorios si corresponde (optimización: en batch)
        // Libro::inRandomOrder()->take(30)->get()->each(function ($libro) {
        //     if (method_exists($libro, 'autores')) {
        //         $autores = \App\Models\Autor::inRandomOrder()->take(rand(1,3))->pluck('id_autor')->toArray();
        //         if (!empty($autores)) $libro->autores()->syncWithoutDetaching($autores);
        //     }
        // });
    }
}
