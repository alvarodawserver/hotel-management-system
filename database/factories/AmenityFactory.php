<?php

namespace Database\Factories;

use App\Enums\AmenityIcon;
use App\Models\Amenity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Amenity>
 */
class AmenityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = fake()->unique()->word();

        return [
            'name' => ['es' => ucfirst($word), 'en' => ucfirst($word).' (en)'],
            'icon' => fake()->randomElement(AmenityIcon::cases())->value,
        ];
    }
}
