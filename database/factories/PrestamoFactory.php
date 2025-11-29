<?php

namespace Database\Factories;

use App\Models\Prestamo;
use App\Models\Alumno;
use App\Models\Copia;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrestamoFactory extends Factory
{
    protected $model = Prestamo::class;

    public function definition()
    {
        $alumno = Alumno::inRandomOrder()->first() ?: Alumno::factory()->create();
        $copia = Copia::inRandomOrder()->first() ?: Copia::factory()->create();

        $cKeyName = $copia->getKeyName();
        $cKeyValue = $copia->{$cKeyName};

        return [
            'rut_alumno' => $alumno->rut_alumno ?? $alumno->{$alumno->getKeyName()},
            // no seteamos la columna de FK dentro del factory porque el nombre varía;
            // mejor usar el seeder para asignar la columna correcta dinámicamente.
            'fecha_prestamo' => now()->subDays(rand(1,7)),
            'fecha_devolucion' => now()->addDays(rand(7,8)),
            'estado' => 'activo',
        ];
    }
}
