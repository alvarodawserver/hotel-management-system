<?php

namespace App\Actions\Reservations;

use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class ConfirmReservationPayment
{
    public function __construct(private RefundReservation $refundReservation) {}

    /**
     * Handle a completed Stripe payment page.
     *
     * Safe to run more than once for the same payment. The payment intent is
     * stored here: it is what refunds are made against later. If the payment
     * arrives after the reservation stopped holding its room and the room is
     * no longer free, the payment is refunded in full.
     *
     * @param  array<string, mixed>  $session  The Stripe Checkout Session.
     */
    public function handle(array $session): void
    {
        if (($session['payment_status'] ?? null) !== 'paid') {
            return;
        }

        $reservation = $this->findReservation($session);

        if ($reservation === null) {
            return;
        }

        $needsRefund = DB::transaction(function () use ($reservation, $session): bool {
            $reservation = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            // This payment was already handled (Stripe repeats events).
            if ($reservation->isConfirmed() || $reservation->paid_at !== null) {
                return false;
            }

            $reservation->forceFill([
                'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
                'paid_at' => now(),
            ]);

            if ($this->roomStillFree($reservation)) {
                $reservation->forceFill(['status' => ReservationStatus::Confirmed])->save();

                return false;
            }

            $reservation->forceFill([
                'status' => ReservationStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => __('Payment received after the booking expired; the room was no longer available.'),
                'refund_amount' => $reservation->total_price,
                'refund_status' => RefundStatus::Pending,
            ])->save();

            return true;
        });

        if ($needsRefund) {
            $this->refundReservation->handle($reservation->refresh());
        }
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function findReservation(array $session): ?Reservation
    {
        return Reservation::query()->where('stripe_checkout_session_id', $session['id'] ?? null)->first()
            ?? Reservation::query()->whereKey($session['metadata']['reservation_id'] ?? null)->first();
    }

    /**
     * A reservation that was cancelled meanwhile never gets the room back;
     * an expired or pending one does if nobody else holds it.
     */
    private function roomStillFree(Reservation $reservation): bool
    {
        if ($reservation->status === ReservationStatus::Cancelled) {
            return false;
        }

        return Reservation::query()
            ->where('room_id', $reservation->room_id)
            ->whereKeyNot($reservation->id)
            ->blocking()
            ->overlapping($reservation->check_in, $reservation->check_out)
            ->doesntExist();
    }
}
