<?php

use App\Actions\Dashboard\CalculateDashboardStats;
use App\Enums\ReservationStatus;
use App\Models\Hotel;
use App\Models\Image;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Room;
use App\Models\User;

beforeEach(function () {
    $this->travelTo('2026-10-15 10:00:00');
});

/**
 * A paid stay in a new room of the given hotel, from a check-in date
 * ("Y-m-d") for some nights, at the given total.
 */
function dashboardStay(Hotel $hotel, string $checkIn, int $nights = 2, int $total = 10000): Reservation
{
    return Reservation::factory()
        ->forRoom(Room::factory()->for($hotel)->create())
        ->create([
            'check_in' => $checkIn,
            'check_out' => date('Y-m-d', strtotime("{$checkIn} +{$nights} days")),
            'total_price' => $total,
        ]);
}

describe('revenue', function () {
    it('counts what was paid minus refunds, by check-in month, for the owner’s hotels only', function () {
        $owner = User::factory()->owner()->create();
        $hotel = Hotel::factory()->for($owner, 'owner')->visible()->create();

        dashboardStay($hotel, '2026-10-20', total: 30000);
        dashboardStay($hotel, '2026-10-05', total: 20000)->forceFill([
            'status' => ReservationStatus::Cancelled,
            'refund_amount' => 15000,
        ])->save();
        Reservation::factory()->expired()->forRoom(Room::factory()->for($hotel)->create())->stay(3)->create(['total_price' => 50000]);
        dashboardStay($hotel, '2026-09-30', total: 12000);
        dashboardStay(Hotel::factory()->visible()->create(), '2026-10-10', total: 99000);

        $stats = app(CalculateDashboardStats::class);

        expect($stats->revenue($owner))->toBe(['current' => 35000, 'previous' => 12000])
            ->and($stats->revenue(User::factory()->admin()->create()))->toBe(['current' => 134000, 'previous' => 12000]);
    });

    it('splits the last twelve months, counting cancelled stays as revenue but not as stays', function () {
        $owner = User::factory()->owner()->create();
        $hotel = Hotel::factory()->for($owner, 'owner')->visible()->create();

        dashboardStay($hotel, '2026-10-20', total: 30000);
        dashboardStay($hotel, '2026-08-02', total: 20000)->forceFill([
            'status' => ReservationStatus::Cancelled,
            'refund_amount' => 5000,
        ])->save();
        dashboardStay($hotel, '2025-10-31', total: 40000);

        $months = app(CalculateDashboardStats::class)->monthlyRevenue($owner);

        expect($months)->toHaveCount(12)
            ->and($months[0])->toBe(['month' => '2025-11', 'revenue' => 0, 'stays' => 0])
            ->and($months[9])->toBe(['month' => '2026-08', 'revenue' => 15000, 'stays' => 0])
            ->and($months[11])->toBe(['month' => '2026-10', 'revenue' => 30000, 'stays' => 1]);
    });
});

it('measures occupancy over the next 30 days in active rooms of published hotels', function () {
    $owner = User::factory()->owner()->create();
    $hotel = Hotel::factory()->for($owner, 'owner')->visible()->create();
    [$first, $second] = Room::factory()->for($hotel)->count(2)->create();
    Room::factory()->for($hotel)->inactive()->create();
    $hiddenRoom = Room::factory()->for(Hotel::factory()->for($owner, 'owner')->create())->create();

    Reservation::factory()->forRoom($first)->create(['check_in' => '2026-10-14', 'check_out' => '2026-10-17']);
    Reservation::factory()->forRoom($first)->create(['check_in' => '2026-11-12', 'check_out' => '2026-11-20']);
    Reservation::factory()->forRoom($second)->create(['check_in' => '2026-10-20', 'check_out' => '2026-10-24']);
    Reservation::factory()->forRoom($second)->expired()->create(['check_in' => '2026-10-25', 'check_out' => '2026-10-28']);
    Reservation::factory()->forRoom($hiddenRoom)->create(['check_in' => '2026-10-20', 'check_out' => '2026-10-24']);

    // 2 nights left of the stay in progress, 2 of the one ending after the window, 4 in the middle.
    expect(app(CalculateDashboardStats::class)->occupancy($owner))->toBe([
        'percent' => 13,
        'booked_nights' => 8,
        'available_nights' => 60,
    ]);
});

