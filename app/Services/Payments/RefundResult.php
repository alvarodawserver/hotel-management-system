<?php

namespace App\Services\Payments;

use App\Enums\RefundStatus;

final readonly class RefundResult
{
    public function __construct(
        public string $id,
        public RefundStatus $status,
    ) {}

    /**
     * Map a provider refund status ("succeeded", "pending", "failed",
     * "canceled", "requires_action") to ours.
     */
    public static function statusFrom(string $providerStatus): RefundStatus
    {
        return match ($providerStatus) {
            'succeeded' => RefundStatus::Succeeded,
            'pending', 'requires_action' => RefundStatus::Pending,
            default => RefundStatus::Failed,
        };
    }
}
