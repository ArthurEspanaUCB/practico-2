<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para el modelo Categoria
 *
 * Usado para generar datos de prueba en tests automatizados.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Categoria>
 */
class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    /**
     * Define el estado predeterminado del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word().' '.fake()->word(),
            'descripcion' => fake()->sentence(),
            'estado' => true,
        ];
    }

    /**
     * Estado: Categoría inactiva
     */
    public function inactiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => false,
        ]);
    }
}
