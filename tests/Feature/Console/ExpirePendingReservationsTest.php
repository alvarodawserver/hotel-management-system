<?php

use App\Enums\ReservationStatus;
use App\Models\Reservation;

it('expires pending reservations whose payment window ran out and closes their payment page', function () {
    $this->travelTo('2026-10-05 10:00:00');
    $overdue = Reservation::factory()->pending()->create([
        'expires_at' => now()->subMinute(),
        'stripe_checkout_session_id' => 'cs_overdue',
    ]);
    $stillPaying = Reservation::factory()->pending()->create(['expires_at' => now()->addMinutes(10)]);
    $confirmed = Reservation::factory()->create(['expires_at' => now()->subDay()]);

    $this->artisan('reservations:expire')->assertSuccessful();

    expect($overdue->refresh()->status)->toBe(ReservationStatus::Expired)
        ->and($stillPaying->refresh()->status)->toBe(ReservationStatus::Pending)
        ->and($confirmed->refresh()->status)->toBe(ReservationStatus::Confirmed)
        ->and(paymentGateway()->expiredSessions)->toBe(['cs_overdue']);
});
