<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Ubicacion;

class UbicacionSeeder extends Seeder
{
    public function run(): void
    {
        Ubicacion::factory(30)->create();
    }
}
