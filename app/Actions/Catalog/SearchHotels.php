<?php

namespace App\Actions\Catalog;

use App\Actions\Pricing\CalculateStayPrice;
use App\Actions\Pricing\StayPriceBreakdown;
use App\Models\Amenity;
use App\Models\Hotel;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;

/**
 * Public hotel search used by the catalogue, the home page and the compare
 * page.
 *
 * Database filters narrow the candidates first; destination matching
 * (accent-insensitive), pricing and price filters/sorting then run in PHP,
 * because the price depends on the dates and offers. That is fine for the
 * catalogue size of this project (tens of hotels); a larger catalogue would
 * need precomputed prices.
 */
class SearchHotels
{
    public const PER_PAGE = 12;

    public function __construct(private CalculateStayPrice $calculateStayPrice) {}

    /**
     * @param  array{q?: string|null, check_in?: string|null, check_out?: string|null, adults?: int|null, children?: int|null, price_min?: int|null, price_max?: int|null, stars?: int|null, amenities?: list<int>, categories?: list<int>, sort?: string|null}  $criteria
     * @param  array<string, mixed>  $query  The query string, kept in pagination links.
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function handle(array $criteria, int $page = 1, string $path = '/', array $query = []): LengthAwarePaginator
    {
        $results = $this->results($criteria);

        return new LengthAwarePaginator(
            $results->forPage($page, self::PER_PAGE)->values(),
            $results->count(),
            self::PER_PAGE,
            $page,
            ['path' => $path, 'query' => $query],
        );
    }

    /**
     * Every matching hotel as a catalogue card, filtered and sorted.
     *
     * @param  array{q?: string|null, check_in?: string|null, check_out?: string|null, adults?: int|null, children?: int|null, price_min?: int|null, price_max?: int|null, stars?: int|null, amenities?: list<int>, categories?: list<int>, sort?: string|null}  $criteria
     * @return Collection<int, array<string, mixed>>
     */
    public function results(array $criteria): Collection
    {
        $guests = $this->guests($criteria);
        [$checkIn, $checkOut, $hasDates] = $this->stayRange($criteria);

        $cards = $this->candidates($criteria, $guests, $checkIn, $checkOut, $hasDates)
            ->filter(fn (Hotel $hotel): bool => $this->matchesDestination($hotel, $criteria['q'] ?? null))
            ->map(fn (Hotel $hotel): array => $this->card($hotel, $checkIn, $checkOut, $hasDates))
            ->filter(fn (array $card): bool => $this->matchesPrice($card, $criteria));

        return $this->sort($cards, $criteria['sort'] ?? 'recommended')->values();
    }

    /**
     * Price of the cheapest room able to host the guests, for the given
     * stay (or tonight when no dates are given).
     *
     * @param  array{check_in?: string|null, check_out?: string|null, adults?: int|null, children?: int|null}  $criteria
     * @return array{total: int, per_night: int, nights: int, has_dates: bool, discount_percent: int}|null
     */
    public function cheapestStay(Hotel $hotel, array $criteria): ?array
    {
        [$checkIn, $checkOut, $hasDates] = $this->stayRange($criteria);

        $hotel->loadMissing([
            'rooms' => fn ($query) => $query->bookableFor($this->guests($criteria), ...$this->availabilityRange($checkIn, $checkOut, $hasDates)),
            'offers' => fn ($query) => $query->activeBetween($checkIn, $checkOut->subDay()),
        ]);

        return $this->pricing($hotel, $checkIn, $checkOut, $hasDates);
    }

    /**
     * @param  array{q?: string|null, stars?: int|null, amenities?: list<int>, categories?: list<int>}  $criteria
     * @return Collection<int, Hotel>
     */
    private function candidates(array $criteria, int $guests, CarbonImmutable $checkIn, CarbonImmutable $checkOut, bool $hasDates): Collection
    {
        return Hotel::query()
            ->published()
            ->whereHas('rooms', fn (Builder $query) => $query->bookableFor($guests, ...$this->availabilityRange($checkIn, $checkOut, $hasDates)))
            ->when($criteria['stars'] ?? null, fn (Builder $query, int $stars) => $query->where('stars', '>=', $stars))
            // Amenities: the hotel must have every selected one.
            ->when($criteria['amenities'] ?? [], function (Builder $query, array $amenityIds): void {
                foreach ($amenityIds as $amenityId) {
                    $query->whereHas('amenities', fn (Builder $query) => $query->whereKey($amenityId));
                }
            })
            // Categories: any of the selected ones is enough.
            ->when($criteria['categories'] ?? [], fn (Builder $query, array $categoryIds) => $query
                ->whereHas('categories', fn (Builder $query) => $query->whereKey($categoryIds)))
            ->with([
                'coverImage',
                'amenities',
                'rooms' => fn ($query) => $query->bookableFor($guests, ...$this->availabilityRange($checkIn, $checkOut, $hasDates)),
                'offers' => fn ($query) => $query->activeBetween($checkIn, $checkOut->subDay()),
            ])
            ->get();
    }

