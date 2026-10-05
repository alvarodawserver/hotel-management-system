<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Reservation;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class StripePaymentGateway implements PaymentGateway
{
    public function __construct(
        private StripeClient $stripe,
        private string $webhookSecret,
    ) {}

    public function createCheckoutSession(Reservation $reservation, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $reservation->loadMissing(['hotel', 'room.roomType', 'user']);

        try {
            $session = $this->stripe->checkout->sessions->create([
                'mode' => 'payment',
                'customer_email' => $reservation->user->email,
                'locale' => $reservation->user->preferredLocale() ?? app()->getLocale(),
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => $reservation->total_price,
                        'product_data' => [
                            'name' => "{$reservation->hotel->name} · {$reservation->room->roomType->translation()}",
                            'description' => trans_choice(':count night|:count nights', $reservation->nights())
                                .", {$reservation->check_in->toDateString()} → {$reservation->check_out->toDateString()}",
                        ],
                    ],
                ]],
                // The reservation id travels with the payment, so the webhook
                // can always find the reservation and refunds can be traced.
                'client_reference_id' => (string) $reservation->id,
                'metadata' => ['reservation_id' => (string) $reservation->id, 'code' => $reservation->code],
                'payment_intent_data' => [
                    'metadata' => ['reservation_id' => (string) $reservation->id, 'code' => $reservation->code],
                ],
                'expires_at' => $reservation->expires_at?->getTimestamp(),
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);
        } catch (ApiErrorException $exception) {
            throw new PaymentGatewayException($exception->getMessage(), previous: $exception);
        }

        return new CheckoutSession($session->id, (string) $session->url);
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        try {
            /** @var array<string, mixed> $session */
            $session = $this->stripe->checkout->sessions->retrieve($sessionId)->toArray();
        } catch (ApiErrorException $exception) {
            throw new PaymentGatewayException($exception->getMessage(), previous: $exception);
        }

        return $session;
    }

    public function expireCheckoutSession(string $sessionId): void
    {
        try {
            $this->stripe->checkout->sessions->expire($sessionId);
        } catch (ApiErrorException $exception) {
            throw new PaymentGatewayException($exception->getMessage(), previous: $exception);
        }
    }

    public function refund(string $paymentIntentId, int $amount, string $idempotencyKey, int $reservationId): RefundResult
    {
        try {
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $paymentIntentId,
                'amount' => $amount,
                'metadata' => ['reservation_id' => (string) $reservationId],
            ], ['idempotency_key' => $idempotencyKey]);
        } catch (ApiErrorException $exception) {
            throw new PaymentGatewayException($exception->getMessage(), previous: $exception);
        }

        return new RefundResult($refund->id, RefundResult::statusFrom((string) $refund->status));
    }

    public function parseWebhook(string $payload, string $signature): WebhookEvent
    {
        try {
            $event = Webhook::constructEvent($payload, $signature, $this->webhookSecret);
        } catch (SignatureVerificationException|UnexpectedValueException $exception) {
            throw new InvalidWebhookSignature($exception->getMessage(), previous: $exception);
        }

        /** @var array<string, mixed> $object */
        $object = $event->data->object->toArray();

        return new WebhookEvent($event->type, $object);
    }
}
