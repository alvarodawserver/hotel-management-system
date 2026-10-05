<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'name' => fake()->randomElement(['Yoga en la playa', 'Clase de cocina', 'Excursión en barco', 'Cata de vinos', 'Ruta en bicicleta']),
            'description' => fake()->sentence(),
            'price' => 0,
            'starts_at' => null,
            'ends_at' => null,
            'capacity' => null,
        ];
    }
}
