<?php

use App\Enums\Province;
use App\Models\Amenity;
use App\Models\Category;
use App\Models\Hotel;
use App\Models\Offer;
use App\Models\Room;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A published hotel with one active room.
 */
function searchableHotel(array $attributes = [], int $pricePerNight = 10000, int $capacity = 2): Hotel
{
    $hotel = Hotel::factory()->visible()->create($attributes);
    Room::factory()->for($hotel)->create(['price_per_night' => $pricePerNight, 'capacity' => $capacity]);

    return $hotel;
}

/**
 * @return list<int>
 */
function resultIds(TestResponse $response): array
{
    return collect($response->inertiaProps('results.data'))->pluck('id')->all();
}

it('only lists published hotels', function () {
    $published = searchableHotel();
    searchableHotel(['is_visible' => false]);
    Hotel::factory()->visible()->blocked()->create();

    $response = $this->get(route('hotels.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('catalog/index'));

    expect(resultIds($response))->toBe([$published->id]);
});

it('finds hotels by municipality, province or name, ignoring accents and case', function (string $query) {
    $match = searchableHotel(['name' => 'Hotel Faro', 'municipality' => 'Estepona', 'province' => Province::Malaga]);
    searchableHotel(['name' => 'Hotel Duna', 'municipality' => 'Mojácar', 'province' => Province::Almeria]);

    expect(resultIds($this->get(route('hotels.index', ['q' => $query]))))->toBe([$match->id]);
})->with([
    'municipality' => 'estepona',
    'province without accent' => 'malaga',
    'province with accent' => 'Málaga',
    'hotel name' => 'faro',
]);

it('only lists hotels with a room big enough for adults and children', function () {
    $big = searchableHotel(capacity: 4);
    searchableHotel(capacity: 2);

    expect(resultIds($this->get(route('hotels.index', ['adults' => 2, 'children' => 1]))))->toBe([$big->id]);
});

it('filters by price per night and stars', function () {
    $match = searchableHotel(['stars' => 4], pricePerNight: 9000);
    searchableHotel(['stars' => 4], pricePerNight: 20000);
    searchableHotel(['stars' => 2], pricePerNight: 9000);

    $response = $this->get(route('hotels.index', ['price_max' => 100, 'stars' => 4]));

    expect(resultIds($response))->toBe([$match->id]);
});

it('requires every selected amenity but any selected category', function () {
    [$pool, $parking] = Amenity::factory()->count(2)->create();
    [$beach, $luxury] = Category::factory()->count(2)->create();

    $both = searchableHotel();
    $both->amenities()->attach([$pool->id, $parking->id]);
    $both->categories()->attach($luxury);

    $onlyPool = searchableHotel();
    $onlyPool->amenities()->attach($pool);
    $onlyPool->categories()->attach($beach);

    $amenityResponse = $this->get(route('hotels.index', ['amenities' => [$pool->id, $parking->id]]));
    $categoryResponse = $this->get(route('hotels.index', ['categories' => [$beach->id, $luxury->id]]));

    expect(resultIds($amenityResponse))->toBe([$both->id])
        ->and(resultIds($categoryResponse))->toEqualCanonicalizing([$both->id, $onlyPool->id]);
});

it('sorts by price', function () {
    $cheap = searchableHotel(pricePerNight: 6000);
    $expensive = searchableHotel(pricePerNight: 15000);

    expect(resultIds($this->get(route('hotels.index', ['sort' => 'price_desc']))))->toBe([$expensive->id, $cheap->id])
        ->and(resultIds($this->get(route('hotels.index', ['sort' => 'price_asc']))))->toBe([$cheap->id, $expensive->id]);
});

it('prices the whole stay with offers when dates are given', function () {
    $hotel = searchableHotel(pricePerNight: 10000);
    $checkIn = today()->addDays(10);
    Offer::factory()->for($hotel)->create([
        'discount_percent' => 20,
        'starts_on' => $checkIn,
        'ends_on' => $checkIn->copy()->addDays(30),
    ]);

    $response = $this->get(route('hotels.index', [
        'check_in' => $checkIn->toDateString(),
        'check_out' => $checkIn->copy()->addDays(2)->toDateString(),
    ]));

    expect($response->inertiaProps('results.data.0.price'))->toMatchArray([
        'total' => 16000,
        'nights' => 2,
        'has_dates' => true,
        'discount_percent' => 20,
    ]);
});

it('rejects a check-out before the check-in', function () {
    $this->get(route('hotels.index', [
        'check_in' => today()->addDays(5)->toDateString(),
        'check_out' => today()->addDays(3)->toDateString(),
    ]))->assertSessionHasErrors('check_out');
});

it('shows the guest rating on each card and sorts by it', function () {
    $good = searchableHotel(['name' => 'Hotel Bueno']);
    $best = searchableHotel(['name' => 'Hotel Mejor']);
    $unrated = searchableHotel(['name' => 'Hotel Aaa']);
    reviewHotel($good, 4);
    reviewHotel($best, 5);
    reviewHotel($best, 4);
    reviewHotel($best, 1)->delete();

    $response = $this->get(route('hotels.index', ['sort' => 'rating']));

    expect(resultIds($response))->toBe([$best->id, $good->id, $unrated->id])
        ->and($response->inertiaProps('results.data.0.rating'))->toBe(['average' => 4.5, 'count' => 2])
        ->and($response->inertiaProps('results.data.2.rating'))->toBe(['average' => null, 'count' => 0]);
});
