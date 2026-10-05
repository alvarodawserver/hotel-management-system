<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class DeactivateUser
{
    /**
     * Deactivate the user's account, keeping all of their data.
     *
     * Business rules:
     * - an owner cannot be deactivated while they still have hotels;
     * - a customer cannot be deactivated while they have active reservations.
     *
     * @throws ValidationException
     */
    public function handle(User $user): void
    {
        $this->ensureCanBeDeactivated($user);

        $user->forceFill(['deactivated_at' => now()])->save();
    }

    /**
     * @throws ValidationException
     */
    private function ensureCanBeDeactivated(User $user): void
    {
        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'user' => __('This account is already deactivated.'),
            ]);
        }

        $hotelCount = $user->hotels()->count();

        if ($hotelCount > 0) {
            throw ValidationException::withMessages([
                'user' => trans_choice(
                    'The owner has :count hotel; delete it before deactivating the account.|The owner has :count hotels; delete them before deactivating the account.',
                    $hotelCount,
                ),
            ]);
        }

        $reservationCount = $user->reservations()->active()->count();

        if ($reservationCount > 0) {
            throw ValidationException::withMessages([
                'user' => trans_choice(
                    'The customer has :count active reservation; it must end or be cancelled before deactivating the account.|The customer has :count active reservations; they must end or be cancelled before deactivating the account.',
                    $reservationCount,
                ),
            ]);
        }
    }
}
