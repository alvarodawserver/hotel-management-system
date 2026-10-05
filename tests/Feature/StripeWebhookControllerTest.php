<?php

use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Notifications\NewReservationForHotel;
use App\Notifications\ReservationCancelled;
use App\Notifications\ReservationConfirmed;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\Fakes\FakePaymentGateway;

beforeEach(function () {
    $this->travelTo('2026-10-05 10:00:00');
});

/**
 * Post a Stripe event signed with the test webhook secret.
 *
 * @param  array<string, mixed>  $object
 */
function sendStripeEvent(string $type, array $object, ?string $signature = null): TestResponse
{
    $payload = json_encode([
        'id' => 'evt_test',
        'object' => 'event',
        'type' => $type,
        'data' => ['object' => $object],
    ], JSON_THROW_ON_ERROR);

    return test()->call(
        'POST',
        route('stripe.webhook'),
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature ?? FakePaymentGateway::sign($payload),
        ],
        content: $payload,
    );
}

/**
 * @return array<string, mixed>
 */
function paidSession(Reservation $reservation): array
{
    return [
        'id' => $reservation->stripe_checkout_session_id,
        'object' => 'checkout.session',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_from_webhook',
        'metadata' => ['reservation_id' => (string) $reservation->id],
    ];
}

it('rejects events without a valid Stripe signature', function () {
    $reservation = Reservation::factory()->pending()->create();

    sendStripeEvent('checkout.session.completed', paidSession($reservation), 't=1,v1=forged')
        ->assertStatus(400);

    expect($reservation->refresh()->status)->toBe(ReservationStatus::Pending);
});

it('confirms the reservation and keeps the payment to refund it later', function () {
    $reservation = Reservation::factory()->pending()->create();

    sendStripeEvent('checkout.session.completed', paidSession($reservation))->assertOk();

    expect($reservation->refresh())
        ->status->toBe(ReservationStatus::Confirmed)
        ->stripe_payment_intent_id->toBe('pi_from_webhook')
        ->paid_at->not->toBeNull();
});

it('ignores a completed page whose payment has not gone through yet', function () {
    $reservation = Reservation::factory()->pending()->create();

    sendStripeEvent('checkout.session.completed', [...paidSession($reservation), 'payment_status' => 'unpaid'])
        ->assertOk();

    expect($reservation->refresh()->status)->toBe(ReservationStatus::Pending);
});

it('confirms a late payment when the room is still free', function () {
    $reservation = Reservation::factory()->expired()->create();

    sendStripeEvent('checkout.session.completed', paidSession($reservation))->assertOk();

    expect($reservation->refresh()->status)->toBe(ReservationStatus::Confirmed);
});

it('refunds a late payment in full when someone else took the room', function () {
    $reservation = Reservation::factory()->expired()->create(['total_price' => 30000]);
    Reservation::factory()->forRoom($reservation->room)->create([
        'check_in' => $reservation->check_in,
        'check_out' => $reservation->check_out,
    ]);

    sendStripeEvent('checkout.session.completed', paidSession($reservation))->assertOk();

    expect($reservation->refresh())
        ->status->toBe(ReservationStatus::Cancelled)
        ->refund_amount->toBe(30000)
        ->refund_status->toBe(RefundStatus::Succeeded)
        ->and(paymentGateway()->refunds)->toHaveCount(1);
});

it('handles a repeated event only once', function () {
    $reservation = Reservation::factory()->expired()->create();
    Reservation::factory()->forRoom($reservation->room)->create([
        'check_in' => $reservation->check_in,
        'check_out' => $reservation->check_out,
    ]);

    sendStripeEvent('checkout.session.completed', paidSession($reservation))->assertOk();
    sendStripeEvent('checkout.session.completed', paidSession($reservation))->assertOk();

    expect(paymentGateway()->refunds)->toHaveCount(1);
});

it('expires the reservation when its payment page expires', function () {
    $reservation = Reservation::factory()->pending()->create();

    sendStripeEvent('checkout.session.expired', ['id' => $reservation->stripe_checkout_session_id, 'object' => 'checkout.session'])
        ->assertOk();

    expect($reservation->refresh()->status)->toBe(ReservationStatus::Expired);
});

it('ignores the expiry of a payment page replaced by a retry', function () {
    $reservation = Reservation::factory()->pending()->create(['stripe_checkout_session_id' => 'cs_current']);

    sendStripeEvent('checkout.session.expired', ['id' => 'cs_previous', 'object' => 'checkout.session'])
        ->assertOk();

    expect($reservation->refresh()->status)->toBe(ReservationStatus::Pending);
});

it('records the final state of a refund', function () {
    $reservation = Reservation::factory()->cancelled()->create([
        'refund_amount' => 15000,
        'refund_status' => RefundStatus::Pending,
        'stripe_refund_id' => 're_123',
    ]);

    sendStripeEvent('refund.updated', ['id' => 're_123', 'object' => 'refund', 'status' => 'succeeded'])
        ->assertOk();

    expect($reservation->refresh())
        ->refund_status->toBe(RefundStatus::Succeeded)
        ->refunded_at->not->toBeNull();
});

it('emails the customer and the hotel owner once when the payment is confirmed', function () {
    $reservation = Reservation::factory()->pending()->create();
    Notification::fake();

    sendStripeEvent('checkout.session.completed', paidSession($reservation))->assertOk();
    sendStripeEvent('checkout.session.completed', paidSession($reservation))->assertOk();

    Notification::assertSentToTimes($reservation->user, ReservationConfirmed::class, 1);
    Notification::assertSentToTimes($reservation->hotel->owner, NewReservationForHotel::class, 1);
});

it('emails the customer a full refund when a late payment finds the room taken', function () {
    $reservation = Reservation::factory()->expired()->create();
    Reservation::factory()->forRoom($reservation->room)->create([
        'check_in' => $reservation->check_in,
        'check_out' => $reservation->check_out,
    ]);
    Notification::fake();

    sendStripeEvent('checkout.session.completed', paidSession($reservation))->assertOk();

    Notification::assertSentTo($reservation->user, ReservationCancelled::class, fn (ReservationCancelled $notification) => $notification->refundPercent === 100);
    Notification::assertNotSentTo($reservation->hotel->owner, NewReservationForHotel::class);
});
