<?php

use App\Http\Controllers\Catalog\HotelPageController;
use App\Models\Hotel;
use App\Models\Offer;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows a published hotel by its slug', function () {
    $hotel = Hotel::factory()->visible()->create();

    $this->get(route('hotels.show', $hotel->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/show')
            ->where('hotel.id', $hotel->id)
            ->where('isPreview', false));
});

it('hides hidden hotels from the public', function () {
    $hotel = Hotel::factory()->create();

    $this->get(route('hotels.show', $hotel->slug))->assertNotFound();
});

it('hides blocked hotels from the public even when visible', function () {
    $hotel = Hotel::factory()->visible()->blocked()->create();

    $this->get(route('hotels.show', $hotel->slug))->assertNotFound();
});

it('lets the owner and admins preview an unpublished hotel', function () {
    $hotel = Hotel::factory()->create();

    $this->actingAs($hotel->owner)
        ->get(route('hotels.show', $hotel->slug))
        ->assertInertia(fn (Assert $page) => $page->where('isPreview', true));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('hotels.show', $hotel->slug))
        ->assertOk();

    $this->actingAs(User::factory()->owner()->create())
        ->get(route('hotels.show', $hotel->slug))
        ->assertNotFound();
});

it('groups identical active rooms into one option', function () {
    $hotel = Hotel::factory()->visible()->create();
    $double = RoomType::factory()->create();
    Room::factory()->for($hotel)->count(3)->sequence(['name' => '101'], ['name' => '102'], ['name' => '103'])
        ->create(['room_type_id' => $double->id, 'capacity' => 2, 'price_per_night' => 9000]);
    Room::factory()->for($hotel)->create(['capacity' => 4, 'price_per_night' => 15000]);
    Room::factory()->for($hotel)->inactive()->create();

    $this->get(route('hotels.show', $hotel->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->has('roomGroups', 2)
            ->where('roomGroups.0.rooms_count', 3)
            ->where('roomGroups.0.price_per_night', 9000));
});

it('includes the stay total with the offer when dates are given', function () {
    $hotel = Hotel::factory()->visible()->create();
    Room::factory()->for($hotel)->create(['capacity' => 2, 'price_per_night' => 10000]);
    $checkIn = today()->addDays(7);
    Offer::factory()->for($hotel)->create([
        'discount_percent' => 10,
        'starts_on' => $checkIn,
        'ends_on' => $checkIn->copy()->addMonth(),
    ]);

    $this->get(route('hotels.show', [
        'hotel' => $hotel->slug,
        'check_in' => $checkIn->toDateString(),
        'check_out' => $checkIn->copy()->addDays(3)->toDateString(),
    ]))->assertInertia(fn (Assert $page) => $page
        ->where('roomGroups.0.stay.total', 27000)
        ->where('roomGroups.0.stay.discount', 3000));
});

it('counts the rooms of each option still free for the dates', function () {
    $hotel = Hotel::factory()->visible()->create();
    $rooms = Room::factory()->for($hotel)->count(2)->sequence(['name' => '101'], ['name' => '102'])
        ->create(['capacity' => 2, 'price_per_night' => 9000, 'room_type_id' => RoomType::factory()->create()->id]);
    $checkIn = today()->addDays(10);
    Reservation::factory()->forRoom($rooms[0])->create([
        'check_in' => $checkIn->toDateString(),
        'check_out' => $checkIn->copy()->addDays(2)->toDateString(),
    ]);

    $this->get(route('hotels.show', [
        'hotel' => $hotel->slug,
        'check_in' => $checkIn->toDateString(),
        'check_out' => $checkIn->copy()->addDays(2)->toDateString(),
    ]))->assertInertia(fn (Assert $page) => $page
        ->where('roomGroups.0.rooms_count', 2)
        ->where('roomGroups.0.available_count', 1));
});

it('summarises the guest reviews, leaving out removed ones', function () {
    $hotel = Hotel::factory()->visible()->create();
    reviewHotel($hotel, 5);
    reviewHotel($hotel, 4);
    reviewHotel($hotel, 1)->delete();

    $this->get(route('hotels.show', $hotel->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rating.average', 4.5)
            ->where('rating.count', 2)
            ->where('rating.distribution.0', ['rating' => 5, 'count' => 1])
            ->where('rating.distribution.4', ['rating' => 1, 'count' => 0])
            ->has('reviews.data', 2));
});

it('shows the newest reviews first, a page at a time', function () {
    $hotel = Hotel::factory()->visible()->create();
    $this->travel(-1)->days();
    foreach (range(1, HotelPageController::REVIEWS_PER_PAGE) as $i) {
        reviewHotel($hotel);
    }
    $this->travelBack();
    $newest = reviewHotel($hotel);

    $this->get(route('hotels.show', $hotel->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->has('reviews.data', HotelPageController::REVIEWS_PER_PAGE)
            ->where('reviews.data.0.id', $newest->id));

    $this->get(route('hotels.show', [$hotel->slug, 'reviews_page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('reviews.data', 1));
});
