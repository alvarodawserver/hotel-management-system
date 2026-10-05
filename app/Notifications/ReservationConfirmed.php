<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * To the customer: the booking is paid and confirmed.
 */
class ReservationConfirmed extends ReservationNotification
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Booking confirmed: :hotel (:code)', [
                'hotel' => $this->reservation->hotel->name,
                'code' => $this->reservation->code,
            ]))
            ->markdown('mail.reservations.confirmed', [
                ...$this->viewData(),
                'policy' => $this->cancellationPolicy(),
                'url' => route('reservations.show', $this->reservation),
            ]);
    }
}
