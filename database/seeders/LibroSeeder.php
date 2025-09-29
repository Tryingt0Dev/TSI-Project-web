<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Libro;
use App\Models\Autor;
use App\Models\GeneroLiterario;

class LibroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $autor1 = Autor::where('nombre', 'Gabriel García Márquez')->first();
        $autor2 = Autor::where('nombre', 'Isabel Allende')->first();
        $genero1 = GeneroLiterario::where('nombre', 'Novela')->first();
        $genero2 = GeneroLiterario::where('nombre', 'Fábula')->first();
        Libro::create([
            'isbn_libro' => '607-071-4865',
            'titulo' => 'Cien años de soledad',
            'autor_id' => $autor1->id,
            'genero_id' => $genero1->id,
            'editorial' => 'Editorial Sudamericana',
            'fecha_publicacion' => '1967-01-01',
            'stock_total' => 10,
            'stock_disponible' => 10,
        ]);

        Libro::create([
            'isbn_libro' => '978-987-7514308',
            'titulo' => 'El Principito',
            'autor_id' => $autor2->id,
            'genero_id' => $genero2->id,
            'editorial' => 'Editorial Gallimard',
            'fecha_publicacion' => '1943-01-01',
            'stock_total' => 5,
            'stock_disponible' => 5,
        ]);

    }
}
