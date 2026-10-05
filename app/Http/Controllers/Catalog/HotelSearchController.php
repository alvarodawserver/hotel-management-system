<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Catalog\SearchHotels;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\HotelSearchRequest;
use App\Models\Amenity;
use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class HotelSearchController extends Controller
{
    /**
     * Search results with filters, list and map.
     */
    public function __invoke(HotelSearchRequest $request, SearchHotels $searchHotels): Response
    {
        $criteria = $request->criteria();

        return Inertia::render('catalog/index', [
            'results' => $searchHotels->handle(
                $criteria,
                max(1, $request->integer('page', 1)),
                $request->url(),
                $request->query(),
            ),
            'criteria' => $criteria,
            'amenities' => Amenity::query()->get()
                ->map(fn (Amenity $amenity): array => [
                    'id' => $amenity->id,
                    'name' => $amenity->translation(),
                    'icon' => $amenity->icon,
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            'categories' => Category::query()->get()
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->translation(),
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
        ]);
    }
}
