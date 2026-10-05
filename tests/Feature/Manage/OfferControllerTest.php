<?php

use App\Models\Hotel;
use App\Models\Offer;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Support\Arr;

/**
 * @return array<string, mixed>
 */
function offerPayload(array $overrides = []): array
{
    return [
        'title' => 'Escapada de primavera',
        'discount_percent' => 20,
        'starts_on' => today()->toDateString(),
        'ends_on' => today()->addWeek()->toDateString(),
        'is_active' => '1',
        ...$overrides,
    ];
}

it('creates an offer for the whole hotel', function () {
    $hotel = Hotel::factory()->create();

    $this->actingAs($hotel->owner)
        ->post(route('manage.hotels.offers.store', $hotel), offerPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('manage.hotels.offers.index', $hotel));

    expect(Offer::sole())
        ->hotel_id->toBe($hotel->id)
        ->room_type_id->toBeNull()
        ->discount_percent->toBe(20)
        ->is_active->toBeTrue();
});

it('creates an offer for a room type the hotel has', function () {
    $room = Room::factory()->create();

    $this->actingAs($room->hotel->owner)
        ->post(route('manage.hotels.offers.store', $room->hotel), offerPayload(['room_type_id' => $room->room_type_id]))
        ->assertSessionHasNoErrors();

    expect(Offer::sole()->room_type_id)->toBe($room->room_type_id);
});

it('rejects a room type the hotel does not have', function () {
    $hotel = Hotel::factory()->create();
    Room::factory()->for($hotel)->create();
    $otherType = RoomType::factory()->create();

    $this->actingAs($hotel->owner)
        ->post(route('manage.hotels.offers.store', $hotel), offerPayload(['room_type_id' => $otherType->id]))
        ->assertSessionHasErrors('room_type_id');
});

it('rejects an end date before the start date', function () {
    $hotel = Hotel::factory()->create();

    $this->actingAs($hotel->owner)
        ->post(route('manage.hotels.offers.store', $hotel), offerPayload([
            'starts_on' => today()->addWeek()->toDateString(),
            'ends_on' => today()->addDay()->toDateString(),
        ]))
        ->assertSessionHasErrors('ends_on');
});

it('rejects a new offer that already ended', function () {
    $hotel = Hotel::factory()->create();

    $this->actingAs($hotel->owner)
        ->post(route('manage.hotels.offers.store', $hotel), offerPayload([
            'starts_on' => today()->subWeek()->toDateString(),
            'ends_on' => today()->subDay()->toDateString(),
        ]))
        ->assertSessionHasErrors('ends_on');
});

it('rejects discounts outside 1 to 90 percent', function (int $discount) {
    $hotel = Hotel::factory()->create();

    $this->actingAs($hotel->owner)
        ->post(route('manage.hotels.offers.store', $hotel), offerPayload(['discount_percent' => $discount]))
        ->assertSessionHasErrors('discount_percent');
})->with([
    'zero' => 0,
    'too big' => 95,
]);

it('switches an offer off when the active box is unticked', function () {
    $offer = Offer::factory()->create();

    $this->actingAs($offer->hotel->owner)
        ->put(route('manage.hotels.offers.update', [$offer->hotel, $offer]), Arr::except(offerPayload(), 'is_active'))
        ->assertSessionHasNoErrors();

    expect($offer->refresh()->is_active)->toBeFalse();
});

it('does not let another owner manage the offers', function () {
    $hotel = Hotel::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('manage.hotels.offers.store', $hotel), offerPayload())
        ->assertRedirect(route('dashboard'));

    expect(Offer::count())->toBe(0);
});

it('deletes an offer', function () {
    $offer = Offer::factory()->create();

    $this->actingAs($offer->hotel->owner)
        ->delete(route('manage.hotels.offers.destroy', [$offer->hotel, $offer]))
        ->assertRedirect(route('manage.hotels.offers.index', $offer->hotel));

    expect(Offer::count())->toBe(0);
});
