<?php

use App\Models\Hotel;
use App\Models\Room;
use Inertia\Testing\AssertableInertia as Assert;

it('compares published hotels in the chosen order', function () {
    [$first, $second] = Hotel::factory()->visible()->count(2)->create();
    Room::factory()->for($first)->create();
    Room::factory()->for($second)->create();

    $this->get(route('hotels.compare', ['ids' => [$second->id, $first->id]]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('catalog/compare')
            ->has('hotels', 2)
            ->where('hotels.0.id', $second->id));
});

it('leaves out hotels that are not published', function () {
    $published = Hotel::factory()->visible()->create();
    $hidden = Hotel::factory()->create();

    $this->get(route('hotels.compare', ['ids' => [$published->id, $hidden->id]]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('hotels', 1)
            ->where('hotels.0.id', $published->id));
});

it('compares at most three hotels', function () {
    $ids = Hotel::factory()->visible()->count(4)->create()->modelKeys();

    $this->get(route('hotels.compare', ['ids' => $ids]))
        ->assertSessionHasErrors('ids');
});
