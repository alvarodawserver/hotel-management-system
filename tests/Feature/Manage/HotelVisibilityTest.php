<?php

use App\Models\Hotel;
use App\Models\Image;
use App\Models\Room;

it('refuses to publish a hotel without an active room or a photo', function () {
    $hotel = Hotel::factory()->create();
    Room::factory()->for($hotel)->inactive()->create();

    $this->actingAs($hotel->owner)
        ->put(route('manage.hotels.visibility.update', $hotel), ['is_visible' => true])
        ->assertSessionHasErrors('is_visible');

    expect($hotel->refresh()->is_visible)->toBeFalse();
});

it('publishes a hotel with an active room and a photo', function () {
    $hotel = Hotel::factory()->create();
    Room::factory()->for($hotel)->create();
    Image::factory()->for($hotel, 'imageable')->create();

    $this->actingAs($hotel->owner)
        ->put(route('manage.hotels.visibility.update', $hotel), ['is_visible' => true])
        ->assertSessionHasNoErrors();

    expect($hotel->refresh()->is_visible)->toBeTrue();
});

it('refuses to publish a hotel that is not on the map', function () {
    $hotel = Hotel::factory()->withoutLocation()->create();
    Room::factory()->for($hotel)->create();
    Image::factory()->for($hotel, 'imageable')->create();

    $this->actingAs($hotel->owner)
        ->put(route('manage.hotels.visibility.update', $hotel), ['is_visible' => true])
        ->assertSessionHasErrors('is_visible');

    expect($hotel->refresh()->is_visible)->toBeFalse();
});

it('always lets the owner hide the hotel', function () {
    $hotel = Hotel::factory()->visible()->create();

    $this->actingAs($hotel->owner)
        ->put(route('manage.hotels.visibility.update', $hotel), ['is_visible' => false])
        ->assertSessionHasNoErrors();

    expect($hotel->refresh()->is_visible)->toBeFalse();
});

it('keeps blocked hotels out of the published scope even when visible', function () {
    Hotel::factory()->visible()->blocked()->create();
    $published = Hotel::factory()->visible()->create();
    Hotel::factory()->create();

    expect(Hotel::published()->pluck('id')->all())->toBe([$published->id]);
});
