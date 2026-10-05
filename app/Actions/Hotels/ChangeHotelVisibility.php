<?php

namespace App\Actions\Hotels;

use App\Models\Hotel;
use Illuminate\Validation\ValidationException;

class ChangeHotelVisibility
{
    /**
     * Show or hide the hotel in the public catalogue.
     *
     * Hiding is always allowed (e.g. during maintenance) and never cancels
     * existing reservations. Showing requires at least one active room, one
     * photo and a map location, so the catalogue never lists an empty hotel.
     *
     * @throws ValidationException
     */
    public function handle(Hotel $hotel, bool $visible): void
    {
        if ($visible) {
            $this->ensureCanBePublished($hotel);
        }

        $hotel->forceFill(['is_visible' => $visible])->save();
    }

    /**
     * @throws ValidationException
     */
    private function ensureCanBePublished(Hotel $hotel): void
    {
        $missing = [];

        if (! $hotel->hasActiveRooms()) {
            $missing[] = __('at least one active room');
        }

        if (! $hotel->images()->exists()) {
            $missing[] = __('at least one photo');
        }

        if (! $hotel->hasLocation()) {
            $missing[] = __('its location on the map');
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'is_visible' => __('To publish the hotel it needs :requirements.', [
                    'requirements' => implode(__(' and '), $missing),
                ]),
            ]);
        }
    }
}
