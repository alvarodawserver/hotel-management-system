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

/**
 * A published hotel with one double room at 100 € a night.
 */
function bookableRoom(): Room
{
    return Room::factory()
        ->for(Hotel::factory()->visible())
        ->create(['capacity' => 2, 'price_per_night' => 10000]);
}

/**
 * The query/payload that books that kind of room for 3 nights from 15 Oct.
 *
 * @return array<string, mixed>
 */
function stayFor(Room $room, array $overrides = []): array
{
    return [
        'hotel' => $room->hotel->slug,
        'room_type_id' => $room->room_type_id,
        'capacity' => $room->capacity,
        'price_per_night' => $room->price_per_night,
        'check_in' => '2026-10-15',
        'check_out' => '2026-10-18',
        'adults' => 2,
        'children' => 0,
        ...$overrides,
    ];
}

/**
 * @return array<string, mixed>
 */
function bookingFor(Room $room, array $overrides = []): array
{
    return stayFor($room, [
        'guest_name' => 'Lucía Pérez',
        'guest_phone' => '+34 600 123 456',
        'special_requests' => 'Late arrival',
        ...$overrides,
    ]);
}

describe('create', function () {
    it('sends guests to log in first', function () {
        $room = bookableRoom();

        $this->get(route('reservations.create', stayFor($room)))
            ->assertRedirect(route('login'));
    });

    it('does not let owners book', function () {
        $room = bookableRoom();

        $this->actingAs($room->hotel->owner)
            ->get(route('reservations.create', stayFor($room)))
            ->assertRedirect(route('dashboard'))
            ->assertInertiaFlash('toast.message', 'Bookings are made with a customer account.');
    });

    it('shows the stay and its total price to a customer', function () {
        $room = bookableRoom();

        $this->actingAs(User::factory()->create(['name' => 'Lucía Pérez']))
            ->get(route('reservations.create', stayFor($room)))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reservations/create')
                ->where('price.total', 30000)
                ->where('price.night_count', 3)
                ->where('guestName', 'Lucía Pérez'));
    });

    it('goes back to the hotel page when that kind of room is no longer free', function () {
        $room = bookableRoom();
        Reservation::factory()->forRoom($room)->stay(10)->create();

        $this->actingAs(User::factory()->create())
            ->get(route('reservations.create', stayFor($room)))
            ->assertRedirect(route('hotels.show', [
                'hotel' => $room->hotel->slug,
                'check_in' => '2026-10-15',
                'check_out' => '2026-10-18',
                'adults' => 2,
                'children' => 0,
            ]))
            ->assertInertiaFlash('toast.message', 'There are no rooms of this kind left for those dates.');
    });

    it('rejects check-in dates in the past', function () {
        $room = bookableRoom();

        $this->actingAs(User::factory()->create())
            ->get(route('reservations.create', stayFor($room, ['check_in' => '2026-10-04'])))
            ->assertSessionHasErrors('check_in');
    });

    it('rejects stays longer than 30 nights', function () {
        $room = bookableRoom();

        $this->actingAs(User::factory()->create())
            ->get(route('reservations.create', stayFor($room, ['check_out' => '2026-11-15'])))
            ->assertSessionHasErrors(['check_out' => 'A stay can last at most 30 nights.']);
    });
});

describe('store', function () {
    it('holds the room as a pending reservation and sends the customer to Stripe', function () {
        $room = bookableRoom();
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->post(route('reservations.store'), bookingFor($room))
            ->assertRedirect('https://checkout.stripe.test/cs_test_1');

        $reservation = Reservation::sole();

        expect($reservation)
            ->user_id->toBe($customer->id)
            ->room_id->toBe($room->id)
            ->status->toBe(ReservationStatus::Pending)
            ->total_price->toBe(30000)
            ->guest_phone->toBe('+34 600 123 456')
            ->stripe_checkout_session_id->toBe('cs_test_1')
            ->and($reservation->expires_at->toDateTimeString())->toBe('2026-10-05 10:30:00')
            ->and($reservation->code)->toMatch('/^RDM-[A-Z0-9]{6}$/');
        expect(paymentGateway()->checkoutSessions)->toBe([[
            'reservation_id' => $reservation->id,
            'amount' => 30000,
            'success_url' => route('reservations.show', ['reservation' => $reservation, 'checkout' => 'success']),
        ]]);
    });

    it('assigns another free room of the same kind', function () {
        $room = bookableRoom();
        $twin = Room::factory()->for($room->hotel)->create([
            'room_type_id' => $room->room_type_id,
            'capacity' => 2,
            'price_per_night' => 10000,
        ]);
        Reservation::factory()->forRoom($room)->stay(10)->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), bookingFor($room))
            ->assertRedirect();

        expect(Reservation::query()->latest('id')->first()->room_id)->toBe($twin->id);
    });

    it('refuses when the last room was just taken', function () {
        $room = bookableRoom();
        Reservation::factory()->forRoom($room)->pending()->stay(11, 1)->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), bookingFor($room))
            ->assertSessionHasErrors(['room' => 'There are no rooms of this kind left for those dates.']);

        expect(Reservation::count())->toBe(1);
    });

    it('refuses bookings for a hidden hotel', function () {
        $room = Room::factory()->for(Hotel::factory())->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), bookingFor($room))
            ->assertSessionHasErrors(['room' => 'This hotel is not accepting bookings at the moment.']);

        expect(Reservation::count())->toBe(0);
    });

    it('requires the guest name and a valid phone', function () {
        $room = bookableRoom();

        $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), bookingFor($room, ['guest_name' => '', 'guest_phone' => 'call me']))
            ->assertSessionHasErrors(['guest_name', 'guest_phone']);
    });

    it('keeps the room held when Stripe is down, so the customer can retry', function () {
        $room = bookableRoom();
        paymentGateway()->failCheckout = true;

        $response = $this->actingAs(User::factory()->create())
            ->post(route('reservations.store'), bookingFor($room));

        $reservation = Reservation::sole();
        $response->assertRedirect(route('reservations.show', $reservation))
            ->assertInertiaFlash('toast.message', 'The payment service is not available right now. Try again in a moment.');
        expect($reservation->status)->toBe(ReservationStatus::Pending);
    });
});

