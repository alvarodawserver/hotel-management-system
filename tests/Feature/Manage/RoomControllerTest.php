<?php

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;

describe('store', function () {
    it('creates a room storing the price in cents', function () {
        $hotel = Hotel::factory()->create();
        $roomType = RoomType::factory()->create();

        $this->actingAs($hotel->owner)
            ->post(route('manage.hotels.rooms.store', $hotel), [
                'room_type_id' => $roomType->id,
                'name' => '101',
                'capacity' => 2,
                'price' => '89.50',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('manage.hotels.rooms.index', $hotel));

        expect(Room::sole())
            ->hotel_id->toBe($hotel->id)
            ->price_per_night->toBe(8950)
            ->is_active->toBeTrue();
    });

    it('rejects a room name already used in the same hotel', function () {
        $hotel = Hotel::factory()->create();
        $room = Room::factory()->for($hotel)->create(['name' => '101']);

        $this->actingAs($hotel->owner)
            ->post(route('manage.hotels.rooms.store', $hotel), [
                'room_type_id' => $room->room_type_id,
                'name' => '101',
                'capacity' => 2,
                'price' => '80',
            ])
            ->assertSessionHasErrors('name');
    });

    it('allows the same room name in different hotels', function () {
        $hotel = Hotel::factory()->create();
        $otherRoom = Room::factory()->create(['name' => '101']);

        $this->actingAs($hotel->owner)
            ->post(route('manage.hotels.rooms.store', $hotel), [
                'room_type_id' => $otherRoom->room_type_id,
                'name' => '101',
                'capacity' => 2,
                'price' => '80',
            ])
            ->assertSessionHasNoErrors();
    });

    it('does not let another owner add rooms', function () {
        $hotel = Hotel::factory()->create();

        $this->actingAs(User::factory()->owner()->create())
            ->post(route('manage.hotels.rooms.store', $hotel), [
                'room_type_id' => RoomType::factory()->create()->id,
                'name' => '101',
                'capacity' => 2,
                'price' => '80',
            ])
            ->assertRedirect(route('dashboard'));

        expect(Room::count())->toBe(0);
    });
});

describe('bulk store', function () {
    it('creates consecutively numbered rooms', function () {
        $hotel = Hotel::factory()->create();
        $roomType = RoomType::factory()->create();

        $this->actingAs($hotel->owner)
            ->post(route('manage.hotels.rooms.bulk.store', $hotel), [
                'room_type_id' => $roomType->id,
                'capacity' => 2,
                'price' => '90',
                'quantity' => 3,
                'first_number' => 101,
            ])
            ->assertSessionHasNoErrors();

        expect($hotel->rooms()->orderBy('name')->pluck('name')->all())->toBe(['101', '102', '103'])
            ->and($hotel->rooms()->pluck('price_per_night')->unique()->all())->toBe([9000]);
    });

    it('creates none of the rooms when a number is already taken', function () {
        $hotel = Hotel::factory()->create();
        $existing = Room::factory()->for($hotel)->create(['name' => '102']);

        $this->actingAs($hotel->owner)
            ->post(route('manage.hotels.rooms.bulk.store', $hotel), [
                'room_type_id' => $existing->room_type_id,
                'capacity' => 2,
                'price' => '90',
                'quantity' => 3,
                'first_number' => 101,
            ])
            ->assertSessionHasErrors('first_number');

        expect($hotel->rooms()->count())->toBe(1);
    });
});

describe('update and destroy', function () {
    it('returns 404 for a room that belongs to another hotel', function () {
        $hotel = Hotel::factory()->create();
        $foreignRoom = Room::factory()->create();

        $this->actingAs($hotel->owner)
            ->get(route('manage.hotels.rooms.edit', [$hotel, $foreignRoom]))
            ->assertNotFound();
    });

    it('deactivates a room when the active box is unticked', function () {
        $room = Room::factory()->create();

        $this->actingAs($room->hotel->owner)
            ->put(route('manage.hotels.rooms.update', [$room->hotel, $room]), [
                'room_type_id' => $room->room_type_id,
                'name' => $room->name,
                'capacity' => $room->capacity,
                'price' => '100',
            ])
            ->assertSessionHasNoErrors();

        expect($room->refresh())
            ->is_active->toBeFalse()
            ->price_per_night->toBe(10000);
    });

    it('soft deletes a room', function () {
        $room = Room::factory()->create();

        $this->actingAs($room->hotel->owner)
            ->delete(route('manage.hotels.rooms.destroy', [$room->hotel, $room]))
            ->assertRedirect(route('manage.hotels.rooms.index', $room->hotel));

        expect($room->refresh()->trashed())->toBeTrue();
    });
});
