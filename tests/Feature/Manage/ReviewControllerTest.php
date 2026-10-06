<?php

use App\Models\Hotel;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the owner their hotel’s reviews with the rating', function () {
    $hotel = Hotel::factory()->create();
    reviewHotel($hotel, 5);
    reviewHotel($hotel, 2);
    reviewHotel(Hotel::factory()->create());

    $this->actingAs($hotel->owner)
        ->get(route('manage.hotels.reviews.index', $hotel))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('manage/hotels/reviews')
            ->has('reviews.data', 2)
            ->where('rating.average', 3.5)
            ->where('rating.count', 2)
            ->where('canReport', true));
});

it('does not show another owner’s reviews', function () {
    $hotel = Hotel::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->get(route('manage.hotels.reviews.index', $hotel))
        ->assertRedirect();
});

it('lets the owner and admins reply, edit and delete the reply', function (string $who) {
    $hotel = Hotel::factory()->create();
    $review = reviewHotel($hotel);
    $user = $who === 'owner' ? $hotel->owner : User::factory()->admin()->create();

    $this->actingAs($user)
        ->put(route('manage.hotels.reviews.reply.update', [$hotel, $review]), ['reply' => 'Thank you, see you soon!'])
        ->assertSessionHasNoErrors();

    expect($review->fresh())
        ->reply->toBe('Thank you, see you soon!')
        ->replied_at->not->toBeNull();

    $this->actingAs($user)
        ->delete(route('manage.hotels.reviews.reply.destroy', [$hotel, $review]));

    expect($review->fresh()->reply)->toBeNull();
})->with(['owner', 'admin']);

it('does not let an owner reply to another hotel’s review', function () {
    $hotel = Hotel::factory()->create();
    $review = reviewHotel($hotel);

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('manage.hotels.reviews.reply.update', [$hotel, $review]), ['reply' => 'Hi']);

    expect($review->fresh()->reply)->toBeNull();
});

it('finds no review of another hotel through this hotel', function () {
    $hotel = Hotel::factory()->create();
    $review = reviewHotel(Hotel::factory()->create());

    $this->actingAs($hotel->owner)
        ->put(route('manage.hotels.reviews.reply.update', [$hotel, $review]), ['reply' => 'Hi'])
        ->assertNotFound();
});

it('lets the owner report a review to the admins', function () {
    $hotel = Hotel::factory()->create();
    $review = reviewHotel($hotel);

    $this->actingAs($hotel->owner)
        ->post(route('manage.hotels.reviews.report', [$hotel, $review]), ['reason' => 'Insults to the staff'])
        ->assertSessionHasNoErrors();

    expect($review->fresh())
        ->isReported()->toBeTrue()
        ->reported_by->toBe($hotel->owner_id)
        ->report_reason->toBe('Insults to the staff');
});

it('does not let admins report, since they remove directly', function () {
    $hotel = Hotel::factory()->create();
    $review = reviewHotel($hotel);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('manage.hotels.reviews.report', [$hotel, $review]), ['reason' => 'Spam']);

    expect($review->fresh()->isReported())->toBeFalse();
});
