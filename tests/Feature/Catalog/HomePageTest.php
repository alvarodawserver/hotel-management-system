<?php

use App\Models\Hotel;
use App\Models\Room;
use Inertia\Testing\AssertableInertia as Assert;

it('only shows hotels that joined in the last 30 days as new', function () {
    $new = Hotel::factory()->visible()->create(['created_at' => now()->subDays(5)]);
    $old = Hotel::factory()->visible()->create(['created_at' => now()->subDays(45)]);
    Room::factory()->for($new)->create();
    Room::factory()->for($old)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->has('newHotels', 1)
            ->where('newHotels.0.id', $new->id));
});

it('has no new hotels when none joined recently', function () {
    $old = Hotel::factory()->visible()->create(['created_at' => now()->subDays(60)]);
    Room::factory()->for($old)->create();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->has('newHotels', 0));
});
