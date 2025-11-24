<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Genero;

class GeneroSeeder extends Seeder
{
    public function run(): void
    {
        Genero::create(['nombre' => 'Novela']);
        Genero::create(['nombre' => 'Fábula']);
        Genero::create(['nombre' => 'Ciencia Ficción']);
        Genero::create(['nombre' => 'Misterio']);
    }
}
