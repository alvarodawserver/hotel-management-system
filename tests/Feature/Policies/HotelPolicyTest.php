<?php

use App\Models\Hotel;
use App\Models\User;

/**
 * Resolve the acting user for a hotel by relationship to it.
 */
function actorFor(Hotel $hotel, string $actor): User
{
    return match ($actor) {
        'its owner' => $hotel->owner,
        'admin' => User::factory()->admin()->create(),
        'another owner' => User::factory()->owner()->create(),
        'customer' => User::factory()->create(),
    };
}

test('only owners can create hotels', function (string $actor, bool $allowed) {
    $user = actorFor(Hotel::factory()->create(), $actor);

    expect($user->can('create', Hotel::class))->toBe($allowed);
})->with([
    ['another owner', true],
    ['admin', false],
    ['customer', false],
]);

test('the hotel owner and admins can manage a hotel', function (string $actor, bool $allowed) {
    $hotel = Hotel::factory()->create();
    $user = actorFor($hotel, $actor);

    expect($user->can('update', $hotel))->toBe($allowed)
        ->and($user->can('delete', $hotel))->toBe($allowed);
})->with([
    ['its owner', true],
    ['admin', true],
    ['another owner', false],
    ['customer', false],
]);

test('only admins can block hotels', function (string $actor, bool $allowed) {
    $hotel = Hotel::factory()->create();

    expect(actorFor($hotel, $actor)->can('block', $hotel))->toBe($allowed);
})->with([
    ['admin', true],
    ['its owner', false],
]);
