<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * To the hotel owner: a guest has booked and paid a room of their hotel.
 */
class NewReservationForHotel extends ReservationNotification
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New booking at :hotel (:code)', [
                'hotel' => $this->reservation->hotel->name,
                'code' => $this->reservation->code,
            ]))
            ->markdown('mail.reservations.new-for-hotel', [
                ...$this->viewData(),
                'url' => route('manage.reservations.show', $this->reservation),
            ]);
    }
}
