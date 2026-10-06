<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Catalog\SearchHotels;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\HotelSearchRequest;
use App\Models\Amenity;
use App\Models\Category;
use App\Models\Hotel;
use Inertia\Inertia;
use Inertia\Response;

class HotelCompareController extends Controller
{
    public const MAX_HOTELS = 3;

    /**
     * Up to three published hotels side by side, priced for the searched
     * dates and guests when given.
     */
    public function __invoke(HotelSearchRequest $request, SearchHotels $searchHotels): Response
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_HOTELS],
            'ids.*' => ['integer'],
        ]);

        /** @var list<int> $ids */
        $ids = array_values(array_map(intval(...), (array) $request->input('ids')));
        $criteria = $request->criteria();

        $hotels = Hotel::query()
            ->published()
            ->whereKey($ids)
            ->with(['coverImage', 'amenities', 'categories'])
            ->withCount(['activities', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->get()
            ->sortBy(fn (Hotel $hotel): int => (int) array_search($hotel->id, $ids, true))
            ->values();

        $amenities = $hotels
            ->flatMap(fn (Hotel $hotel) => $hotel->amenities)
            ->unique('id')
            ->map(fn (Amenity $amenity): array => [
                'id' => $amenity->id,
                'name' => $amenity->translation(),
                'icon' => $amenity->icon,
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return Inertia::render('catalog/compare', [
            'hotels' => $hotels->map(fn (Hotel $hotel): array => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
                'province' => $hotel->province->label(),
                'municipality' => $hotel->municipality,
                'stars' => $hotel->stars,
                'rating' => $hotel->rating(),
                'cover_url' => $hotel->coverImage?->url,
                'price' => $searchHotels->cheapestStay($hotel, $criteria),
                'amenity_ids' => $hotel->amenities->modelKeys(),
                'categories' => $hotel->categories
                    ->map(fn (Category $category): string => $category->translation())
                    ->values(),
                'cancellation_policy' => $hotel->cancellation_policy,
                'activities_count' => $hotel->activities_count,
            ]),
            'amenities' => $amenities,
            'criteria' => $criteria,
        ]);
    }
}
