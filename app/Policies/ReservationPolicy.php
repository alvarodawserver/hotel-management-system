<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReservationPolicy
{
    /**
     * Only customers book; owners and admins manage.
     */
    public function create(User $user): Response
    {
        return $user->isCustomer()
            ? Response::allow()
            : Response::deny(__('Bookings are made with a customer account.'));
    }

    /**
     * The customer who booked, the hotel's owner and admins.
     */
    public function view(User $user, Reservation $reservation): Response
    {
        $allowed = $user->id === $reservation->user_id
            || $user->isAdmin()
            || ($user->isOwner() && $reservation->hotel->owner_id === $user->id);

        return $allowed ? Response::allow() : Response::deny(__('You cannot see this reservation.'));
    }

    public function cancel(User $user, Reservation $reservation): Response
    {
        return $this->view($user, $reservation);
    }

    /**
     * Retrying a failed refund is an admin task.
     */
    public function retryRefund(User $user, Reservation $reservation): bool
    {
        return $user->isAdmin();
    }
}
