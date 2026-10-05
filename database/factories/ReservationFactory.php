<?php

namespace Database\Factories;

use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A paid, confirmed stay of 3 nights in 10 days by default.
 *
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'hotel_id' => fn (array $attributes) => Room::query()->withTrashed()->whereKey($attributes['room_id'])->firstOrFail()->hotel_id,
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(13)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'guest_name' => fake()->name(),
            'guest_phone' => fake()->numerify('6## ### ###'),
            'special_requests' => null,
            'status' => ReservationStatus::Confirmed,
            'price_breakdown' => fn (array $attributes) => $this->nightlyPrices($attributes),
            'subtotal' => fn (array $attributes) => array_sum(array_column($attributes['price_breakdown'], 'price')),
            'discount' => 0,
            'total_price' => fn (array $attributes) => $attributes['subtotal'],
            'stripe_checkout_session_id' => 'cs_test_'.fake()->unique()->bothify('??????????'),
            'stripe_payment_intent_id' => 'pi_test_'.fake()->unique()->bothify('??????????'),
            'expires_at' => now()->addMinutes(Reservation::PAYMENT_WINDOW_MINUTES),
            'paid_at' => now(),
        ];
    }

    /**
     * For the given room, keeping its hotel.
     */
    public function forRoom(Room $room): static
    {
        return $this->state(fn (array $attributes) => [
            'room_id' => $room->id,
            'hotel_id' => $room->hotel_id,
        ]);
    }

    /**
     * From the given check-in date (relative days from today), for some nights.
     */
    public function stay(int $daysFromToday, int $nights = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'check_in' => now()->addDays($daysFromToday)->toDateString(),
            'check_out' => now()->addDays($daysFromToday + $nights)->toDateString(),
        ]);
    }

    /**
     * Paid and confirmed by the Stripe webhook (the default).
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Confirmed,
        ]);
    }

    /**
     * Created and waiting for the payment, inside its payment window.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Pending,
            'stripe_payment_intent_id' => null,
            'paid_at' => null,
        ]);
    }

    /**
     * Never paid; its payment window has run out.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Expired,
            'stripe_payment_intent_id' => null,
            'paid_at' => null,
            'expires_at' => now()->subMinutes(5),
        ]);
    }

    /**
     * Cancelled by its customer, already refunded.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $attributes['user_id'],
            'refund_amount' => 0,
        ]);
    }

    /**
     * Cancelled with a refund Stripe could not make.
     */
    public function refundFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
            'refund_amount' => 10000,
            'refund_status' => RefundStatus::Failed,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<array{date: string, base: int, discount_percent: int, price: int}>
     */
    private function nightlyPrices(array $attributes): array
    {
        $price = Room::query()->withTrashed()->whereKey($attributes['room_id'])->firstOrFail()->price_per_night;
        $nights = [];

        for ($night = CarbonImmutable::parse($attributes['check_in']); $night->lt(CarbonImmutable::parse($attributes['check_out'])); $night = $night->addDay()) {
            $nights[] = ['date' => $night->toDateString(), 'base' => $price, 'discount_percent' => 0, 'price' => $price];
        }

        return $nights;
    }
}
