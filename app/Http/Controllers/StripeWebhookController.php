<?php

namespace App\Http\Controllers;

use App\Actions\Reservations\ConfirmReservationPayment;
use App\Actions\Reservations\ExpireReservation;
use App\Contracts\PaymentGateway;
use App\Enums\RefundStatus;
use App\Models\Reservation;
use App\Services\Payments\InvalidWebhookSignature;
use App\Services\Payments\RefundResult;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController extends Controller
{
    /**
     * Receive Stripe events. Only signed requests from Stripe are accepted;
     * every handler is safe to run more than once, as Stripe retries.
     */
    public function __invoke(
        Request $request,
        PaymentGateway $gateway,
        ConfirmReservationPayment $confirmPayment,
        ExpireReservation $expireReservation,
    ): Response {
        try {
            $event = $gateway->parseWebhook($request->getContent(), (string) $request->header('Stripe-Signature'));
        } catch (InvalidWebhookSignature) {
            return response('Invalid signature.', 400);
        }

        match ($event->type) {
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded' => $confirmPayment->handle($event->object),
            'checkout.session.expired' => $this->expire($event->object, $expireReservation),
            'refund.updated' => $this->syncRefund($event->object),
            default => null,
        };

        return response('', 200);
    }

    /**
     * Only the reservation's current payment page counts: an older page
     * closed when the customer retried the payment must not expire it.
     *
     * @param  array<string, mixed>  $session
     */
    private function expire(array $session, ExpireReservation $expireReservation): void
    {
        $reservation = Reservation::query()->where('stripe_checkout_session_id', $session['id'] ?? null)->first();

        if ($reservation !== null) {
            $expireReservation->handle($reservation, closePaymentPage: false);
        }
    }

    /**
     * @param  array<string, mixed>  $refund
     */
    private function syncRefund(array $refund): void
    {
        $reservation = Reservation::query()->where('stripe_refund_id', $refund['id'] ?? null)->first()
            ?? Reservation::query()->whereKey($refund['metadata']['reservation_id'] ?? null)->first();

        if ($reservation === null) {
            return;
        }

        $status = RefundResult::statusFrom((string) ($refund['status'] ?? ''));

        $reservation->forceFill([
            'stripe_refund_id' => $refund['id'] ?? $reservation->stripe_refund_id,
            'refund_status' => $status,
            'refunded_at' => $status === RefundStatus::Succeeded ? ($reservation->refunded_at ?? now()) : null,
        ])->save();
    }
}
