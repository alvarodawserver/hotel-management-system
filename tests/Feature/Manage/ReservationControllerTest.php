<?php

use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-05 10:00:00');
});

describe('index', function () {
    it('shows owners only the reservations of their own hotels', function () {
        $hotel = Hotel::factory()->create();
        $own = Reservation::factory()->forRoom(Room::factory()->for($hotel)->create())->create();
        Reservation::factory()->create();

        $this->actingAs($hotel->owner)
            ->get(route('manage.reservations.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('manage/reservations/index')
                ->has('reservations.data', 1)
                ->where('reservations.data.0.code', $own->code));
    });

    it('shows admins every reservation, filtered by status', function () {
        Reservation::factory()->count(2)->create();
        $cancelled = Reservation::factory()->cancelled()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('manage.reservations.index', ['status' => 'cancelled']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('reservations.data', 1)
                ->where('reservations.data.0.code', $cancelled->code));
    });

    it('finds a reservation by its code', function () {
        $wanted = Reservation::factory()->create();
        Reservation::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('manage.reservations.index', ['search' => $wanted->code]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('reservations.data', 1)
                ->where('reservations.data.0.code', $wanted->code));
    });

    it('is not available to customers', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('manage.reservations.index'))
            ->assertRedirect(route('home'));
    });
});

describe('show', function () {
    it('shows the guest’s contact details to the hotel owner', function () {
        $reservation = Reservation::factory()->create(['guest_phone' => '600 111 222']);

        $this->actingAs($reservation->hotel->owner)
            ->get(route('manage.reservations.show', $reservation))
            ->assertInertia(fn (Assert $page) => $page
                ->component('manage/reservations/show')
                ->where('reservation.guest_phone', '600 111 222')
                ->where('reservation.customer.email', $reservation->user->email));
    });

    it('does not show another owner’s reservation', function () {
        $reservation = Reservation::factory()->create();

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('manage.reservations.show', $reservation))
            ->assertRedirect(route('dashboard'))
            ->assertInertiaFlash('toast.message', 'You cannot see this reservation.');
    });
});

describe('cancel', function () {
    it('cancels with a full refund and the reason for the guest', function () {
        $reservation = Reservation::factory()->stay(1)->create(['total_price' => 30000]);

        $this->actingAs($reservation->hotel->owner)
            ->post(route('manage.reservations.cancel', $reservation), ['reason' => 'Flooded room'])
            ->assertRedirect(route('manage.reservations.show', $reservation));

        expect($reservation->refresh())
            ->status->toBe(ReservationStatus::Cancelled)
            ->cancellation_reason->toBe('Flooded room')
            ->refund_amount->toBe(30000);
    });

    it('requires a reason', function () {
        $reservation = Reservation::factory()->create();

        $this->actingAs($reservation->hotel->owner)
            ->post(route('manage.reservations.cancel', $reservation), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        expect($reservation->refresh()->status)->toBe(ReservationStatus::Confirmed);
    });

    it('does not let another owner cancel', function () {
        $reservation = Reservation::factory()->create();

        $this->actingAs(User::factory()->owner()->create())
            ->post(route('manage.reservations.cancel', $reservation), ['reason' => 'No'])
            ->assertRedirect(route('dashboard'));

        expect($reservation->refresh()->status)->toBe(ReservationStatus::Confirmed);
    });
});

describe('retry refund', function () {
    it('lets an admin retry a failed refund with the same idempotency key', function () {
        $reservation = Reservation::factory()->refundFailed()->create(['stripe_payment_intent_id' => 'pi_paid']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('manage.reservations.refund', $reservation))
            ->assertInertiaFlash('toast.type', 'success');

        expect(paymentGateway()->refunds)->toBe([[
            'payment_intent' => 'pi_paid',
            'amount' => 10000,
            'idempotency_key' => "refund-{$reservation->id}",
        ]])
            ->and($reservation->refresh()->refund_status)->toBe(RefundStatus::Succeeded);
    });

    it('refuses to retry a refund that did not fail', function () {
        $reservation = Reservation::factory()->cancelled()->create(['refund_status' => RefundStatus::Succeeded]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('manage.reservations.refund', $reservation))
            ->assertSessionHasErrors(['refund' => 'This refund does not need to be retried.']);

        expect(paymentGateway()->refunds)->toBe([]);
    });

    it('is an admin task', function () {
        $reservation = Reservation::factory()->refundFailed()->create();

        $this->actingAs($reservation->hotel->owner)
            ->post(route('manage.reservations.refund', $reservation))
            ->assertRedirect(route('dashboard'));

        expect(paymentGateway()->refunds)->toBe([]);
    });
});
