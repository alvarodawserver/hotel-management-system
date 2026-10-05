<?php

namespace App\Enums;

enum ReservationStatus: string
{
    /** Created, waiting for the payment (holds the room until expires_at). */
    case Pending = 'pending';

    /** Paid; confirmed by the Stripe webhook. */
    case Confirmed = 'confirmed';

    /** Cancelled by the customer, the hotel owner or an admin. */
    case Cancelled = 'cancelled';

    /** Never paid; the payment window ran out. */
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Awaiting payment'),
            self::Confirmed => __('Confirmed'),
            self::Cancelled => __('Cancelled'),
            self::Expired => __('Expired'),
        };
    }
}
