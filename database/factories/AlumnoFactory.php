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
            'rut_alumno'       => $this->generateRealRut(),
            'nombre_alumno'    => $this->faker->firstName(),
            'apellido_alumno'  => $this->faker->lastName(),
            'fecha_registro'   => now(),
            'atrasos'          => $this->faker->numberBetween(0, 8),
            'permiso_prestamo' => $this->faker->boolean(90),
        ];
    }

    /**
     * Genera un RUT realista con DV válido.
     */
    private function generateRealRut()
    {
        // Rango realista (alumnos jóvenes): 8–10 dígitos
        // Ejemplo: 11.123.456-K
        $numero = $this->faker->numberBetween(7_000_000, 26_000_000);

        $dv = $this->calculateDv($numero);

        return "{$numero}-{$dv}";
    }

    /**
     * Calcula dígito verificador (DV) real para un RUT 
     */
    private function calculateDv($rut)
    {
        $s = 1;
        $m = 0;

        while ($rut > 0) {
            $s = ($s + ($rut % 10) * (9 - $m % 6)) % 11;
            $rut = intdiv($rut, 10);
            $m++;
        }

        return $s ? $s - 1 : 'K';
    }
}
