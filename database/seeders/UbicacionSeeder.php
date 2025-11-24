<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ubicacion;

class UbicacionSeeder extends Seeder
{
    public function run(): void
    {
        Ubicacion::create(['estante' => 'A', 'seccion' => 'Literatura']);
        Ubicacion::create(['estante' => 'B', 'seccion' => 'Historia']);
        Ubicacion::create(['estante' => 'C', 'seccion' => 'Ciencia']);
        Ubicacion::create(['estante' => 'D', 'seccion' => 'Arte']);
        Ubicacion::create(['estante' => 'E', 'seccion' => 'Filosofía']);
        Ubicacion::create(['estante' => 'F', 'seccion' => 'Infantil']);
    }
}
