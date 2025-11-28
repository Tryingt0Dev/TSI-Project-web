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
            // crea 4 copias por libro de ejemplo
                Copia::create([
                    'id_libro_interno' => $libro->id_libro_interno,
                    'estado' => 'Disponible',
                    'id_ubicacion' => $libro->id_ubicacion,
                ]);
                Copia::create([
                    'id_libro_interno' => $libro->id_libro_interno,
                    'estado' => 'Disponible',
                    'id_ubicacion' => $libro->id_ubicacion,
                ]);
                Copia::create([
                    'id_libro_interno' => $libro->id_libro_interno,
                    'estado' => 'Disponible',
                    'id_ubicacion' => $libro->id_ubicacion,
                ]);
                Copia::create([
                    'id_libro_interno' => $libro->id_libro_interno,
                    'estado' => 'Perdido',
                    'id_ubicacion' => $libro->id_ubicacion,
                ]);
        }
    }
}