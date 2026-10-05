<?php

namespace Database\Factories;

use App\Enums\Province;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hotel>
 */
class HotelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Hotel '.fake()->unique()->lastName();

        return [
            'owner_id' => User::factory()->owner(),
            'name' => $name,
            'description' => fake()->paragraphs(2, true),
            'province' => fake()->randomElement(Province::cases()),
            'municipality' => fake()->city(),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(36.4, 37.2),
            'longitude' => fake()->longitude(-7.4, -1.7),
            'stars' => fake()->numberBetween(1, 5),
            'cancellation_policy' => Hotel::DEFAULT_CANCELLATION_POLICY,
            'is_visible' => false,
        ];
    }

    /**
     * Indicate that the owner has made the hotel visible.
     */
    public function visible(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => true,
        ]);
    }

    /**
     * Indicate that the hotel has not been placed on the map yet.
     */
    public function withoutLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    /**
     * Indicate that an admin has blocked the hotel.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'blocked_at' => now(),
            'blocked_reason' => 'Contenido inapropiado',
        ]);
    }
}
