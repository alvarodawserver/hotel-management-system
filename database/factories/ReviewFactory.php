<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A review of a stay that ended a week ago.
 *
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory()->stay(-10),
            'user_id' => fn (array $attributes) => Reservation::query()->whereKey($attributes['reservation_id'])->firstOrFail()->user_id,
            'hotel_id' => fn (array $attributes) => Reservation::query()->whereKey($attributes['reservation_id'])->firstOrFail()->hotel_id,
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->paragraph(),
        ];
    }

    /**
     * For the given reservation, keeping its customer and hotel.
     */
    public function forReservation(Reservation $reservation): static
    {
        return $this->state(fn (array $attributes) => [
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'hotel_id' => $reservation->hotel_id,
        ]);
    }

    /**
     * With the hotel's public reply.
     */
    public function withReply(): static
    {
        return $this->state(fn (array $attributes) => [
            'reply' => fake()->sentence(),
            'replied_at' => now(),
        ]);
    }

    /**
     * Reported by the hotel's owner, waiting for an admin.
     */
    public function reported(): static
    {
        return $this->state(fn (array $attributes) => [
            'reported_at' => now(),
            'reported_by' => User::factory()->owner(),
            'report_reason' => 'Insults to the staff',
        ]);
    }

    /**
     * Removed by an admin.
     */
    public function removed(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
            'deleted_by' => User::factory()->admin(),
            'deletion_reason' => 'Offensive language',
        ]);
    }
}
