<?php

use App\Actions\Pricing\CalculateStayPrice;
use App\Models\Offer;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\CarbonImmutable;

/**
 * A 3-night stay (1, 2 and 3 June) in a 100 € room.
 */
function priceStay(Room $room, string $checkIn = '2030-06-01', string $checkOut = '2030-06-04')
{
    return app(CalculateStayPrice::class)->handle(
        $room,
        CarbonImmutable::parse($checkIn),
        CarbonImmutable::parse($checkOut),
    );
}

it('charges the base price for every night when there are no offers', function () {
    $room = Room::factory()->create(['price_per_night' => 10000]);

    $stay = priceStay($room);

    expect($stay->nightCount())->toBe(3)
        ->and($stay->subtotal)->toBe(30000)
        ->and($stay->discount)->toBe(0)
        ->and($stay->total)->toBe(30000);
});

it('discounts only the nights covered by an offer that starts mid-stay', function () {
    $room = Room::factory()->create(['price_per_night' => 10000]);
    Offer::factory()->for($room->hotel)->create([
        'discount_percent' => 20,
        'starts_on' => '2030-06-02',
        'ends_on' => '2030-06-30',
    ]);

    $stay = priceStay($room);

    expect(array_column($stay->nights, 'price'))->toBe([10000, 8000, 8000])
        ->and($stay->total)->toBe(26000)
        ->and($stay->discount)->toBe(4000);
});

it('includes the offer end date as a discounted night', function () {
    $room = Room::factory()->create(['price_per_night' => 10000]);
    Offer::factory()->for($room->hotel)->create([
        'discount_percent' => 50,
        'starts_on' => '2030-05-01',
        'ends_on' => '2030-06-02',
    ]);

    expect(array_column(priceStay($room)->nights, 'discount_percent'))->toBe([50, 50, 0]);
});

it('applies a room type offer only to rooms of that type', function () {
    $suite = RoomType::factory()->create();
    $suiteRoom = Room::factory()->create(['room_type_id' => $suite->id, 'price_per_night' => 10000]);
    $otherRoom = Room::factory()->for($suiteRoom->hotel)->create(['price_per_night' => 10000]);
    Offer::factory()->for($suiteRoom->hotel)->create([
        'room_type_id' => $suite->id,
        'discount_percent' => 30,
        'starts_on' => '2030-06-01',
        'ends_on' => '2030-06-30',
    ]);

    expect(priceStay($suiteRoom)->total)->toBe(21000)
        ->and(priceStay($otherRoom)->total)->toBe(30000);
});

it('applies only the best of several overlapping offers', function () {
    $room = Room::factory()->create(['price_per_night' => 10000]);
    Offer::factory()->for($room->hotel)->create(['discount_percent' => 10, 'starts_on' => '2030-06-01', 'ends_on' => '2030-06-30']);
    Offer::factory()->for($room->hotel)->create(['discount_percent' => 25, 'starts_on' => '2030-06-01', 'ends_on' => '2030-06-30']);

    $stay = priceStay($room);

    expect($stay->bestDiscountPercent())->toBe(25)
        ->and($stay->total)->toBe(22500);
});

it('ignores disabled offers', function () {
    $room = Room::factory()->create(['price_per_night' => 10000]);
    Offer::factory()->for($room->hotel)->inactive()->create(['starts_on' => '2030-06-01', 'ends_on' => '2030-06-30']);

    expect(priceStay($room)->total)->toBe(30000);
});

it('ignores offers of other hotels', function () {
    $room = Room::factory()->create(['price_per_night' => 10000]);
    Offer::factory()->create(['starts_on' => '2030-06-01', 'ends_on' => '2030-06-30']);

    expect(priceStay($room)->total)->toBe(30000);
});

it('rounds each discounted night to the cent', function () {
    $room = Room::factory()->create(['price_per_night' => 8999]);
    Offer::factory()->for($room->hotel)->create(['discount_percent' => 15, 'starts_on' => '2030-06-01', 'ends_on' => '2030-06-30']);

    // 8999 - 15 % = 7649.15 → 7649 per night.
    expect(priceStay($room, '2030-06-01', '2030-06-02')->total)->toBe(7649);
});
