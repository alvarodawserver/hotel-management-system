<?php

namespace App\Services\Payments;

/**
 * A verified webhook event: its type (e.g. "checkout.session.completed")
 * and the object it refers to, as an array.
 */
final readonly class WebhookEvent
{
    /**
     * @param  array<string, mixed>  $object
     */
    public function __construct(
        public string $type,
        public array $object,
    ) {}
}
