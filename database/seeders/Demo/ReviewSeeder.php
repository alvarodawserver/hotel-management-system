<?php

namespace Database\Seeders\Demo;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Guests' reviews of finished stays: each hotel's ratings tend to its
 * average in the demo data, the owners reply to many of them (never to the
 * last two weeks', so there is always something to answer), two are
 * waiting for moderation and one was removed by an admin.
 *
 * @phpstan-import-type ReviewTexts from DemoSeeder
 */
class ReviewSeeder extends Seeder
{
    /** The share of finished stays that get a review, up to a maximum per hotel. */
    private const REVIEW_RATE = 0.3;

    private const MAX_PER_HOTEL = 18;

    /** @var ReviewTexts */
    private array $texts;

    /** @var array<string, list<string>> Comments still unused at the current hotel, by language and tone. */
    private array $unusedComments = [];

    private CarbonImmutable $today;

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    public function run(): void
    {
        $this->texts = DemoSeeder::reviewTexts();
        $this->today = Reservation::today();
        $hotels = collect(DemoSeeder::hotels())->keyBy('name');
        $demoCustomer = User::where('email', 'customer@example.com')->firstOrFail();

        foreach (Hotel::all() as $hotel) {
            $this->unusedComments = [];

            // The demo customer's stays are handled apart, so one is left to review.
            $stays = $this->finishedStays($hotel)
                ->reject(fn (Reservation $reservation): bool => $reservation->user_id === $demoCustomer->id)
                ->all();
            $reviewed = array_slice(fake()->shuffleArray($stays), 0, min(self::MAX_PER_HOTEL, (int) round(count($stays) * self::REVIEW_RATE)));

            foreach ($reviewed as $reservation) {
                $this->rows[] = $this->review($reservation, $hotels[$hotel->name]['rating'], $hotels[$hotel->name]['language']);
            }
        }

        $this->reviewOneDemoCustomerStay($demoCustomer);
        $this->reportTwoReviews();
        $this->removeOneReview();

        foreach (array_chunk($this->rows, 200) as $chunk) {
            Review::insert($chunk);
        }
    }

    /**
     * Confirmed stays whose check-out has passed, oldest first.
     *
     * @return Collection<int, Reservation>
     */
    private function finishedStays(Hotel $hotel): Collection
    {
        return $hotel->reservations()
            ->with('user')
            ->where('status', ReservationStatus::Confirmed)
            ->whereDate('check_out', '<=', $this->today)
            ->orderBy('check_out')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function review(Reservation $reservation, float $hotelRating, string $hotelLanguage, ?int $rating = null): array
    {
        $rating ??= $this->ratingAround($hotelRating);
        $writtenAt = $this->writtenAt($reservation);
        $replyAt = $writtenAt->addDays(fake()->numberBetween(1, 4))->setTime(fake()->numberBetween(9, 20), fake()->numberBetween(0, 59));
        $replied = $writtenAt->lt(now()->subDays(14)) && fake()->boolean(55);
        $tone = $rating >= 4 ? 'positive' : 'critical';

        return [
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'hotel_id' => $reservation->hotel_id,
            'rating' => $rating,
            'comment' => $this->comment($reservation->user->locale ?? 'es', $rating),
            'edited_at' => fake()->boolean(5) ? $writtenAt->addDay()->toDateTimeString() : null,
            'reply' => $replied ? fake()->randomElement($this->texts['replies'][$hotelLanguage][$tone]) : null,
            'replied_at' => $replied ? $replyAt->toDateTimeString() : null,
            'reported_at' => null,
            'reported_by' => null,
            'report_reason' => null,
            'deleted_at' => null,
            'deleted_by' => null,
            'deletion_reason' => null,
            'created_at' => $writtenAt->toDateTimeString(),
            'updated_at' => $writtenAt->toDateTimeString(),
        ];
    }

    /**
     * A rating near the hotel's average: the sum of two random numbers
     * spreads it up to about a point either way, more often close to it.
     */
    private function ratingAround(float $average): int
    {
        $spread = fake()->randomFloat(2, 0, 1) + fake()->randomFloat(2, 0, 1) - 1;

        return max(1, min(5, (int) round($average + $spread * 1.3)));
    }

    /**
     * Between the check-out day and ten days later; for stays that just
     * ended, some time in the last few hours instead of in the future.
     */
    private function writtenAt(Reservation $reservation): CarbonImmutable
    {
        $checkOut = CarbonImmutable::parse($reservation->check_out->toDateString());
        $writtenAt = $checkOut
            ->addDays(fake()->numberBetween(0, 10))
            ->setTime(fake()->numberBetween(9, 22), fake()->numberBetween(0, 59));

        return $writtenAt->lt(now()->subHour())
            ? $writtenAt
            : now()->subMinutes(fake()->numberBetween(60, 600))->max($checkOut);
    }

    /**
     * A hand-written comment in the guest's language that matches the
     * rating, without repeating one at the same hotel until all are used.
     */
    private function comment(string $language, int $rating): string
    {
        $tone = match (true) {
            $rating >= 4 => 'positive',
            $rating === 3 => 'mixed',
            default => 'negative',
        };
        $key = "{$language}.{$tone}";
        $unused = $this->unusedComments[$key] ?? [];

        if ($unused === []) {
            $unused = $this->texts['comments'][$language][$tone];
        }

        $index = fake()->numberBetween(0, count($unused) - 1);
        $comment = $unused[$index];
        array_splice($unused, $index, 1);
        $this->unusedComments[$key] = $unused;

        return $comment;
    }

    /**
     * The demo customer has reviewed an older stay (with the hotel's reply)
     * and can still review their most recent one.
     */
    private function reviewOneDemoCustomerStay(User $demoCustomer): void
    {
        $stays = $demoCustomer->reservations()
            ->with(['user', 'hotel'])
            ->where('status', ReservationStatus::Confirmed)
            ->whereDate('check_out', '<=', $this->today)
            ->orderByDesc('check_out')
            ->get();

        $olderStay = $stays->get(1);

        if ($olderStay === null) {
            return;
        }

        $review = $this->review($olderStay, 5, 'es', rating: 5);
        $writtenAt = CarbonImmutable::parse($review['created_at']);
        $replyLanguage = $olderStay->hotel->owner?->locale === 'en' ? 'en' : 'es';

        $this->rows[] = [
            ...$review,
            'reply' => $this->texts['replies'][$replyLanguage]['positive'][0],
            'replied_at' => $writtenAt->addDay()->toDateTimeString(),
        ];
    }

    /**
     * The latest review of two of the demo owner's hotels, rude enough for
     * the owner to report them to the admins.
     */
    private function reportTwoReviews(): void
    {
        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $hotelIds = Hotel::whereIn('name', ['Hotel Mirador de Burriana', 'Casa Alcazaba'])->pluck('id');

        foreach ($this->texts['reported'] as $index => $reported) {
            $key = $this->latestRowIndex(fn (array $row): bool => $row['hotel_id'] === $hotelIds[$index]);
            $writtenAt = CarbonImmutable::parse($this->rows[$key]['created_at']);

            $this->rows[$key] = [
                ...$this->rows[$key],
                'rating' => $reported['rating'],
                'comment' => $reported['comment'],
                'edited_at' => null,
                'reply' => null,
                'replied_at' => null,
                'reported_at' => $writtenAt->addHours(6)->min(now())->toDateTimeString(),
                'reported_by' => $owner->id,
                'report_reason' => $reported['report_reason'],
            ];
        }
    }

    /**
     * An offensive review an admin already took down after the owner
     * reported it.
     */
    private function removeOneReview(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $hotel = Hotel::where('name', 'Villa Lentisco')->firstOrFail();
        $key = $this->latestRowIndex(fn (array $row): bool => $row['hotel_id'] === $hotel->id, skip: 3);
        $writtenAt = CarbonImmutable::parse($this->rows[$key]['created_at']);
        $removed = $this->texts['removed'];

        $this->rows[$key] = [
            ...$this->rows[$key],
            'rating' => $removed['rating'],
            'comment' => $removed['comment'],
            'reply' => null,
            'replied_at' => null,
            'reported_at' => $writtenAt->addHours(3)->toDateTimeString(),
            'reported_by' => $hotel->owner_id,
            'report_reason' => 'Insultos al propietario.',
            'deleted_at' => $writtenAt->addDay()->toDateTimeString(),
            'deleted_by' => $admin->id,
            'deletion_reason' => $removed['deletion_reason'],
        ];
    }

    /**
     * The index of the most recent review matching the filter, skipping
     * the given number of more recent ones.
     *
     * @param  callable(array<string, mixed>): bool  $matches
     */
    private function latestRowIndex(callable $matches, int $skip = 0): int
    {
        $key = collect($this->rows)
            ->filter($matches)
            ->sortByDesc('created_at')
            ->keys()
            ->get($skip);

        return $key ?? throw new RuntimeException('Not enough demo reviews to report or remove.');
    }
}
