<?php

namespace App\Actions\Reservations;

use App\Models\Reservation;
use App\Models\User;

/**
 * The single place that decides how much a cancellation refunds. The
 * cancel dialog shows this quote and CancelReservation refunds exactly it.
 */
class CalculateRefund
{
    public function handle(Reservation $reservation, User $cancelledBy): RefundQuote
    {
        $daysBefore = max(0, (int) Reservation::today()->diffInDays($reservation->check_in->toDateString(), absolute: false));

        // Nothing was paid yet: nothing to give back.
        if (! $reservation->isConfirmed()) {
            return new RefundQuote(0, 0, $daysBefore);
        }

        $percent = $this->cancelledByHotel($reservation, $cancelledBy)
            ? 100
            : $this->percentForDaysBefore($reservation, $daysBefore);

        return new RefundQuote(
            $percent,
            (int) round($reservation->total_price * $percent / 100),
            $daysBefore,
        );
    }

    /**
     * Owners and admins cancelling a booking always refund it in full.
     */
    private function cancelledByHotel(Reservation $reservation, User $user): bool
    {
        return $user->isAdmin()
            || ($user->isOwner() && $reservation->hotel->owner_id === $user->id);
    }

    /**
     * The tiers are sorted from the most to the least days before check-in;
     * the first one the cancellation is early enough for applies.
     */
    private function percentForDaysBefore(Reservation $reservation, int $daysBefore): int
    {
        foreach ($reservation->hotel->cancellation_policy as $tier) {
            if ($daysBefore >= $tier['days_before']) {
                return $tier['refund_percent'];
            }
        }

        return 0;
    }
}
