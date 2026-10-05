<?php

use App\Enums\Province;
use App\Models\Hotel;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('lists every hotel for admins', function () {
    Hotel::factory()->count(2)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.hotels.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/hotels/index')
            ->has('hotels.data', 2));
});

it('filters hotels by province and status', function () {
    $blocked = Hotel::factory()->visible()->blocked()->create(['province' => Province::Cadiz]);
    Hotel::factory()->visible()->create(['province' => Province::Cadiz]);
    Hotel::factory()->blocked()->create(['province' => Province::Huelva]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.hotels.index', ['province' => 'cadiz', 'status' => 'blocked']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('hotels.data', 1)
            ->where('hotels.data.0.id', $blocked->id));
});

it('blocks a hotel with a reason', function () {
    $hotel = Hotel::factory()->visible()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.hotels.block.store', $hotel), ['reason' => 'Fotos engañosas'])
        ->assertSessionHasNoErrors();

    expect($hotel->refresh())
        ->isBlocked()->toBeTrue()
        ->blocked_reason->toBe('Fotos engañosas')
        ->isPublished()->toBeFalse();
});

it('requires a reason to block a hotel', function () {
    $hotel = Hotel::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.hotels.block.store', $hotel), [])
        ->assertSessionHasErrors('reason');
});

it('unblocks a hotel', function () {
    $hotel = Hotel::factory()->blocked()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.hotels.block.destroy', $hotel))
        ->assertSessionHasNoErrors();

    expect($hotel->refresh()->isBlocked())->toBeFalse();
});

it('does not let owners unblock their own hotel', function () {
    $hotel = Hotel::factory()->blocked()->create();

    $this->actingAs($hotel->owner)
        ->delete(route('admin.hotels.block.destroy', $hotel))
        ->assertRedirect(route('dashboard'));

    expect($hotel->refresh()->isBlocked())->toBeTrue();
});
