<?php

namespace App\Policies;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HotelPolicy
{
    /**
     * Owners list their own hotels; admins use the admin hotel list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin();
    }

    /**
     * Only owners create hotels (they become the hotel's owner).
     */
    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * The hotel's owner and admins manage its details, rooms, photos and activities.
     */
    public function update(User $user, Hotel $hotel): Response
    {
        return $this->ownsOrAdministers($user, $hotel)
            ? Response::allow()
            : Response::deny(__('You can only manage your own hotels.'));
    }

    public function delete(User $user, Hotel $hotel): Response
    {
        return $this->update($user, $hotel);
    }

    /**
     * Blocking hides a hotel regardless of its visibility; only admins may do it.
     */
    public function block(User $user, Hotel $hotel): bool
    {
        return $user->isAdmin();
    }

    private function ownsOrAdministers(User $user, Hotel $hotel): bool
    {
        return $user->isAdmin() || ($user->isOwner() && $hotel->owner_id === $user->id);
    }
}
