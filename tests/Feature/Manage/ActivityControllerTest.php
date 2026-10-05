<?php

use App\Models\Activity;
use App\Models\Hotel;

it('creates an activity storing the price in cents', function () {
    $hotel = Hotel::factory()->create();

    $this->actingAs($hotel->owner)
        ->post(route('manage.hotels.activities.store', $hotel), [
            'name' => 'Yoga en la playa',
            'description' => 'Cada mañana frente al mar.',
            'price' => '12.50',
            'starts_at' => '08:00',
            'ends_at' => '09:00',
            'capacity' => 15,
        ])
        ->assertSessionHasNoErrors();

    expect(Activity::sole())
        ->hotel_id->toBe($hotel->id)
        ->price->toBe(1250)
        ->capacity->toBe(15);
});

it('rejects an end time before the start time', function () {
    $hotel = Hotel::factory()->create();

    $this->actingAs($hotel->owner)
        ->post(route('manage.hotels.activities.store', $hotel), [
            'name' => 'Cata',
            'description' => 'Vinos de la zona.',
            'price' => '0',
            'starts_at' => '20:00',
            'ends_at' => '19:00',
        ])
        ->assertSessionHasErrors('ends_at');
});

it('deletes an activity', function () {
    $activity = Activity::factory()->create();

    $this->actingAs($activity->hotel->owner)
        ->delete(route('manage.hotels.activities.destroy', [$activity->hotel, $activity]))
        ->assertRedirect(route('manage.hotels.activities.index', $activity->hotel));

    expect(Activity::count())->toBe(0);
});
