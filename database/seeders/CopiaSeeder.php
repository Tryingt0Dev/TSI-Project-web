<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Libro;
use App\Models\Copia;

class CopiaSeeder extends Seeder
{
    public function run(): void
    {
        $libros = Libro::all();

        foreach ($libros as $libro) {
            // crea 3 copias por libro de ejemplo
            for ($i = 0; $i < 3; $i++) {
                Copia::create([
                    'id_libro_interno' => $libro->id_libro_interno,
                    'estado' => 'bueno',
                    'ubicacion' => 'estantería A',
                ]);
            }
        }
    }
}