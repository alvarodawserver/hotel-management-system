<?php

namespace Tests\Fakes;

use App\Contracts\PaymentGateway;
use App\Enums\RefundStatus;
use App\Models\Reservation;
use App\Services\Payments\CheckoutSession;
use App\Services\Payments\InvalidWebhookSignature;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\RefundResult;
use App\Services\Payments\WebhookEvent;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * Records what the app asks of Stripe instead of calling it. Webhooks are
 * still verified with Stripe's real signature check, using a test secret.
 */
class FakePaymentGateway implements PaymentGateway
{
    public const WEBHOOK_SECRET = 'whsec_test_secret';

    /** @var list<array{reservation_id: int, amount: int, success_url: string}> */
    public array $checkoutSessions = [];

    /** @var list<string> */
    public array $expiredSessions = [];

    /**
     * Sessions returned by retrieveCheckoutSession(), by id; unknown ids
     * are reported as unpaid.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $sessions = [];

    /** @var list<string> */
    public array $retrievedSessions = [];

    /** @var list<array{payment_intent: string, amount: int, idempotency_key: string}> */
    public array $refunds = [];

    public bool $failRefunds = false;

    public bool $failCheckout = false;

    public bool $failSessionLookup = false;

    public RefundStatus $refundStatus = RefundStatus::Succeeded;

    public function createCheckoutSession(Reservation $reservation, string $successUrl, string $cancelUrl): CheckoutSession
    {
        if ($this->failCheckout) {
            throw new PaymentGatewayException('Stripe is down.');
        }

        $this->checkoutSessions[] = [
            'reservation_id' => $reservation->id,
            'amount' => $reservation->total_price,
            'success_url' => $successUrl,
        ];

        $id = 'cs_test_'.count($this->checkoutSessions);

        return new CheckoutSession($id, "https://checkout.stripe.test/{$id}");
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        if ($this->failSessionLookup) {
            throw new PaymentGatewayException('Stripe is down.');
        }

        $this->retrievedSessions[] = $sessionId;

        return $this->sessions[$sessionId] ?? ['id' => $sessionId, 'payment_status' => 'unpaid'];
    }

    public function expireCheckoutSession(string $sessionId): void
    {
        $this->expiredSessions[] = $sessionId;
    }

    public function refund(string $paymentIntentId, int $amount, string $idempotencyKey, int $reservationId): RefundResult
    {
        if ($this->failRefunds) {
            throw new PaymentGatewayException('Stripe is down.');
        }

        $this->refunds[] = [
            'payment_intent' => $paymentIntentId,
            'amount' => $amount,
            'idempotency_key' => $idempotencyKey,
        ];

        return new RefundResult('re_test_'.count($this->refunds), $this->refundStatus);
    }

    public function parseWebhook(string $payload, string $signature): WebhookEvent
    {
        try {
            $event = Webhook::constructEvent($payload, $signature, self::WEBHOOK_SECRET);
        } catch (SignatureVerificationException|UnexpectedValueException $exception) {
            throw new InvalidWebhookSignature($exception->getMessage(), previous: $exception);
        }

        /** @var array<string, mixed> $object */
        $object = $event->data->object->toArray();

        return new WebhookEvent($event->type, $object);
    }

    /**
     * Sign a payload the way Stripe does ("t=…,v1=HMAC").
     */
    public static function sign(string $payload, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        return "t={$timestamp},v1=".hash_hmac('sha256', "{$timestamp}.{$payload}", self::WEBHOOK_SECRET);
    }
}
