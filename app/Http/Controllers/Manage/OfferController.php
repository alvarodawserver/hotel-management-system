<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\OfferRequest;
use App\Http\Resources\HotelSummaryResource;
use App\Models\Hotel;
use App\Models\Offer;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    /**
     * List the hotel's offers with their current status.
     */
    public function index(Hotel $hotel): Response
    {
        Gate::authorize('update', $hotel);

        $offers = $hotel->offers()
            ->with('roomType')
            ->orderByDesc('starts_on')
            ->get()
            ->map(fn (Offer $offer): array => [
                'id' => $offer->id,
                'title' => $offer->title,
                'room_type_id' => $offer->room_type_id,
                'room_type_name' => $offer->roomType?->translation(),
                'discount_percent' => $offer->discount_percent,
                'starts_on' => $offer->starts_on->toDateString(),
                'ends_on' => $offer->ends_on->toDateString(),
                'is_active' => $offer->is_active,
                'status' => $offer->status(),
            ]);

        $roomTypeIds = $hotel->rooms()->distinct()->pluck('room_type_id');

        return Inertia::render('manage/hotels/offers', [
            'hotel' => HotelSummaryResource::make($hotel)->resolve(),
            'offers' => $offers,
            'roomTypes' => RoomType::query()
                ->whereKey($roomTypeIds)
                ->get()
                ->map(fn (RoomType $roomType): array => [
                    'id' => $roomType->id,
                    'name' => $roomType->translation(),
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
        ]);
    }

    public function store(OfferRequest $request, Hotel $hotel): RedirectResponse
    {
        $hotel->offers()->create($request->offerAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offer created.')]);

        return to_route('manage.hotels.offers.index', $hotel);
    }

    public function update(OfferRequest $request, Hotel $hotel, Offer $offer): RedirectResponse
    {
        $offer->update($request->offerAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offer updated.')]);

        return to_route('manage.hotels.offers.index', $hotel);
    }

    public function destroy(Hotel $hotel, Offer $offer): RedirectResponse
    {
        Gate::authorize('update', $hotel);

        $offer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offer deleted.')]);

        return to_route('manage.hotels.offers.index', $hotel);
    }
}
