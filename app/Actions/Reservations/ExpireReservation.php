<?php

namespace App\Actions\Reservations;

use App\Contracts\PaymentGateway;
use App\Enums\ReservationStatus;
use App\Models\Reservation;

class ExpireReservation
{
    public function __construct(private PaymentGateway $gateway) {}

    /**
     * Release the room of an unpaid reservation whose payment window ran
     * out, and close its payment page so it cannot be paid late.
     */
    public function handle(Reservation $reservation, bool $closePaymentPage = true): void
    {
        if (! $reservation->isPending()) {
            return;
        }

        $reservation->forceFill(['status' => ReservationStatus::Expired])->save();

        if ($closePaymentPage && $reservation->stripe_checkout_session_id !== null) {
            rescue(fn () => $this->gateway->expireCheckoutSession($reservation->stripe_checkout_session_id), report: false);
        }
    }
}
