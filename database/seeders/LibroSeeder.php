<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Libro;
use App\Models\Ubicacion;
use App\Models\Genero;

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

        // Crear X libros — ajusta el número a tu gusto
        $num = 150; // <- cambia aquí si quieres más o menos
        Libro::factory($num)->create()->each(function ($libro) {
            // opcional: si tienes relación con autores, asócialos aquí
            if (method_exists($libro, 'autores')) {
                $autores = \App\Models\Autor::inRandomOrder()->take(rand(1,3))->pluck('id_autor')->toArray();
                if (!empty($autores)) $libro->autores()->attach($autores);
            }
        });
    }
}
