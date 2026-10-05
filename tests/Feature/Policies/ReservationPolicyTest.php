<?php

use App\Models\Reservation;
use App\Models\User;

/**
 * Resolve the acting user by their relationship to the reservation.
 */
function reservationActor(Reservation $reservation, string $actor): User
{
    return match ($actor) {
        'its customer' => $reservation->user,
        'the hotel owner' => $reservation->hotel->owner,
        'admin' => User::factory()->admin()->create(),
        'another customer' => User::factory()->create(),
        'another owner' => User::factory()->owner()->create(),
    };
}

test('only customers can book', function (string $role, bool $allowed) {
    $user = match ($role) {
        'customer' => User::factory()->create(),
        'owner' => User::factory()->owner()->create(),
        'admin' => User::factory()->admin()->create(),
    };

    expect($user->can('create', Reservation::class))->toBe($allowed);
})->with([
    ['customer', true],
    ['owner', false],
    ['admin', false],
]);

test('the customer, the hotel owner and admins can see and cancel a reservation', function (string $actor, bool $allowed) {
    $reservation = Reservation::factory()->create();
    $user = reservationActor($reservation, $actor);

    expect($user->can('view', $reservation))->toBe($allowed)
        ->and($user->can('cancel', $reservation))->toBe($allowed);
})->with([
    ['its customer', true],
    ['the hotel owner', true],
    ['admin', true],
    ['another customer', false],
    ['another owner', false],
]);

test('only admins can retry a refund', function (string $actor, bool $allowed) {
    $reservation = Reservation::factory()->refundFailed()->create();

    expect(reservationActor($reservation, $actor)->can('retryRefund', $reservation))->toBe($allowed);
})->with([
    ['admin', true],
    ['the hotel owner', false],
    ['its customer', false],
]);