    /**
     * Candidates always have at least one bookable room (see candidates()),
     * so they always have a price.
     *
     * @return array<string, mixed>
     */
    private function card(Hotel $hotel, CarbonImmutable $checkIn, CarbonImmutable $checkOut, bool $hasDates): array
    {
        $pricing = $this->pricing($hotel, $checkIn, $checkOut, $hasDates)
            ?? throw new LogicException("Hotel {$hotel->id} has no bookable room to price.");

        return [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'slug' => $hotel->slug,
            'province' => $hotel->province->label(),
            'municipality' => $hotel->municipality,
            'stars' => $hotel->stars,
            'cover_url' => $hotel->coverImage?->url,
            'latitude' => $hotel->latitude,
            'longitude' => $hotel->longitude,
            'amenities' => $hotel->amenities
                ->take(4)
                ->map(fn (Amenity $amenity): array => ['name' => $amenity->translation(), 'icon' => $amenity->icon])
                ->values()
                ->all(),
            'price' => $pricing,
            'created_at' => $hotel->created_at?->toIso8601String(),
        ];
    }

    /**
     * Cheapest stay among the loaded bookable rooms. Rooms of the same type
     * and price cost the same, so each combination is priced once.
     *
     * @return array{total: int, per_night: int, nights: int, has_dates: bool, discount_percent: int}|null
     */
    private function pricing(Hotel $hotel, CarbonImmutable $checkIn, CarbonImmutable $checkOut, bool $hasDates): ?array
    {
        /** @var StayPriceBreakdown|null $cheapest */
        $cheapest = $hotel->rooms
            ->unique(fn (Room $room): string => $room->room_type_id.'-'.$room->price_per_night)
            ->map(fn (Room $room): StayPriceBreakdown => $this->calculateStayPrice->handle($room, $checkIn, $checkOut, $hotel->offers))
            ->sortBy('total')
            ->first();

        if ($cheapest === null) {
            return null;
        }

        return [
            'total' => $cheapest->total,
            'per_night' => (int) round($cheapest->total / $cheapest->nightCount()),
            'nights' => $cheapest->nightCount(),
            'has_dates' => $hasDates,
            'discount_percent' => $cheapest->bestDiscountPercent(),
        ];
    }

    private function matchesDestination(Hotel $hotel, ?string $destination): bool
    {
        if (blank($destination)) {
            return true;
        }

        $haystack = $this->normalize("{$hotel->name} {$hotel->municipality} {$hotel->province->label()}");

        return Str::contains($haystack, $this->normalize($destination));
    }

    /**
     * Price filters are in euros per night; prices are in cents.
     *
     * @param  array<string, mixed>  $card
     * @param  array{price_min?: int|null, price_max?: int|null}  $criteria
     */
    private function matchesPrice(array $card, array $criteria): bool
    {
        $perNight = $card['price']['per_night'];

        return (! isset($criteria['price_min']) || $perNight >= $criteria['price_min'] * 100)
            && (! isset($criteria['price_max']) || $perNight <= $criteria['price_max'] * 100);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $cards
     * @return Collection<int, array<string, mixed>>
     */
    private function sort(Collection $cards, string $sort): Collection
    {
        return match ($sort) {
            'price_asc' => $cards->sortBy(fn (array $card): int => $card['price']['total']),
            'price_desc' => $cards->sortByDesc(fn (array $card): int => $card['price']['total']),
            'newest' => $cards->sortByDesc('created_at'),
            // Recommended: hotels with an offer first, then by stars and name.
            default => $cards->sortBy([
                fn (array $a, array $b): int => $b['price']['discount_percent'] <=> $a['price']['discount_percent'],
                fn (array $a, array $b): int => ($b['stars'] ?? 0) <=> ($a['stars'] ?? 0),
                fn (array $a, array $b): int => $a['name'] <=> $b['name'],
            ]),
        };
    }

    /**
     * @param  array{adults?: int|null, children?: int|null}  $criteria
     */
    private function guests(array $criteria): int
    {
        return max(1, (int) ($criteria['adults'] ?? 2) + (int) ($criteria['children'] ?? 0));
    }

    /**
     * The searched stay, or tonight (one night) when no dates were given, so
     * the "from" price also reflects today's offers.
     *
     * @param  array{check_in?: string|null, check_out?: string|null}  $criteria
     * @return array{CarbonImmutable, CarbonImmutable, bool}
     */
    private function stayRange(array $criteria): array
    {
        if (filled($criteria['check_in'] ?? null) && filled($criteria['check_out'] ?? null)) {
            return [
                CarbonImmutable::parse($criteria['check_in'])->startOfDay(),
                CarbonImmutable::parse($criteria['check_out'])->startOfDay(),
                true,
            ];
        }

        $today = CarbonImmutable::today();

        return [$today, $today->addDay(), false];
    }

    /**
     * Availability is only checked for searched dates; without dates every
     * active room counts (the "tonight" range is only used for pricing).
     *
     * @return array{0: CarbonImmutable|null, 1: CarbonImmutable|null}
     */
    private function availabilityRange(CarbonImmutable $checkIn, CarbonImmutable $checkOut, bool $hasDates): array
    {
        return $hasDates ? [$checkIn, $checkOut] : [null, null];
    }

    private function normalize(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }
}
