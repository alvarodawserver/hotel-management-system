<?php

namespace App\Actions\Reservations;

/**
 * How much of a reservation would be refunded if it were cancelled now.
 */
final readonly class RefundQuote
{
    public function __construct(
        /** Percentage of the amount paid (0–100). */
        public int $percent,
        /** In euro cents. */
        public int $amount,
        /** Whole days left until check-in when cancelling. */
        public int $daysBefore,
    ) {}

    /**
     * @return array{percent: int, amount: int, days_before: int}
     */
    public function toArray(): array
    {
        return [
            'percent' => $this->percent,
            'amount' => $this->amount,
            'days_before' => $this->daysBefore,
        ];
    }
}
