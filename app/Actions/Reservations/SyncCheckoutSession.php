<?php

namespace App\Actions\Reservations;

use App\Contracts\PaymentGateway;
use App\Models\Reservation;
use App\Services\Payments\PaymentGatewayException;

class SyncCheckoutSession
{
    public function __construct(
        private PaymentGateway $gateway,
        private ConfirmReservationPayment $confirmPayment,
    ) {}

    /**
     * A fallback for the "checkout.session.completed" webhook: when the
     * customer comes back from Stripe and the reservation is still pending,
     * ask Stripe for the payment page's state and confirm it if it was paid.
     * Confirming is idempotent, so it does not matter which arrives first.
     */
    public function handle(Reservation $reservation): void
    {
        if (! $reservation->isPending() || $reservation->stripe_checkout_session_id === null) {
            return;
        }

        try {
            $session = $this->gateway->retrieveCheckoutSession($reservation->stripe_checkout_session_id);
        } catch (PaymentGatewayException $exception) {
            // The webhook (or the next check) will confirm it later.
            report($exception);

            return;
        }

        $this->confirmPayment->handle($session);
        $reservation->refresh();
    }
}
