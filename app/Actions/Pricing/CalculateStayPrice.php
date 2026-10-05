<?php

namespace App\Actions\Pricing;

use App\Models\Offer;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The single place where the price of a stay is computed. The catalogue,
 * the hotel page and the payment all use it, so the price shown is always
 * the price charged.
 */
class CalculateStayPrice
{
    /**
     * Price every night from check-in up to (not including) check-out. Each
     * night gets the single best active offer covering it for the room's
     * type or the whole hotel; offers never stack.
     *
     * @param  Collection<int, Offer>|null  $offers  The hotel's offers, when already loaded (avoids a query per room).
     */
    public function handle(Room $room, CarbonImmutable $checkIn, CarbonImmutable $checkOut, ?Collection $offers = null): StayPriceBreakdown
    {
        $checkIn = $checkIn->startOfDay();
        $checkOut = $checkOut->startOfDay();

        if ($checkOut->lte($checkIn)) {
            throw new InvalidArgumentException('Check-out must be after check-in.');
        }

        $lastNight = $checkOut->subDay();
        $offers ??= Offer::query()
            ->where('hotel_id', $room->hotel_id)
            ->activeBetween($checkIn, $lastNight)
            ->get();

        $nights = [];

        foreach (CarbonPeriod::create($checkIn, $lastNight) as $night) {
            $discountPercent = (int) $offers
                ->filter(fn (Offer $offer): bool => $offer->appliesTo($night, $room->room_type_id))
                ->max('discount_percent');

            $base = $room->price_per_night;

            $nights[] = [
                'date' => $night->toDateString(),
                'base' => $base,
                'discount_percent' => $discountPercent,
                'price' => $base - (int) round($base * $discountPercent / 100),
            ];
        }

        $subtotal = array_sum(array_column($nights, 'base'));
        $total = array_sum(array_column($nights, 'price'));

        return new StayPriceBreakdown($nights, $subtotal, $subtotal - $total, $total);
    }
}
