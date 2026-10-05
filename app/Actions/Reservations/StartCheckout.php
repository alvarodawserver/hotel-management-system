<?php

namespace App\Actions\Reservations;

use App\Contracts\PaymentGateway;
use App\Models\Reservation;
use App\Services\Payments\PaymentGatewayException;
use Illuminate\Validation\ValidationException;

class StartCheckout
{
    public function __construct(private PaymentGateway $gateway) {}

    /**
     * Open a Stripe payment page for a pending reservation and return its
     * URL. Used right after booking and by "Complete payment".
     *
     * @throws ValidationException
     */
    public function handle(Reservation $reservation): string
    {
        if (! $reservation->awaitsPayment()) {
            throw ValidationException::withMessages([
                'reservation' => __('This reservation can no longer be paid.'),
            ]);
        }

        // Close the previous payment page, if any, so the same stay can never
        // be paid twice. It may already be closed; that is fine.
        if ($reservation->stripe_checkout_session_id !== null) {
            rescue(fn () => $this->gateway->expireCheckoutSession($reservation->stripe_checkout_session_id), report: false);
        }

        // Stripe keeps a payment page open for at least 30 minutes, so the
        // room is held for a fresh payment window.
        $reservation->forceFill([
            'expires_at' => now()->addMinutes(Reservation::PAYMENT_WINDOW_MINUTES),
        ])->save();

        try {
            $session = $this->gateway->createCheckoutSession(
                $reservation,
                successUrl: route('reservations.show', ['reservation' => $reservation, 'checkout' => 'success']),
                cancelUrl: route('reservations.show', $reservation),
            );
        } catch (PaymentGatewayException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'reservation' => __('The payment service is not available right now. Try again in a moment.'),
            ]);
        }

        $reservation->forceFill(['stripe_checkout_session_id' => $session->id])->save();

        return $session->url;
    }
}
