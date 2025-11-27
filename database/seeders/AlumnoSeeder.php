<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Alumno;

class AlumnoSeeder extends Seeder
{
    public function run(): void
    {
        Alumno::create([
                'rut_alumno' => '123123123',
                'nombre_alumno' => 'Isagi',
                'apellido_alumno' => 'Yoichi',
                'fecha_registro' => now()->subDays(10),
                'atrasos' => 1,
                'permiso_prestamo' => true,
        ]);

        Alumno::create([
                'rut_alumno' => '456456456',
                'nombre_alumno' => 'Yuki',
                'apellido_alumno' => 'Judai',
                'fecha_registro' => now()->subDays(10),
                'atrasos' => 1,
                'permiso_prestamo' => true,
        ]);
    }
}
