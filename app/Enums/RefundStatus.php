<?php

namespace App\Enums;

enum RefundStatus: string
{
    /** Requested to Stripe, final result not known yet. */
    case Pending = 'pending';

    case Succeeded = 'succeeded';

    /** Stripe rejected it or could not be reached; an admin can retry. */
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Refund in progress'),
            self::Succeeded => __('Refunded'),
            self::Failed => __('Refund failed'),
        };
    }
}
