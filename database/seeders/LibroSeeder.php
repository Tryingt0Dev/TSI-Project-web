<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Libro;
use App\Models\Autor;
use App\Models\Genero;
use App\Models\Ubicacion;

class LibroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $autor1 = Autor::where('nombre', 'Gabriel García Márquez')->first();
        $autor2 = Autor::where('nombre', 'Isabel Allende')->first();
        $genero1 = Genero::where('nombre', 'Novela')->first();
        $genero2 = Genero::where('nombre', 'Fábula')->first();
        $ubicacion1 = Ubicacion::where('estante', 'A')->first();
        $ubicacion2 = Ubicacion::where('estante', 'B')->first();
        Libro::create([
            'id_libro_interno' => 1,
            'isbn_libro' => '607-071-4865',
            'titulo' => 'Cien años de soledad',
            'fecha_publicacion' => '1967-01-01',
            'editorial' => 'Editorial Sudamericana',
            'stock_total' => 10,
            'stock_disponible' => 10,
            'id_autor' => $autor1->id_autor,
            'id_genero' => $genero1->id_genero,
            'id_ubicacion' => $ubicacion1->id_ubicacion,
        ]);

        Libro::create([
            'id_libro_interno' => 2,
            'isbn_libro' => '978-987-7514308',
            'titulo' => 'El Principito',
            'id_autor' => $autor2->id_autor,
            'id_genero' => $genero2->id_genero,
            'editorial' => 'Editorial Gallimard',
            'fecha_publicacion' => '1943-01-01',
            'stock_total' => 5,
            'stock_disponible' => 5,
            'id_ubicacion' => $ubicacion2->id_ubicacion,
        ]);

    }
}
