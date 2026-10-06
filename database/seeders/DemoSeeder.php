<?php

namespace Database\Seeders;

use Database\Seeders\Demo\HotelSeeder;
use Database\Seeders\Demo\ReservationSeeder;
use Database\Seeders\Demo\ReviewSeeder;
use Database\Seeders\Demo\UserSeeder;
use Illuminate\Database\Seeder;

/**
 * A lived-in coast for the demo: owners and their hotels, a year of
 * bookings and the guests' reviews. Dates are relative to today, so the
 * demo always looks current, and the random choices use a fixed seed, so
 * every run produces the same data.
 *
 * @phpstan-type DemoHotel array{
 *     owner: string,
 *     name: string,
 *     province: \App\Enums\Province,
 *     municipality: string,
 *     address: string,
 *     latitude: float,
 *     longitude: float,
 *     stars: int,
 *     language: string,
 *     description: string,
 *     amenities: list<string>,
 *     categories: list<string>,
 *     cancellation_policy: list<array{days_before: int, refund_percent: int}>|null,
 *     status: 'published'|'draft'|'blocked',
 *     blocked_reason?: string,
 *     created_days_ago: int,
 *     popularity: float,
 *     rating: float,
 *     photos: list<string>,
 *     rooms: list<array{type: string, capacity: int, price: int, count: int}>,
 *     inactive_rooms?: int,
 *     activities: list<array{0: string, 1: string, 2: int, 3: string, 4: string, 5: int}>,
 *     offers: list<array{0: string, 1: int, 2: int, 3: int, 4: string|null, 5: bool}>,
 * }
 * @phpstan-type ReviewTexts array{
 *     comments: array<string, array<string, list<string>>>,
 *     replies: array<string, array<string, list<string>>>,
 *     reported: list<array{rating: int, comment: string, report_reason: string}>,
 *     removed: array{rating: int, comment: string, deletion_reason: string},
 * }
 */
class DemoSeeder extends Seeder
{
    public const RANDOM_SEED = 2026;

    public function run(): void
    {
        fake()->seed(self::RANDOM_SEED);

        $this->call([
            UserSeeder::class,
            HotelSeeder::class,
            ReservationSeeder::class,
            ReviewSeeder::class,
        ]);

        // The seed is global; leave randomness random for whatever runs next.
        fake()->seed();
    }

    /**
     * The hand-written hotels in database/seeders/data/hotels.php.
     *
     * @return list<DemoHotel>
     */
    public static function hotels(): array
    {
        return require database_path('seeders/data/hotels.php');
    }

    /**
     * The hand-written review texts in database/seeders/data/reviews.php.
     *
     * @return ReviewTexts
     */
    public static function reviewTexts(): array
    {
        return require database_path('seeders/data/reviews.php');
    }
}
