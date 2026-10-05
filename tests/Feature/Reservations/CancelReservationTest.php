<?php

use App\Actions\Reservations\CancelReservation;
use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ReservationCancelled;
use App\Notifications\ReservationCancelledByGuest;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->travelTo('2026-10-05 10:00:00');
});

/**
 * A paid 300 € stay at a hotel with the default policy (7 days → 100 %,
 * 3 days → 50 %), starting the given number of days from today.
 */
function paidStay(int $daysFromToday): Reservation
{
    return Reservation::factory()
        ->forRoom(Room::factory()->for(Hotel::factory()->visible())->create())
        ->stay($daysFromToday)
        ->create(['total_price' => 30000, 'stripe_payment_intent_id' => 'pi_paid']);
}

it('refunds the customer according to the hotel’s tiers', function (int $daysBefore, int $percent, int $amount) {
    $reservation = paidStay($daysBefore);

    $quote = app(CancelReservation::class)->handle($reservation, $reservation->user);

    expect($quote->percent)->toBe($percent)
        ->and($reservation->refresh())
        ->status->toBe(ReservationStatus::Cancelled)
        ->refund_amount->toBe($amount);
})->with([
    'well in advance' => [10, 100, 30000],
    'exactly on the first tier' => [7, 100, 30000],
    'inside the 50 % tier' => [4, 50, 15000],
    'after the last tier' => [1, 0, 0],
    'on the check-in day' => [0, 0, 0],
]);

it('refunds in full when the hotel owner or an admin cancels', function (string $canceller) {
    $reservation = paidStay(1);
    $user = $canceller === 'owner' ? $reservation->hotel->owner : User::factory()->admin()->create();

    app(CancelReservation::class)->handle($reservation, $user, 'Pool closed for repairs');

    expect($reservation->refresh())
        ->refund_amount->toBe(30000)
        ->cancelled_by->toBe($user->id)
        ->cancellation_reason->toBe('Pool closed for repairs');
})->with(['owner', 'admin']);

it('refunds the payment with an idempotency key per reservation', function () {
    $reservation = paidStay(10);

    app(CancelReservation::class)->handle($reservation, $reservation->user);

    expect(paymentGateway()->refunds)->toBe([[
        'payment_intent' => 'pi_paid',
        'amount' => 30000,
        'idempotency_key' => "refund-{$reservation->id}",
    ]])
        ->and($reservation->refresh())
        ->stripe_refund_id->toBe('re_test_1')
        ->refund_status->toBe(RefundStatus::Succeeded)
        ->refunded_at->not->toBeNull();
});

it('leaves a refund Stripe reports as pending to the refund webhook', function () {
    $reservation = paidStay(10);
    paymentGateway()->refundStatus = RefundStatus::Pending;

    app(CancelReservation::class)->handle($reservation, $reservation->user);

    expect($reservation->refresh())
        ->refund_status->toBe(RefundStatus::Pending)
        ->refunded_at->toBeNull();
});

it('does not call Stripe when nothing is refunded', function () {
    $reservation = paidStay(1);

    app(CancelReservation::class)->handle($reservation, $reservation->user);

    expect(paymentGateway()->refunds)->toBe([])
        ->and($reservation->refresh()->refund_status)->toBeNull();
});

it('keeps the cancellation and marks the refund as failed when Stripe is down', function () {
    $reservation = paidStay(10);
    paymentGateway()->failRefunds = true;

    app(CancelReservation::class)->handle($reservation, $reservation->user);

    expect($reservation->refresh())
        ->status->toBe(ReservationStatus::Cancelled)
        ->refund_status->toBe(RefundStatus::Failed);
});

it('cancels an unpaid reservation without a refund and closes its payment page', function () {
    $reservation = Reservation::factory()->pending()->create(['stripe_checkout_session_id' => 'cs_open']);

    $quote = app(CancelReservation::class)->handle($reservation, $reservation->user);

    expect($quote->amount)->toBe(0)
        ->and(paymentGateway()->expiredSessions)->toBe(['cs_open'])
        ->and(paymentGateway()->refunds)->toBe([])
        ->and($reservation->refresh()->status)->toBe(ReservationStatus::Cancelled);
});

it('refuses to cancel once the check-in day has passed', function () {
    $reservation = paidStay(-1);

    expect(fn () => app(CancelReservation::class)->handle($reservation, $reservation->user))
        ->toThrow(ValidationException::class, __('This reservation can no longer be cancelled.'));

    expect($reservation->refresh()->status)->toBe(ReservationStatus::Confirmed);
});

it('frees the room for new bookings at once', function () {
    $reservation = paidStay(10);

    app(CancelReservation::class)->handle($reservation, $reservation->user);

    expect(Room::query()->availableBetween($reservation->check_in, $reservation->check_out)->whereKey($reservation->room_id)->exists())
        ->toBeTrue();
});

it('emails the customer and the hotel owner when the customer cancels', function () {
    $reservation = paidStay(4);
    Notification::fake();

    app(CancelReservation::class)->handle($reservation, $reservation->user);

    Notification::assertSentTo($reservation->user, ReservationCancelled::class, fn (ReservationCancelled $notification) => $notification->refundPercent === 50);
    Notification::assertSentTo($reservation->hotel->owner, ReservationCancelledByGuest::class);
});

it('emails only the customer when the hotel cancels', function () {
    $reservation = paidStay(4);
    Notification::fake();

    app(CancelReservation::class)->handle($reservation, $reservation->hotel->owner, 'Flooded room');

    Notification::assertSentTo($reservation->user, ReservationCancelled::class, fn (ReservationCancelled $notification) => $notification->refundPercent === 100);
    Notification::assertNotSentTo($reservation->hotel->owner, ReservationCancelledByGuest::class);
});
