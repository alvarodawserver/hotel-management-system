<?php

use App\Models\Reservation;
use App\Notifications\NewReservationForHotel;
use App\Notifications\ReservationCancelled;
use App\Notifications\ReservationCancelledByGuest;
use App\Notifications\ReservationConfirmed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

beforeEach(function () {
    $this->travelTo('2026-10-05 10:00:00');
});

/**
 * The email's HTML as the notifiable receives it, in the given language.
 */
function renderEmail(Notification $notification, object $notifiable, string $locale): string
{
    $previousLocale = App::getLocale();
    App::setLocale($locale);

    try {
        return (string) $notification->toMail($notifiable)->render();
    } finally {
        App::setLocale($previousLocale);
    }
}

/**
 * A paid 3-night stay from 15 October at 136,44 €.
 */
function paidReservation(array $attributes = []): Reservation
{
    return Reservation::factory()->stay(10)->create([
        'total_price' => 13644,
        'guest_name' => 'Lucía Pérez',
        ...$attributes,
    ]);
}

it('confirms the booking to the customer in Spanish', function () {
    $reservation = paidReservation();

    $html = renderEmail(new ReservationConfirmed($reservation), $reservation->user, 'es');

    expect($html)
        ->toContain('¡Tu reserva está confirmada!')
        ->toContain($reservation->code)
        ->toContain('136,44')
        ->toContain('jue., 15 oct. 2026')
        ->toContain('Cancelación gratuita hasta 7 días antes de la llegada.')
        ->toContain(route('reservations.show', $reservation));
});

it('confirms the booking to the customer in English', function () {
    $reservation = paidReservation();

    $html = renderEmail(new ReservationConfirmed($reservation), $reservation->user, 'en');

    expect($html)
        ->toContain('Your booking is confirmed!')
        ->toContain('€136.44')
        ->toContain('Thu, 15 Oct 2026');
});

it('tells the customer how much a cancellation refunds', function (array $attributes, int $percent, string $expected) {
    $reservation = paidReservation(['status' => 'cancelled', ...$attributes]);
    $reservation->forceFill(['cancelled_by' => $reservation->user_id])->save();

    $html = renderEmail(new ReservationCancelled($reservation, $percent), $reservation->user, 'en');

    expect($html)->toContain($expected);
})->with([
    'partial refund' => [['refund_amount' => 6822], 50, 'We are refunding €68.22 (50 %) to the card you paid with.'],
    'no refund' => [['refund_amount' => 0], 0, 'This cancellation was not refunded'],
    'never paid' => [['refund_amount' => 0, 'paid_at' => null, 'stripe_payment_intent_id' => null], 0, 'Nothing was charged for this reservation.'],
]);

it('gives the customer the hotel’s reason when the hotel cancels', function () {
    $reservation = paidReservation([
        'status' => 'cancelled',
        'refund_amount' => 13644,
        'cancellation_reason' => 'Cerrado por obras',
    ]);
    $reservation->forceFill(['cancelled_by' => $reservation->hotel->owner_id])->save();

    $html = renderEmail(new ReservationCancelled($reservation, 100), $reservation->user, 'es');

    expect($html)
        ->toContain('lo sentimos')
        ->toContain('Cerrado por obras')
        ->toContain('136,44');
});

it('translates the reason of a late payment refund', function () {
    $reservation = paidReservation([
        'status' => 'cancelled',
        'refund_amount' => 13644,
        'cancellation_reason' => 'Payment received after the booking expired; the room was no longer available.',
    ]);

    $html = renderEmail(new ReservationCancelled($reservation, 100), $reservation->user, 'es');

    expect($html)->toContain('El pago llegó después de que la reserva caducara');
});

it('gives the hotel owner the guest’s contact details and requests', function () {
    $reservation = paidReservation(['guest_phone' => '600 111 222', 'special_requests' => 'Cuna para el bebé']);

    $html = renderEmail(new NewReservationForHotel($reservation), $reservation->hotel->owner, 'es');

    expect($html)
        ->toContain('Nueva reserva en '.e($reservation->hotel->name))
        ->toContain('600 111 222')
        ->toContain('Cuna para el bebé')
        ->toContain('N.º '.$reservation->room->name)
        ->toContain(route('manage.reservations.show', $reservation));
});

it('tells the hotel owner that a guest cancelled and the room is free', function () {
    $reservation = paidReservation(['status' => 'cancelled', 'refund_amount' => 6822]);

    $html = renderEmail(new ReservationCancelledByGuest($reservation), $reservation->hotel->owner, 'en');

    expect($html)
        ->toContain('Lucía Pérez has cancelled reservation '.$reservation->code)
        ->toContain('€68.22');
});
