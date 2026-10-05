<?php

use App\Models\Reservation;
use App\Models\Room;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo('2026-10-05 10:00:00');
});

/**
 * Whether the room is free from 15 to 18 October.
 */
function isFreeForStay(Room $room): bool
{
    return Room::query()
        ->availableBetween(CarbonImmutable::parse('2026-10-15'), CarbonImmutable::parse('2026-10-18'))
        ->whereKey($room->id)
        ->exists();
}

it('blocks a room with an overlapping reservation that holds it', function (string $state) {
    $room = Room::factory()->create();
    Reservation::factory()->forRoom($room)->{$state}()->stay(11, 4)->create();

    expect(isFreeForStay($room))->toBeFalse();
})->with([
    'pending, still inside the payment window' => 'pending',
    'confirmed' => 'confirmed',
]);

it('frees a room whose reservation expired or was cancelled', function (string $state) {
    $room = Room::factory()->create();
    Reservation::factory()->forRoom($room)->{$state}()->stay(10)->create();

    expect(isFreeForStay($room))->toBeTrue();
})->with(['expired', 'cancelled']);

it('frees a room when the pending reservation’s payment window ran out', function () {
    $room = Room::factory()->create();
    Reservation::factory()->forRoom($room)->pending()->stay(10)->create(['expires_at' => now()->subMinute()]);

    expect(isFreeForStay($room))->toBeTrue();
});

it('lets one stay start on the day the previous one ends', function () {
    $room = Room::factory()->create();
    Reservation::factory()->forRoom($room)->stay(7, 3)->create();
    Reservation::factory()->forRoom($room)->stay(13, 2)->create();

    expect(isFreeForStay($room))->toBeTrue();
});
