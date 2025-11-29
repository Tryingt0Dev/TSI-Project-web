<?php

namespace Database\Factories;

use App\Models\Autor;
use Illuminate\Database\Eloquent\Factories\Factory;

class AutorFactory extends Factory
{
    protected $model = Autor::class;

    public function definition()
    {
        return [
            // Ajusta campos si tu modelo Autor tiene otros atributos
            'nombre' => $this->faker->name(),
        ];
    }
}