it('lists confirmed arrivals of the next seven days, soonest first', function () {
    $owner = User::factory()->owner()->create();
    $hotel = Hotel::factory()->for($owner, 'owner')->visible()->create();

    $later = dashboardStay($hotel, '2026-10-21');
    $today = dashboardStay($hotel, '2026-10-15', nights: 3);
    dashboardStay($hotel, '2026-10-22');
    dashboardStay($hotel, '2026-10-15')->forceFill(['status' => ReservationStatus::Cancelled])->save();
    dashboardStay(Hotel::factory()->visible()->create(), '2026-10-15');

    $stats = app(CalculateDashboardStats::class);

    expect($stats->arrivalCounts($owner))->toBe(['today' => 1, 'week' => 2])
        ->and(array_column($stats->upcomingArrivals($owner), 'code'))->toBe([$today->code, $later->code])
        ->and($stats->upcomingArrivals($owner)[0])->toMatchArray([
            'hotel' => $hotel->name,
            'check_in' => '2026-10-15',
            'nights' => 3,
        ]);
});

it('averages the ratings of the owner’s hotels without removed reviews', function () {
    $owner = User::factory()->owner()->create();
    $hotel = Hotel::factory()->for($owner, 'owner')->visible()->create();

    foreach ([5, 4] as $rating) {
        Review::factory()->forReservation(dashboardStay($hotel, '2026-10-01'))->create(['rating' => $rating]);
    }
    Review::factory()->removed()->forReservation(dashboardStay($hotel, '2026-10-01'))->create(['rating' => 1]);
    Review::factory()->create(['rating' => 1]);

    expect(app(CalculateDashboardStats::class)->rating($owner))->toBe(['average' => 4.5, 'count' => 2]);
});

describe('attention', function () {
    it('shows owners their unanswered reviews and the hotels that are not public', function () {
        $owner = User::factory()->owner()->create();
        $reviewed = Hotel::factory()->for($owner, 'owner')->visible()->create(['name' => 'Reviewed']);
        Review::factory()->forReservation(dashboardStay($reviewed, '2026-10-01'))->create();
        Review::factory()->forReservation(dashboardStay($reviewed, '2026-10-02'))->create();
        Review::factory()->withReply()->forReservation(dashboardStay($reviewed, '2026-10-01'))->create();

        $draft = Hotel::factory()->for($owner, 'owner')->withoutLocation()->create(['name' => 'Draft']);
        Room::factory()->for($draft)->create();
        $ready = Hotel::factory()->for($owner, 'owner')->create(['name' => 'Ready']);
        Room::factory()->for($ready)->create();
        Image::factory()->create(['imageable_id' => $ready->id]);
        Hotel::factory()->for($owner, 'owner')->visible()->blocked()->create(['name' => 'Blocked']);
        Hotel::factory()->create(['name' => 'Someone else’s']);

        $items = collect(app(CalculateDashboardStats::class)->ownerAttention($owner))
            ->map(fn (array $item): array => collect($item)->except('href')->all());

        expect($items->all())->toBe([
            ['type' => 'unanswered_reviews', 'hotel' => 'Reviewed', 'count' => 2],
            ['type' => 'blocked_hotel', 'hotel' => 'Blocked', 'reason' => 'Contenido inapropiado'],
            ['type' => 'incomplete_hotel', 'hotel' => 'Draft', 'missing' => ['photo', 'location']],
            ['type' => 'hidden_hotel', 'hotel' => 'Ready'],
        ]);
    });

    it('shows admins only the queues that have something in them', function () {
        Review::factory()->reported()->count(2)->create();
        Reservation::factory()->refundFailed()->create();

        $items = app(CalculateDashboardStats::class)->adminAttention();

        expect(array_column($items, 'count', 'type'))->toBe(['reported_reviews' => 2, 'failed_refunds' => 1])
            ->and($items[1]['href'])->toBe(route('manage.reservations.index', ['refund_failed' => 1]));
    });
});

it('ranks hotels by revenue and counts stays per province over the last twelve months', function () {
    $big = Hotel::factory()->visible()->create(['name' => 'Big', 'province' => 'malaga']);
    $small = Hotel::factory()->visible()->create(['name' => 'Small', 'province' => 'cadiz']);
    dashboardStay($big, '2026-07-01', total: 90000);
    dashboardStay($big, '2026-08-01', total: 10000);
    dashboardStay($small, '2026-09-01', total: 20000);
    dashboardStay($small, '2025-09-01', total: 500000);

    $stats = app(CalculateDashboardStats::class);

    expect($stats->topHotels())->toBe([
        ['hotel' => 'Big', 'revenue' => 100000, 'stays' => 2],
        ['hotel' => 'Small', 'revenue' => 20000, 'stays' => 1],
    ])
        ->and(array_column($stats->staysByProvince(), 'stays', 'province'))->toBe([
            'Huelva' => 0, 'Cádiz' => 1, 'Málaga' => 2, 'Granada' => 0, 'Almería' => 0,
        ]);
});
