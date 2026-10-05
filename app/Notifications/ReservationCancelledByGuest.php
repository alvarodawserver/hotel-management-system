<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * To the hotel owner: a guest cancelled their booking, so the room is free.
 */
class ReservationCancelledByGuest extends ReservationNotification
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Booking cancelled by the guest: :hotel (:code)', [
                'hotel' => $this->reservation->hotel->name,
                'code' => $this->reservation->code,
            ]))
            ->markdown('mail.reservations.cancelled-by-guest', [
                ...$this->viewData(),
                'refund' => $this->money($this->reservation->refund_amount),
                'url' => route('manage.reservations.show', $this->reservation),
            ]);
    }
}
