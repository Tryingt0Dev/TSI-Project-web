<?php

namespace Database\Factories;

use App\Models\Ubicacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class UbicacionFactory extends Factory
{
    protected $model = Ubicacion::class;

    public function definition()
    {
        return [
            
            'estante' => 'E' . $this->faker->numberBetween(1, 40),
            'seccion' => strtoupper($this->faker->lexify('??')), // Ej: AB
            
        ];
    }
}
