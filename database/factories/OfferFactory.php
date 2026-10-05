<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
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
            'room_type_id' => null,
            'title' => fake()->randomElement(['Escapada de primavera', 'Verano en la costa', 'Oferta de última hora']),
            'discount_percent' => 20,
            'starts_on' => today()->subDay(),
            'ends_on' => today()->addMonth(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the owner has switched the offer off.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
