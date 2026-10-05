<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
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
            'room_type_id' => RoomType::factory(),
            'name' => (string) fake()->unique()->numberBetween(100, 999),
            'capacity' => fake()->numberBetween(1, 4),
            'price_per_night' => fake()->numberBetween(50, 300) * 100,
            'description' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the room is not offered for booking.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
