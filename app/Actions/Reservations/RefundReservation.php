<?php

namespace App\Actions\Reservations;

use App\Contracts\PaymentGateway;
use App\Enums\RefundStatus;
use App\Models\Reservation;
use App\Services\Payments\PaymentGatewayException;

class RefundReservation
{
    public function __construct(private PaymentGateway $gateway) {}

    /**
     * Send the reservation's refund_amount back to the customer's card.
     *
     * The refund is made on the payment (PaymentIntent), with an idempotency
     * key per reservation so retrying can never refund twice. If Stripe
     * cannot be reached the refund is marked as failed for an admin to retry.
     */
    public function handle(Reservation $reservation): void
    {
        if ($reservation->refund_amount <= 0 || $reservation->stripe_payment_intent_id === null) {
            return;
        }

        try {
            $result = $this->gateway->refund(
                $reservation->stripe_payment_intent_id,
                $reservation->refund_amount,
                "refund-{$reservation->id}",
                $reservation->id,
            );
        } catch (PaymentGatewayException $exception) {
            report($exception);
            $reservation->forceFill(['refund_status' => RefundStatus::Failed])->save();

            return;
        }

        $reservation->forceFill([
            'stripe_refund_id' => $result->id,
            'refund_status' => $result->status,
            'refunded_at' => $result->status === RefundStatus::Succeeded ? now() : null,
        ])->save();
    }
}
