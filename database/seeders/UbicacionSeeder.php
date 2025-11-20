<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ubicacion;

class UbicacionSeeder extends Seeder
{
    public function run(): void
    {
        Ubicacion::create(['estante' => 'A']);
        Ubicacion::create(['estante' => 'B']);
        Ubicacion::create(['estante' => 'C']);
        Ubicacion::create(['seccion' => 'D']);
        Ubicacion::create(['seccion' => 'E']);
        Ubicacion::create(['seccion' => 'F']);
    }
}
