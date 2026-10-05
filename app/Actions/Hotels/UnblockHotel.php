<?php

namespace App\Actions\Hotels;

use App\Models\Hotel;

class UnblockHotel
{
    /**
     * Lift an admin block; the hotel's own visibility setting applies again.
     */
    public function handle(Hotel $hotel): void
    {
        $hotel->forceFill([
            'blocked_at' => null,
            'blocked_reason' => null,
        ])->save();
    }
}
