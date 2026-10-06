<?php

namespace Database\Seeders\Demo;

use App\Actions\Pricing\CalculateStayPrice;
use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * A year of bookings and the next three months, walking each room's
 * calendar forward so stays never overlap. Rows are inserted directly:
 * no emails are sent and Stripe is never called (its ids are fake).
 */
class ReservationSeeder extends Seeder
{
    private const HISTORY_DAYS = 365;

    private const HORIZON_DAYS = 90;

    /**
     * How likely a free room is to be booked on a given day, by month. The
     * hotel's popularity scales it.
     *
     * @var array<int, float>
     */
    private const SEASON = [
        1 => 0.15, 2 => 0.15, 3 => 0.25, 4 => 0.4, 5 => 0.45, 6 => 0.6,
        7 => 0.8, 8 => 0.85, 9 => 0.6, 10 => 0.35, 11 => 0.15, 12 => 0.25,
    ];

    /**
     * Reasons typed by hotels that had to cancel a booking.
     *
     * @var array<string, list<string>>
     */
    private const HOTEL_CANCELLATION_REASONS = [
        'es' => [
            'Avería en la habitación que no podemos reparar a tiempo. Disculpa las molestias.',
            'Obras urgentes en esa planta del hotel. Te hemos devuelto el importe completo.',
            'Overbooking por un error en nuestro sistema. Lo sentimos mucho.',
        ],
        'en' => [
            'A leak in the room that cannot be repaired in time. We are very sorry.',
            'Urgent maintenance on that floor. You have received a full refund.',
        ],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const SPECIAL_REQUESTS = [
        'es' => [
            'Llegaremos tarde, sobre las 23:00.',
            '¿Sería posible una cuna para el bebé?',
            'Celebramos nuestro aniversario, si pudiera ser una habitación con vistas.',
            'Preferimos una habitación tranquila, lejos del ascensor.',
            'Viajamos con un perro pequeño.',
        ],
        'en' => [
            'We will arrive late, around 11 pm.',
            'Could we have a quiet room, please?',
            'It is our honeymoon!',
        ],
    ];

    private CarbonImmutable $today;

    /** @var Collection<int, User> Customers who book at random (not the demo account). */
    private Collection $customers;

    /** @var array<int, list<array{string, string}>> Check-in and check-out of each customer's stays, so they never overlap. */
    private array $trips = [];

    /** @var array<int, array<string, mixed>> */
    private array $rows = [];

    public function __construct(private CalculateStayPrice $calculateStayPrice) {}

    public function run(): void
    {
        $this->today = Reservation::today();
        $this->customers = User::query()
            ->where('role', UserRole::Customer)
            ->whereNot('email', 'customer@example.com')
            ->get();

        $hotels = collect(DemoSeeder::hotels())->keyBy('name');

        Hotel::query()
            ->with(['owner', 'offers', 'rooms' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->get()
            ->each(function (Hotel $hotel) use ($hotels): void {
                $data = $hotels[$hotel->name];

                if ($data['status'] === 'draft') {
                    return;
                }

                foreach ($hotel->rooms as $index => $room) {
                    // The demo owner always has guests arriving today.
                    $arrivalToday = $index === 0 && $hotel->owner->email === 'owner@example.com';

                    $this->fillCalendar($hotel, $room, $data['popularity'], $data['language'], $arrivalToday);
                }
            });

        $this->giveDemoCustomerOneOfEach();
        $this->failOneRefund();

        foreach (array_chunk($this->rows, 200) as $chunk) {
            Reservation::insert($chunk);
        }
    }

    private function fillCalendar(Hotel $hotel, Room $room, float $popularity, string $language, bool $arrivalToday): void
    {
        $opensOn = CarbonImmutable::parse($hotel->created_at?->toDateString())->addDays(2);
        $start = $opensOn->max($this->today->subDays(self::HISTORY_DAYS))->addDays(fake()->numberBetween(0, 6));
        $end = $this->today->addDays(self::HORIZON_DAYS);

        if (! $arrivalToday) {
            $this->fillBetween($hotel, $room, $start, $end, $popularity, $language);

            return;
        }

        $checkOut = $this->today->addDays(fake()->numberBetween(2, 4));

        $this->fillBetween($hotel, $room, $start, $this->today, $popularity, $language);
        $this->rows[] = $this->reservation($hotel, $room, $this->today, $checkOut, $language, ReservationStatus::Confirmed);
        $this->fillBetween($hotel, $room, $checkOut, $end, $popularity, $language);
    }

    /**
     * Place stays from $from, each one starting on or after the previous
     * check-out and ending by $until. Bookings get rarer further ahead.
     */
    private function fillBetween(Hotel $hotel, Room $room, CarbonImmutable $from, CarbonImmutable $until, float $popularity, string $language): void
    {
        $day = $from;

        while ($day->lt($until)) {
            $daysAhead = (int) $this->today->diffInDays($day, absolute: false);
            $chance = self::SEASON[$day->month] * $popularity * ($daysAhead > 0 ? max(0.1, 1 - $daysAhead / 100) : 1);

            if (! fake()->boolean((int) round($chance * 100))) {
                $day = $day->addDays(fake()->numberBetween(2, 6));

                continue;
            }

            $nights = in_array($day->month, [6, 7, 8, 9], true) ? fake()->numberBetween(3, 7) : fake()->numberBetween(2, 4);
            $checkOut = $day->addDays($nights);

            if ($checkOut->gt($until)) {
                return;
            }

            $this->rows[] = $this->reservation($hotel, $room, $day, $checkOut, $language);
            $day = $checkOut->addDays(fake()->numberBetween(0, 3));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function reservation(Hotel $hotel, Room $room, CarbonImmutable $checkIn, CarbonImmutable $checkOut, string $language, ?ReservationStatus $status = null): array
    {
        $bookedAt = $this->bookingTime($hotel, $checkIn);
        $customer = $this->customerFor($bookedAt, $checkIn, $checkOut);
        $price = $this->calculateStayPrice->handle($room, $checkIn, $checkOut, $hotel->offers);
        $status ??= $this->randomStatus($checkOut);
        $guests = $this->guests($room->capacity);

        $row = [
            'code' => 'RDM-'.fake()->unique()->regexify('[A-Z0-9]{6}'),
            'user_id' => $customer->id,
            'hotel_id' => $hotel->id,
            'room_id' => $room->id,
            'check_in' => $checkIn->toDateTimeString(),
            'check_out' => $checkOut->toDateTimeString(),
            'adults' => $guests['adults'],
            'children' => $guests['children'],
            'guest_name' => fake()->boolean(90) ? $customer->name : fake()->name(),
            'guest_phone' => $customer->locale === 'en' ? fake()->numerify('+44 7### ######') : fake()->numerify('6## ### ###'),
            'special_requests' => fake()->boolean(15) ? fake()->randomElement(self::SPECIAL_REQUESTS[$customer->locale ?? 'es']) : null,
            'status' => ReservationStatus::Confirmed->value,
            'price_breakdown' => json_encode($price->nights),
            'subtotal' => $price->subtotal,
            'discount' => $price->discount,
            'total_price' => $price->total,
            'stripe_checkout_session_id' => 'cs_demo_'.fake()->regexify('[a-zA-Z0-9]{24}'),
            'stripe_payment_intent_id' => 'pi_demo_'.fake()->regexify('[a-zA-Z0-9]{24}'),
            'expires_at' => $bookedAt->addMinutes(Reservation::PAYMENT_WINDOW_MINUTES)->toDateTimeString(),
            'paid_at' => $bookedAt->addMinutes(fake()->numberBetween(2, 15))->toDateTimeString(),
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancellation_reason' => null,
            'refund_amount' => 0,
            'refund_status' => null,
            'stripe_refund_id' => null,
            'refunded_at' => null,
            'created_at' => $bookedAt->toDateTimeString(),
            'updated_at' => $bookedAt->toDateTimeString(),
        ];

        return match ($status) {
            ReservationStatus::Expired => $this->expire($row),
            ReservationStatus::Cancelled => $this->cancel($row, $hotel, $checkIn, $bookedAt, $language),
            default => $row,
        };
    }

    private function randomStatus(CarbonImmutable $checkOut): ReservationStatus
    {
        $roll = fake()->numberBetween(1, 100);
        $isPast = $checkOut->lte($this->today);

        return match (true) {
            $roll <= ($isPast ? 89 : 91) => ReservationStatus::Confirmed,
            $roll <= ($isPast ? 96 : 97) => ReservationStatus::Cancelled,
            default => ReservationStatus::Expired,
        };
    }

    /**
     * Booked between a day and two and a half months before arriving, never
     * before the hotel joined and never in the future.
     */
    private function bookingTime(Hotel $hotel, CarbonImmutable $checkIn): CarbonImmutable
    {
        $bookedAt = $checkIn
            ->subDays(fake()->numberBetween(1, 75))
            ->setTime(fake()->numberBetween(8, 23), fake()->numberBetween(0, 59));

        return CarbonImmutable::parse($bookedAt->toDateTimeString())
            ->max(CarbonImmutable::parse($hotel->created_at?->toDateTimeString())->addHour())
            ->min(now()->subMinutes(45));
    }

    /**
     * A customer who had already signed up when booking and is not away on
     * another trip those nights. A deactivated one only appears in stays
     * that ended before the deactivation.
     */
    private function customerFor(CarbonImmutable $bookedAt, CarbonImmutable $checkIn, CarbonImmutable $checkOut): User
    {
        $candidates = $this->customers
            ->filter(fn (User $customer): bool => ($customer->created_at?->lte($bookedAt) ?? false)
                && ($customer->deactivated_at === null || $checkOut->lt($customer->deactivated_at)))
            ->all();
        [$from, $to] = [$checkIn->toDateString(), $checkOut->toDateString()];

        for ($attempt = 0; $attempt < 50; $attempt++) {
            /** @var User $customer */
            $customer = fake()->randomElement($candidates);
            $isAway = collect($this->trips[$customer->id] ?? [])->contains(fn (array $trip): bool => $trip[0] < $to && $trip[1] > $from);

            if (! $isAway) {
                break;
            }
        }

        $this->trips[$customer->id][] = [$from, $to];

        return $customer;
    }

    /**
     * @return array{adults: int, children: int}
     */
    private function guests(int $capacity): array
    {
        if ($capacity === 1) {
            return ['adults' => 1, 'children' => 0];
        }

        $adults = fake()->boolean(85) ? 2 : 1;

        return ['adults' => $adults, 'children' => fake()->numberBetween(0, $capacity - $adults)];
    }

    /**
     * Never paid: the Stripe page was abandoned.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function expire(array $row): array
    {
        return [
            ...$row,
            'status' => ReservationStatus::Expired->value,
            'stripe_payment_intent_id' => null,
            'paid_at' => null,
            'updated_at' => $row['expires_at'],
        ];
    }

    /**
     * Paid and later cancelled, usually by the guest (refunding what the
     * hotel's policy says for that many days before check-in), sometimes by
     * the hotel (always a full refund, with a reason).
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function cancel(array $row, Hotel $hotel, CarbonImmutable $checkIn, CarbonImmutable $bookedAt, string $language): array
    {
        $earliest = $bookedAt->addHour();
        $latest = $checkIn->setTime(11, 0)->min(now()->subHour());

        if ($latest->lte($earliest)) {
            return $row;
        }

        $cancelledAt = $earliest->addMinutes(fake()->numberBetween(0, (int) $earliest->diffInMinutes($latest)));
        $byHotel = fake()->boolean(15);
        $daysBefore = (int) CarbonImmutable::parse($cancelledAt->toDateString())->diffInDays($checkIn->toDateString());
        $percent = $byHotel ? 100 : $this->refundPercent($hotel, $daysBefore);
        $refund = (int) round($row['total_price'] * $percent / 100);

        return [
            ...$row,
            'status' => ReservationStatus::Cancelled->value,
            'cancelled_at' => $cancelledAt->toDateTimeString(),
            'cancelled_by' => $byHotel ? $hotel->owner_id : $row['user_id'],
            'cancellation_reason' => $byHotel ? fake()->randomElement(self::HOTEL_CANCELLATION_REASONS[$language]) : null,
            'refund_amount' => $refund,
            'refund_status' => $refund > 0 ? RefundStatus::Succeeded->value : null,
            'stripe_refund_id' => $refund > 0 ? 're_demo_'.fake()->regexify('[a-zA-Z0-9]{24}') : null,
            'refunded_at' => $refund > 0 ? $cancelledAt->addMinutes(2)->toDateTimeString() : null,
            'updated_at' => $cancelledAt->addMinutes(2)->toDateTimeString(),
        ];
    }

    /**
     * The same tiers CalculateRefund applies, counted from the day the guest
     * cancelled instead of from today.
     */
    private function refundPercent(Hotel $hotel, int $daysBefore): int
    {
        foreach ($hotel->cancellation_policy as $tier) {
            if ($daysBefore >= $tier['days_before']) {
                return $tier['refund_percent'];
            }
        }

        return 0;
    }

    /**
     * The demo customer gets a short, readable history: two upcoming stays
     * (one at the demo owner's hotel), a recent one still to review, older
     * ones and a booking they cancelled. The date windows never overlap.
     */
    private function giveDemoCustomerOneOfEach(): void
    {
        $demoCustomer = User::where('email', 'customer@example.com')->firstOrFail();
        $demoHotelId = Hotel::where('name', 'Hotel Mirador de Burriana')->value('id');
        $confirmed = ReservationStatus::Confirmed->value;
        $between = fn (mixed $date, int $fromDay, int $toDay): bool => $date >= $this->today->addDays($fromDay)->toDateTimeString()
            && $date <= $this->today->addDays($toDay)->toDateTimeString();

        $picks = [
            fn (array $row): bool => $row['status'] === $confirmed && $row['hotel_id'] === $demoHotelId && $between($row['check_in'], 10, 40),
            fn (array $row): bool => $row['status'] === $confirmed && $row['hotel_id'] !== $demoHotelId && $between($row['check_in'], 50, 85),
            fn (array $row): bool => $row['status'] === $confirmed && $between($row['check_out'], -20, 0),
            fn (array $row): bool => $row['status'] === ReservationStatus::Cancelled->value
                && $row['cancelled_by'] === $row['user_id'] && $between($row['check_in'], -80, -50),
            fn (array $row): bool => $row['status'] === $confirmed && $between($row['check_out'], -150, -100),
            fn (array $row): bool => $row['status'] === $confirmed && $between($row['check_out'], -300, -220),
        ];

        foreach ($picks as $matches) {
            $index = fake()->randomElement(array_keys(array_filter($this->rows, $matches)));
            $row = $this->rows[$index];

            $this->rows[$index] = [
                ...$row,
                'user_id' => $demoCustomer->id,
                'cancelled_by' => $row['cancelled_by'] === null ? null : $demoCustomer->id,
                'guest_name' => $demoCustomer->name,
                'guest_phone' => '612 345 678',
            ];
        }
    }

    /**
     * The most recent refund failed, so admins find one to retry.
     */
    private function failOneRefund(): void
    {
        $latest = collect($this->rows)
            ->filter(fn (array $row): bool => $row['refund_status'] === RefundStatus::Succeeded->value)
            ->sortByDesc('cancelled_at')
            ->keys()
            ->first();

        if ($latest !== null) {
            $this->rows[$latest] = [
                ...$this->rows[$latest],
                'refund_status' => RefundStatus::Failed->value,
                'stripe_refund_id' => null,
                'refunded_at' => null,
            ];
        }
    }
}