describe('show', function () {
    it('shows the customer their reservation and what cancelling would refund', function () {
        $reservation = Reservation::factory()->stay(4)->create();

        $this->actingAs($reservation->user)
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reservations/show')
                ->where('reservation.code', $reservation->code)
                ->where('refundQuote', ['percent' => 50, 'amount' => (int) round($reservation->total_price / 2), 'days_before' => 4]));
    });

    it('confirms a paid reservation on return from Stripe without waiting for the webhook', function () {
        $reservation = Reservation::factory()->pending()->create(['stripe_checkout_session_id' => 'cs_paid']);
        paymentGateway()->sessions['cs_paid'] = [
            'id' => 'cs_paid',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_paid',
        ];

        $this->actingAs($reservation->user)
            ->get(route('reservations.show', ['reservation' => $reservation, 'checkout' => 'success']))
            ->assertInertia(fn (Assert $page) => $page->where('reservation.status', 'confirmed'));

        expect($reservation->refresh())
            ->status->toBe(ReservationStatus::Confirmed)
            ->stripe_payment_intent_id->toBe('pi_paid');
    });

    it('keeps the reservation pending while Stripe says the page is unpaid', function () {
        $reservation = Reservation::factory()->pending()->create(['stripe_checkout_session_id' => 'cs_open']);

        $this->actingAs($reservation->user)
            ->get(route('reservations.show', ['reservation' => $reservation, 'checkout' => 'success']))
            ->assertInertia(fn (Assert $page) => $page->where('reservation.status', 'pending'));

        expect(paymentGateway()->retrievedSessions)->toBe(['cs_open']);
    });

    it('still shows the page when Stripe cannot be asked', function () {
        $reservation = Reservation::factory()->pending()->create();
        paymentGateway()->failSessionLookup = true;

        $this->actingAs($reservation->user)
            ->get(route('reservations.show', ['reservation' => $reservation, 'checkout' => 'success']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('reservation.status', 'pending'));
    });

    it('only asks Stripe when the customer comes back from paying', function () {
        $reservation = Reservation::factory()->pending()->create();

        $this->actingAs($reservation->user)
            ->get(route('reservations.show', $reservation))
            ->assertOk();

        expect(paymentGateway()->retrievedSessions)->toBe([]);
    });

    it('does not show a reservation to another customer', function () {
        $reservation = Reservation::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('reservations.show', $reservation))
            ->assertRedirect(route('home'))
            ->assertInertiaFlash('toast.message', 'You cannot see this reservation.');
    });
});

describe('index', function () {
    it('splits the customer reservations into upcoming, past and cancelled', function () {
        $customer = User::factory()->create();
        $upcoming = Reservation::factory()->for($customer)->stay(10)->create();
        $past = Reservation::factory()->for($customer)->stay(-10)->create();
        $cancelled = Reservation::factory()->for($customer)->cancelled()->create();
        $expired = Reservation::factory()->for($customer)->expired()->create();
        Reservation::factory()->stay(10)->create();

        $codes = fn (string $tab) => collect(
            $this->actingAs($customer)->get(route('reservations.index', ['tab' => $tab]))
                ->viewData('page')['props']['reservations']['data'],
        )->pluck('code')->sort()->values()->all();

        expect($codes('upcoming'))->toBe([$upcoming->code])
            ->and($codes('past'))->toBe([$past->code])
            ->and($codes('cancelled'))->toBe(collect([$cancelled->code, $expired->code])->sort()->values()->all());
    });
});

describe('pay', function () {
    it('closes the previous payment page and opens a new one', function () {
        $reservation = Reservation::factory()->pending()->create(['stripe_checkout_session_id' => 'cs_old']);

        $this->actingAs($reservation->user)
            ->post(route('reservations.pay', $reservation))
            ->assertRedirect('https://checkout.stripe.test/cs_test_1');

        expect(paymentGateway()->expiredSessions)->toBe(['cs_old'])
            ->and($reservation->refresh()->stripe_checkout_session_id)->toBe('cs_test_1');
    });

    it('refuses to pay an expired reservation', function () {
        $reservation = Reservation::factory()->expired()->create();

        $this->actingAs($reservation->user)
            ->post(route('reservations.pay', $reservation))
            ->assertRedirect(route('reservations.show', $reservation))
            ->assertInertiaFlash('toast.message', 'This reservation can no longer be paid.');

        expect(paymentGateway()->checkoutSessions)->toBe([]);
    });
});

describe('cancel', function () {
    it('cancels the reservation and refunds what the policy allows', function () {
        $reservation = Reservation::factory()->stay(4)->create(['total_price' => 30000]);

        $this->actingAs($reservation->user)
            ->post(route('reservations.cancel', $reservation))
            ->assertRedirect(route('reservations.show', $reservation))
            ->assertInertiaFlash('toast.type', 'success');

        expect($reservation->refresh())
            ->status->toBe(ReservationStatus::Cancelled)
            ->refund_amount->toBe(15000)
            ->refund_status->toBe(RefundStatus::Succeeded);
    });

    it('does not let a customer cancel someone else’s reservation', function () {
        $reservation = Reservation::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reservations.cancel', $reservation))
            ->assertRedirect(route('home'));

        expect($reservation->refresh()->status)->toBe(ReservationStatus::Confirmed);
    });
});
