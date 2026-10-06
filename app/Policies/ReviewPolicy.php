<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class ReviewPolicy
{
    /**
     * Only the guest who stayed reviews, from the check-out day, once.
     */
    public function create(User $user, Reservation $reservation): Response
    {
        if ($user->id !== $reservation->user_id) {
            return Response::deny(__('You cannot see this reservation.'));
        }

        return $reservation->canBeReviewed()
            ? Response::allow()
            : Response::deny(__('This stay cannot be reviewed.'));
    }

    /**
     * Authors edit and delete their own reviews. Reviews removed by an admin
     * are soft deleted, so they never reach this check.
     */
    public function update(User $user, Review $review): Response
    {
        return $user->id === $review->user_id
            ? Response::allow()
            : Response::deny(__('You can only change your own reviews.'));
    }

    public function delete(User $user, Review $review): Response
    {
        return $this->update($user, $review);
    }

    /**
     * Whoever manages the hotel answers its reviews: its owner and admins.
     */
    public function reply(User $user, Review $review): Response
    {
        return Gate::forUser($user)->inspect('update', $review->hotel);
    }

    /**
     * The hotel's owner cannot remove reviews (they could hide fair
     * criticism), but reports them to the admins. Admins remove directly.
     */
    public function report(User $user, Review $review): Response
    {
        return $user->isOwner() && $review->hotel->owner_id === $user->id
            ? Response::allow()
            : Response::deny(__('Only the hotel’s owner can report its reviews.'));
    }

    /**
     * Removing reviews and dismissing reports is for admins.
     */
    public function moderate(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }
}
