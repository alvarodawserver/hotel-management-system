<?php

namespace App\Actions\Users;

use App\Models\User;

class ReactivateUser
{
    /**
     * Restore access to a previously deactivated account.
     */
    public function handle(User $user): void
    {
        $user->forceFill(['deactivated_at' => null])->save();
    }
}
