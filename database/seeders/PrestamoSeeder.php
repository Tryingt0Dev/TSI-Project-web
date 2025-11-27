<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Prestamo;

class PrestamoSeeder extends Seeder
{
    public function run(): void
    {
        Prestamo::create([
            'id_usuario' => 1,
            'rut_alumno' => '123123123',
            'id_copia' => 1,
            'fecha_inicio' => now()->subDays(3), // para la fecha manual '2025-11-01'
            'fecha_limite' => now()->addDays(7),
            'entregado' => 0, // Pendiente
        ]);

        Prestamo::create([
            'id_usuario' => 2,
            'rut_alumno' => '456456456',
            'id_copia' => 4,
            'fecha_inicio' => now()->subDays(10),
            'fecha_limite' => now()->subDays(2),
            'entregado' => 1, // Devuelto
        ]);
    }
}