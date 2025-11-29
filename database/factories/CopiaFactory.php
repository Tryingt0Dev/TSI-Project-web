<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Copia;
use App\Models\Libro;
use App\Models\Ubicacion;

class CopiaSeeder extends Seeder
{
    public function run(): void
    {
        // Asegurarnos de que hay ubicaciones: crear algunas si no existen
        if (Ubicacion::count() === 0) {
            Ubicacion::factory(10)->create();
        }

        // Obtener una ubicacion por defecto (clave correcta) para fallback
        $ubicDefault = Ubicacion::inRandomOrder()->first();
        $ubicKeyName = $ubicDefault ? $ubicDefault->getKeyName() : null;
        $ubicDefaultValue = $ubicDefault ? $ubicDefault->{$ubicKeyName} : null;

        // Por cada libro, crear entre 1 y 6 copias
        Libro::all()->each(function($libro) use ($ubicDefaultValue) {
            $num = rand(1,6);
            for ($i=0;$i<$num;$i++){
                // obtener llave primaria del libro de forma genérica
                $libKeyName = $libro->getKeyName();
                $libKeyValue = $libro->{$libKeyName};

                // intentamos obtener una ubicacion aleatoria; si no hay, usamos fallback creado arriba
                $ubic = Ubicacion::inRandomOrder()->first();
                if ($ubic) {
                    $ubicKeyName = $ubic->getKeyName();
                    $ubicKeyValue = $ubic->{$ubicKeyName};
                } else {
                    $ubicKeyValue = $ubicDefaultValue;
                }

                // crear copia (id_ubicacion no nulo)
                Copia::create([
                    'id_libro_interno' => $libKeyValue,
                    'estado' => 'disponible', // valor por defecto no nulo
                    'id_ubicacion' => $ubicKeyValue,
                ]);
            }
        });
    }
}
