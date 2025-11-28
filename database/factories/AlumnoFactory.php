<?php

namespace Database\Factories;

use App\Models\Alumno;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlumnoFactory extends Factory
{
    protected $model = Alumno::class;

    public function definition()
    {
        return [
            'rut_alumno'        => strtoupper($this->faker->bothify('########-?')),
            'nombre_alumno'     => $this->faker->firstName(),
            'apellido_alumno'   => $this->faker->lastName(),
            'fecha_registro'    => now(),
            'atrasos'          => $this->faker->numberBetween(0, 8),
            'permiso_prestamo'  => $this->faker->boolean(90),
        ];
    }
}
