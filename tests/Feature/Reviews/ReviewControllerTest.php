<?php

use App\Models\Reservation;
use App\Models\Review;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array<string, mixed>
 */
function reviewPayload(array $overrides = []): array
{
    return [
        'rating' => 4,
        'comment' => 'Great views of the sea and a very friendly staff.',
        ...$overrides,
    ];
}

it('lets the guest review a stay from the check-out day', function () {
    $reservation = Reservation::factory()->stay(-3, 3)->create();

    $this->actingAs($reservation->user)
        ->post(route('reservations.review.store', $reservation), reviewPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('reservations.show', $reservation));

    expect(Review::sole())
        ->reservation_id->toBe($reservation->id)
        ->user_id->toBe($reservation->user_id)
        ->hotel_id->toBe($reservation->hotel_id)
        ->rating->toBe(4);
});

it('does not let the guest review before the check-out day', function () {
    $reservation = Reservation::factory()->stay(-2, 3)->create();

    $this->actingAs($reservation->user)
        ->post(route('reservations.review.store', $reservation), reviewPayload())
        ->assertRedirect();

    expect(Review::count())->toBe(0);
});

it('does not let the guest review stays that did not happen', function (string $state) {
    $reservation = Reservation::factory()->stay(-10)->{$state}()->create();

    $this->actingAs($reservation->user)
        ->post(route('reservations.review.store', $reservation), reviewPayload());

    expect(Review::count())->toBe(0);
})->with(['cancelled', 'expired']);

it('does not let a customer review someone else’s stay', function () {
    $reservation = Reservation::factory()->stay(-10)->create();
    $other = Reservation::factory()->stay(-10)->create()->user;

    $this->actingAs($other)
        ->post(route('reservations.review.store', $reservation), reviewPayload());

    expect(Review::count())->toBe(0);
});

it('allows one review per stay', function () {
    $review = Review::factory()->create();

    $this->actingAs($review->user)
        ->post(route('reservations.review.store', $review->reservation), reviewPayload());

    expect(Review::count())->toBe(1);
});

it('validates the rating and the comment', function (array $payload, string $field) {
    $reservation = Reservation::factory()->stay(-10)->create();

    $this->actingAs($reservation->user)
        ->post(route('reservations.review.store', $reservation), reviewPayload($payload))
        ->assertSessionHasErrors($field);
})->with([
    'rating too low' => [['rating' => 0], 'rating'],
    'rating too high' => [['rating' => 6], 'rating'],
    'comment too short' => [['comment' => 'Nice'], 'comment'],
    'comment too long' => [['comment' => str_repeat('a', Review::MAX_COMMENT_LENGTH + 1)], 'comment'],
]);

it('lets the author edit their review and marks it as edited', function () {
    $review = Review::factory()->create(['rating' => 2]);

    $this->actingAs($review->user)
        ->put(route('reservations.review.update', $review->reservation), reviewPayload(['rating' => 5]))
        ->assertSessionHasNoErrors();

    expect($review->fresh())
        ->rating->toBe(5)
        ->edited_at->not->toBeNull();
});

it('lets the author delete their review and write it again', function () {
    $review = Review::factory()->create();
    $reservation = $review->reservation;

    $this->actingAs($review->user)
        ->delete(route('reservations.review.destroy', $reservation))
        ->assertRedirect(route('reservations.show', $reservation));

    expect(Review::withTrashed()->count())->toBe(0);

    $this->actingAs($review->user)
        ->post(route('reservations.review.store', $reservation), reviewPayload())
        ->assertSessionHasNoErrors();

    expect(Review::count())->toBe(1);
});

it('does not let the author edit, delete or rewrite a review removed by an admin', function () {
    $review = Review::factory()->removed()->create(['rating' => 1]);
    $reservation = $review->reservation;

    $this->actingAs($review->user)
        ->put(route('reservations.review.update', $reservation), reviewPayload(['rating' => 5]));
    $this->actingAs($review->user)
        ->delete(route('reservations.review.destroy', $reservation))
        ->assertNotFound();
    $this->actingAs($review->user)
        ->post(route('reservations.review.store', $reservation), reviewPayload());

    expect(Review::withTrashed()->sole())
        ->rating->toBe(1)
        ->trashed()->toBeTrue();
});

it('shows the guest a form, their review or why it was removed', function () {
    $toReview = Reservation::factory()->stay(-10)->create();
    $reviewed = Reservation::factory()->for($toReview->user)->stay(-20)->create();
    $removed = Review::factory()->forReservation($reviewed)->removed()->create();

    $this->actingAs($toReview->user)
        ->get(route('reservations.show', $toReview))
        ->assertInertia(fn (Assert $page) => $page
            ->where('reservation.can_be_reviewed', true)
            ->where('reservation.review', null));

    $this->actingAs($toReview->user)
        ->get(route('reservations.show', $removed->reservation))
        ->assertInertia(fn (Assert $page) => $page
            ->where('reservation.can_be_reviewed', false)
            ->where('reservation.review.removal_reason', 'Offensive language'));
});
