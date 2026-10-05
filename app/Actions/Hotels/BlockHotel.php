<?php

namespace App\Actions\Hotels;

use App\Models\Hotel;

class BlockHotel
{
    /**
     * Hide the hotel from the public catalogue regardless of its visibility.
     * The owner cannot lift a block; existing reservations are kept.
     */
    public function handle(Hotel $hotel, string $reason): void
    {
        $hotel->forceFill([
            'blocked_at' => now(),
            'blocked_reason' => $reason,
        ])->save();
    }
}
