<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Catalog\SearchHotels;
use App\Enums\Province;
use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Hotels count as "new on the coast" during their first days on the
     * platform; the section is hidden when there are none.
     */
    public const NEW_HOTEL_DAYS = 30;

    /**
     * Landing page: search, provinces, current offers and new hotels.
     */
    public function __invoke(SearchHotels $searchHotels): Response
    {
        $hotelCounts = Hotel::query()
            ->published()
            ->selectRaw('province, count(*) as total')
            ->groupBy('province')
            ->pluck('total', 'province');

        $hotels = $searchHotels->results(['sort' => 'newest']);
        $newSince = now()->subDays(self::NEW_HOTEL_DAYS);

        return Inertia::render('welcome', [
            'provinces' => array_map(fn (Province $province): array => [
                'value' => $province->value,
                'label' => $province->label(),
                'hotels_count' => (int) ($hotelCounts[$province->value] ?? 0),
            ], Province::cases()),
            'offers' => $hotels
                ->filter(fn (array $card): bool => $card['price']['discount_percent'] > 0)
                ->sortByDesc(fn (array $card): int => $card['price']['discount_percent'])
                ->take(3)
                ->values(),
            'newHotels' => $hotels
                ->filter(fn (array $card): bool => $card['created_at'] !== null
                    && Carbon::parse($card['created_at'])->gte($newSince))
                ->take(3)
                ->values(),
        ]);
    }
}
