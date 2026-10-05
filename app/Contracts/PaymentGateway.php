<?php

namespace App\Contracts;

use App\Models\Reservation;
use App\Services\Payments\CheckoutSession;
use App\Services\Payments\InvalidWebhookSignature;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\RefundResult;
use App\Services\Payments\WebhookEvent;

/**
 * The payment provider (Stripe) as the app needs it. Tests bind a fake, so
 * they never call the real provider.
 */
interface PaymentGateway
{
    /**
     * Start a hosted payment page for the reservation's total.
     *
     * @throws PaymentGatewayException
     */
    public function createCheckoutSession(Reservation $reservation, string $successUrl, string $cancelUrl): CheckoutSession;

    /**
     * Read a payment page's current state, as the webhook would send it.
     *
     * @return array<string, mixed> The Stripe Checkout Session.
     *
     * @throws PaymentGatewayException
     */
    public function retrieveCheckoutSession(string $sessionId): array;

    /**
     * Close a payment page so it can no longer be paid.
     *
     * @throws PaymentGatewayException
     */
    public function expireCheckoutSession(string $sessionId): void;

    /**
     * Refund part or all of a payment. The idempotency key guarantees that
     * retrying the same refund never returns the money twice.
     *
     * @param  int  $amount  In euro cents, never more than what was paid.
     *
     * @throws PaymentGatewayException
     */
    public function refund(string $paymentIntentId, int $amount, string $idempotencyKey, int $reservationId): RefundResult;

    /**
     * Verify a webhook's signature and read its event.
     *
     * @throws InvalidWebhookSignature
     */
    public function parseWebhook(string $payload, string $signature): WebhookEvent;
}
