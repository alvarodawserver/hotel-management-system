<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * To the customer: their reservation was cancelled, by them, by the hotel
 * or because a late payment found the room taken, and what is refunded.
 */
class ReservationCancelled extends ReservationNotification
{
    /**
     * @param  int  $refundPercent  The share of the total being refunded.
     */
    public function __construct(Reservation $reservation, public int $refundPercent)
    {
        parent::__construct($reservation);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $reservation = $this->reservation;

        return (new MailMessage)
            ->subject(__('Reservation cancelled: :hotel (:code)', [
                'hotel' => $reservation->hotel->name,
                'code' => $reservation->code,
            ]))
            ->markdown('mail.reservations.cancelled', [
                ...$this->viewData(),
                'cancelledByCustomer' => $reservation->cancelled_by === $reservation->user_id,
                'wasPaid' => $reservation->paid_at !== null,
                'refundAmount' => $reservation->refund_amount,
                'refund' => $this->money($reservation->refund_amount),
                'refundPercent' => $this->refundPercent,
                'url' => route('reservations.show', $reservation),
            ]);
    }
}
