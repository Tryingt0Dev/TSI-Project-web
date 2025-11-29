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
            UserSeeder::class,
            GeneroSeeder::class,
            AutorSeeder::class,
            UbicacionSeeder::class,
            LibroSeeder::class,
            CopiaSeeder::class,
            AlumnoSeeder::class,
            PrestamoSeeder::class,
        ]);
    }
}
