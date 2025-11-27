<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
        AutorSeeder::class,
        UbicacionSeeder::class,
        GeneroSeeder::class,
        LibroSeeder::class,
        CopiaSeeder::class,
        UserSeeder::class,
        AlumnoSeeder::class,
        PrestamoSeeder::class,
        ]);
    }
}
