<?php

namespace App\Http\Controllers\Catalog;

use App\Actions\Pricing\CalculateStayPrice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\HotelSearchRequest;
use App\Models\Activity;
use App\Models\Amenity;
use App\Models\Category;
use App\Models\Hotel;
use App\Models\Image;
use App\Models\Offer;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class HotelPageController extends Controller
{
    /**
     * The public hotel page. Hidden or blocked hotels are not found, except
     * for their owner and admins, who see them as a preview.
     */
    public function __invoke(HotelSearchRequest $request, Hotel $hotel, CalculateStayPrice $calculateStayPrice): Response
    {
        $isPreview = ! $hotel->isPublished();

        if ($isPreview && ! $request->user()?->can('update', $hotel)) {
            abort(404);
        }

        $criteria = $request->criteria();

        $hotel->load([
            'images',
            'amenities',
            'categories',
            'activities' => fn (HasMany $query) => $query->orderBy('name'),
            'rooms' => fn (HasMany $query) => $query->where('is_active', true)->with(['roomType', 'images']),
            'offers' => fn (HasMany $query) => $query
                ->where('is_active', true)
                ->whereDate('ends_on', '>=', today())
                ->with('roomType'),
        ]);

        return Inertia::render('catalog/show', [
            'hotel' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'slug' => $hotel->slug,
                'description' => $hotel->description,
                'province' => $hotel->province->label(),
                'municipality' => $hotel->municipality,
                'address' => $hotel->address,
                'latitude' => $hotel->latitude,
                'longitude' => $hotel->longitude,
                'stars' => $hotel->stars,
                'cancellation_policy' => $hotel->cancellation_policy,
                'images' => $hotel->images->map(fn (Image $image): string => $image->url)->values(),
                'amenities' => $hotel->amenities
                    ->map(fn (Amenity $amenity): array => ['name' => $amenity->translation(), 'icon' => $amenity->icon])
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values(),
                'categories' => $hotel->categories
                    ->map(fn (Category $category): string => $category->translation())
                    ->values(),
                'activities' => $hotel->activities->map(fn (Activity $activity): array => [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'description' => $activity->description,
                    'price' => $activity->price,
                    'starts_at' => $activity->starts_at ? substr($activity->starts_at, 0, 5) : null,
                    'ends_at' => $activity->ends_at ? substr($activity->ends_at, 0, 5) : null,
                    'capacity' => $activity->capacity,
                ])->values(),
                'offers' => $hotel->offers->sortBy('starts_on')->map(fn (Offer $offer): array => [
                    'title' => $offer->title,
                    'discount_percent' => $offer->discount_percent,
                    'starts_on' => $offer->starts_on->toDateString(),
                    'ends_on' => $offer->ends_on->toDateString(),
                    'room_type_name' => $offer->roomType?->translation(),
                ])->values(),
            ],
            'roomGroups' => $this->roomGroups($hotel, $criteria, $calculateStayPrice),
            'criteria' => $criteria,
            'isPreview' => $isPreview,
        ]);
    }

    /**
     * Identical rooms (same type, capacity and price) are shown as one
     * option with the number of rooms, and priced once.
     *
     * @param  array{check_in: string|null, check_out: string|null, adults: int, children: int}  $criteria
     * @return Collection<int, array<string, mixed>>
     */
    private function roomGroups(Hotel $hotel, array $criteria, CalculateStayPrice $calculateStayPrice): Collection
    {
        $availableRoomIds = $this->availableRoomIds($hotel, $criteria);

        return $hotel->rooms
            ->groupBy(fn (Room $room): string => "{$room->room_type_id}-{$room->capacity}-{$room->price_per_night}")
            ->map(fn (Collection $rooms, string $key): array => $this->roomGroup($hotel, $rooms, $key, $criteria, $availableRoomIds, $calculateStayPrice))
            ->sortBy('price_per_night')
            ->values();
    }

    /**
     * The active rooms free for the searched dates, or null without dates.
     *
     * @param  array{check_in: string|null, check_out: string|null, adults: int, children: int}  $criteria
     * @return Collection<int, int>|null
     */
    private function availableRoomIds(Hotel $hotel, array $criteria): ?Collection
    {
        if ($criteria['check_in'] === null || $criteria['check_out'] === null) {
            return null;
        }

        return $hotel->rooms()
            ->where('is_active', true)
            ->availableBetween(CarbonImmutable::parse($criteria['check_in']), CarbonImmutable::parse($criteria['check_out']))
            ->pluck('id');
    }

    /**
     * @param  Collection<int, Room>  $rooms  Identical rooms.
     * @param  array{check_in: string|null, check_out: string|null, adults: int, children: int}  $criteria
     * @param  Collection<int, int>|null  $availableRoomIds
     * @return array<string, mixed>
     */
    private function roomGroup(Hotel $hotel, Collection $rooms, string $key, array $criteria, ?Collection $availableRoomIds, CalculateStayPrice $calculateStayPrice): array
    {
        /** @var Room $room */
        $room = $rooms->first();
        $fitsGuests = $room->capacity >= $criteria['adults'] + $criteria['children'];
        $hasDates = $criteria['check_in'] !== null && $criteria['check_out'] !== null;

        return [
            'key' => $key,
            'room_type_id' => $room->room_type_id,
            'room_type' => $room->roomType->translation(),
            'capacity' => $room->capacity,
            'price_per_night' => $room->price_per_night,
            'rooms_count' => $rooms->count(),
            'available_count' => $availableRoomIds === null
                ? null
                : $rooms->filter(fn (Room $room): bool => $availableRoomIds->contains($room->id))->count(),
            'description' => $rooms->pluck('description')->filter()->first(),
            'image_url' => $rooms->flatMap(fn (Room $room): Collection => $room->images)->first()?->url,
            'fits_guests' => $fitsGuests,
            'stay' => $hasDates && $fitsGuests
                ? $calculateStayPrice->handle(
                    $room,
                    CarbonImmutable::parse($criteria['check_in']),
                    CarbonImmutable::parse($criteria['check_out']),
                    $hotel->offers,
                )->toArray()
                : null,
        ];
    }
}
