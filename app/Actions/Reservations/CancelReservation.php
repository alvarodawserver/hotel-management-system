<?php

namespace App\Actions\Reservations;

use App\Contracts\PaymentGateway;
use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationCancelled;
use App\Notifications\ReservationCancelledByGuest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelReservation
{
    public function __construct(
        private CalculateRefund $calculateRefund,
        private RefundReservation $refundReservation,
        private PaymentGateway $gateway,
    ) {}

    /**
     * Cancel a reservation (by its customer, the hotel owner or an admin),
     * free the room at once and refund what the cancellation policy says.
     *
     * @throws ValidationException
     */
    public function handle(Reservation $reservation, User $cancelledBy, ?string $reason = null): RefundQuote
    {
        if (! $reservation->canBeCancelled()) {
            throw ValidationException::withMessages([
                'reservation' => __('This reservation can no longer be cancelled.'),
            ]);
        }

        $quote = $this->calculateRefund->handle($reservation, $cancelledBy);
        $wasPending = $reservation->isPending();

        DB::transaction(function () use ($reservation, $cancelledBy, $reason, $quote): void {
            $reservation->forceFill([
                'status' => ReservationStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy->id,
                'cancellation_reason' => $reason,
                'refund_amount' => $quote->amount,
                'refund_status' => $quote->amount > 0 ? RefundStatus::Pending : null,
            ])->save();
        });

        if ($wasPending && $reservation->stripe_checkout_session_id !== null) {
            rescue(fn () => $this->gateway->expireCheckoutSession($reservation->stripe_checkout_session_id), report: false);
        }

        $this->refundReservation->handle($reservation);

        $reservation->user->notify(new ReservationCancelled($reservation, $quote->percent));

        // The hotel only needs telling when the guest cancels; when the
        // owner or an admin cancels, they already know.
        if ($cancelledBy->id === $reservation->user_id) {
            $reservation->hotel->owner->notify(new ReservationCancelledByGuest($reservation));
        }

        return $quote;
    }
}
