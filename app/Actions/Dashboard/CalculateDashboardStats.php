<?php

namespace App\Actions\Dashboard;

use App\Enums\Province;
use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The numbers of the management dashboard. Owners see their own hotels and
 * admins the whole platform.
 *
 * Revenue is what guests paid minus what was refunded to them, counted in
 * the month of the stay's check-in (a cancellation without refund still
 * counts; one refunded in full does not).
 */
class CalculateDashboardStats
{
    public const CHART_MONTHS = 12;

    public const OCCUPANCY_DAYS = 30;

    public const ARRIVAL_DAYS = 7;

    public const MAX_ARRIVALS = 8;

    public const TOP_HOTELS = 5;

    /**
     * Revenue of this month and of the previous one.
     *
     * @return array{current: int, previous: int}
     */
    public function revenue(User $user): array
    {
        $month = $this->today()->startOfMonth();

        return [
            'current' => $this->revenueBetween($user, $month, $month->endOfMonth()),
            'previous' => $this->revenueBetween($user, $month->subMonth(), $month->subDay()),
        ];
    }

    /**
     * Revenue and confirmed stays by check-in month, oldest first, for the
     * last twelve months including the current one.
     *
     * @return list<array{month: string, revenue: int, stays: int}>
     */
    public function monthlyRevenue(User $user): array
    {
        $firstMonth = $this->chartStart();

        $byMonth = $this->paidReservations($user)
            ->whereDate('check_in', '>=', $firstMonth)
            ->whereDate('check_in', '<=', $this->today()->endOfMonth())
            ->get(['check_in', 'status', 'total_price', 'refund_amount'])
            ->groupBy(fn (Reservation $reservation): string => $reservation->check_in->format('Y-m'));

        return array_map(function (int $offset) use ($firstMonth, $byMonth): array {
            $month = $firstMonth->addMonths($offset)->format('Y-m');
            $reservations = $byMonth->get($month, collect());

            return [
                'month' => $month,
                'revenue' => (int) $reservations->sum(fn (Reservation $reservation): int => $reservation->total_price - $reservation->refund_amount),
                'stays' => $reservations->where('status', ReservationStatus::Confirmed)->count(),
            ];
        }, range(0, self::CHART_MONTHS - 1));
    }

    /**
     * Share of the nights of the next 30 days already booked, over the
     * active rooms of published hotels.
     *
     * @return array{percent: int, booked_nights: int, available_nights: int}
     */
    public function occupancy(User $user): array
    {
        $from = $this->today();
        $until = $from->addDays(self::OCCUPANCY_DAYS);

        $roomIds = Room::query()
            ->where('is_active', true)
            ->whereIn('hotel_id', $this->hotels($user)->published()->select('id'))
            ->pluck('id');

        $bookedNights = (int) Reservation::query()
            ->blocking()
            ->whereIn('room_id', $roomIds)
            ->overlapping($from, $until)
            ->get(['check_in', 'check_out'])
            ->sum(function (Reservation $reservation) use ($from, $until): int {
                $checkIn = CarbonImmutable::parse($reservation->check_in->toDateString())->max($from);
                $checkOut = CarbonImmutable::parse($reservation->check_out->toDateString())->min($until);

                return (int) $checkIn->diffInDays($checkOut);
            });

        $availableNights = $roomIds->count() * self::OCCUPANCY_DAYS;

        return [
            'percent' => $availableNights > 0 ? (int) round($bookedNights * 100 / $availableNights) : 0,
            'booked_nights' => $bookedNights,
            'available_nights' => $availableNights,
        ];
    }

    /**
     * Confirmed guests arriving today and over the next seven days.
     *
     * @return array{today: int, week: int}
     */
    public function arrivalCounts(User $user): array
    {
        return [
            'today' => $this->arrivals($user)->whereDate('check_in', $this->today())->count(),
            'week' => $this->arrivals($user)->count(),
        ];
    }

    /**
     * The next confirmed arrivals, soonest first.
     *
     * @return list<array{code: string, guest_name: string, guests: int, hotel: string, room: string, check_in: string, nights: int}>
     */
    public function upcomingArrivals(User $user): array
    {
        $arrivals = $this->arrivals($user)
            ->with(['hotel', 'room'])
            ->orderBy('check_in')
            ->orderBy('id')
            ->limit(self::MAX_ARRIVALS)
            ->get();

        return array_values($arrivals->map(fn (Reservation $reservation): array => [
            'code' => $reservation->code,
            'guest_name' => $reservation->guest_name,
            'guests' => $reservation->adults + $reservation->children,
            'hotel' => $reservation->hotel->name,
            'room' => $reservation->room->name,
            'check_in' => $reservation->check_in->toDateString(),
            'nights' => $reservation->nights(),
        ])->all());
    }

    /**
     * The guests' average rating of the user's hotels (removed reviews
     * never count).
     *
     * @return array{average: float|null, count: int}
     */
    public function rating(User $user): array
    {
        $reviews = Review::query()->whereIn('hotel_id', $this->hotels($user)->select('id'));
        $count = (clone $reviews)->count();

        return [
            'average' => $count > 0 ? round((float) $reviews->avg('rating'), 1) : null,
            'count' => $count,
        ];
    }

    /**
     * Bookings paid this month and the previous one, across the platform.
     *
     * @return array{current: int, previous: int}
     */
    public function bookings(): array
    {
        [$month, $previousMonth] = $this->monthStarts();

        return [
            'current' => Reservation::where('paid_at', '>=', $month)->count(),
            'previous' => Reservation::where('paid_at', '>=', $previousMonth)->where('paid_at', '<', $month)->count(),
        ];
    }

    /**
     * @return array{published: int, total: int}
     */
    public function hotelCounts(): array
    {
        return [
            'published' => Hotel::published()->count(),
            'total' => Hotel::count(),
        ];
    }

    /**
     * Accounts created this month and the previous one.
     *
     * @return array{current: int, previous: int}
     */
    public function newUsers(): array
    {
        [$month, $previousMonth] = $this->monthStarts();

        return [
            'current' => User::where('created_at', '>=', $month)->count(),
            'previous' => User::where('created_at', '>=', $previousMonth)->where('created_at', '<', $month)->count(),
        ];
    }

    /**
     * What an owner should look at: reviews waiting for a reply, hotels
     * that are not public yet (and what they lack) and blocked hotels.
     *
     * @return list<array<string, mixed>>
     */
    public function ownerAttention(User $user): array
    {
        $items = [];

        $withoutReply = fn (Builder $query) => $query->whereNull('reply');
        $unanswered = $this->hotels($user)
            ->whereHas('reviews', $withoutReply)
            ->withCount(['reviews' => $withoutReply])
            ->orderByDesc('reviews_count')
            ->get();

        foreach ($unanswered as $hotel) {
            $items[] = [
                'type' => 'unanswered_reviews',
                'hotel' => $hotel->name,
                'count' => (int) $hotel->reviews_count,
                'href' => route('manage.hotels.reviews.index', $hotel),
            ];
        }

        $notPublic = $this->hotels($user)
            ->where(fn (Builder $query) => $query->where('is_visible', false)->orWhereNotNull('blocked_at'))
            ->orderBy('name')
            ->get();

        foreach ($notPublic as $hotel) {
            $items[] = match (true) {
                $hotel->isBlocked() => ['type' => 'blocked_hotel', 'hotel' => $hotel->name, 'reason' => $hotel->blocked_reason],
                $hotel->canBePublished() => ['type' => 'hidden_hotel', 'hotel' => $hotel->name],
                default => ['type' => 'incomplete_hotel', 'hotel' => $hotel->name, 'missing' => $this->missingToPublish($hotel)],
            } + ['href' => route('manage.hotels.edit', $hotel)];
        }

        return $items;
    }

    /**
     * What an admin should look at: reported reviews, refunds Stripe could
     * not make and blocked hotels. Only the non-empty ones.
     *
     * @return list<array{type: string, count: int, href: string}>
     */
    public function adminAttention(): array
    {
        $items = [
            ['type' => 'reported_reviews', 'count' => Review::whereNotNull('reported_at')->count(), 'href' => route('admin.reviews.index')],
            ['type' => 'failed_refunds', 'count' => Reservation::where('refund_status', RefundStatus::Failed)->count(), 'href' => route('manage.reservations.index', ['refund_failed' => 1])],
            ['type' => 'blocked_hotels', 'count' => Hotel::whereNotNull('blocked_at')->count(), 'href' => route('admin.hotels.index', ['status' => 'blocked'])],
        ];

        return array_values(array_filter($items, fn (array $item): bool => $item['count'] > 0));
    }

    /**
     * The hotels with the highest revenue over the chart's twelve months.
     *
     * @return list<array{hotel: string, revenue: int, stays: int}>
     */
    public function topHotels(): array
    {
        $totals = $this->paidReservations(null)
            ->whereDate('check_in', '>=', $this->chartStart())
            ->selectRaw('hotel_id, sum(total_price - refund_amount) as revenue, sum(case when status = ? then 1 else 0 end) as stays', [ReservationStatus::Confirmed->value])
            ->groupBy('hotel_id')
            ->orderByDesc('revenue')
            ->limit(self::TOP_HOTELS)
            ->toBase()
            ->get();

        $names = Hotel::withTrashed()->whereIn('id', $totals->pluck('hotel_id'))->pluck('name', 'id');

        return array_values($totals->map(fn (object $row): array => [
            'hotel' => (string) $names[$row->hotel_id],
            'revenue' => (int) $row->revenue,
            'stays' => (int) $row->stays,
        ])->all());
    }

    /**
     * Confirmed stays over the chart's twelve months in each province.
     *
     * @return list<array{province: string, stays: int}>
     */
    public function staysByProvince(): array
    {
        $stays = Reservation::query()
            ->join('hotels', 'hotels.id', '=', 'reservations.hotel_id')
            ->where('reservations.status', ReservationStatus::Confirmed)
            ->whereDate('reservations.check_in', '>=', $this->chartStart())
            ->selectRaw('hotels.province, count(*) as stays')
            ->groupBy('hotels.province')
            ->toBase()
            ->pluck('stays', 'province');

        return array_map(fn (Province $province): array => [
            'province' => $province->label(),
            'stays' => (int) ($stays[$province->value] ?? 0),
        ], Province::cases());
    }

    /**
     * @return list<string>
     */
    private function missingToPublish(Hotel $hotel): array
    {
        return array_keys(array_filter([
            'active_room' => ! $hotel->hasActiveRooms(),
            'photo' => ! $hotel->images()->exists(),
            'location' => ! $hotel->hasLocation(),
        ]));
    }

    /**
     * Hotels the user manages: every hotel for admins, their own for owners.
     *
     * @return Builder<Hotel>
     */
    private function hotels(User $user): Builder
    {
        return Hotel::query()->when(! $user->isAdmin(), fn (Builder $query) => $query->where('owner_id', $user->id));
    }

    /**
     * Paid reservations (confirmed, or cancelled after paying) of the
     * user's hotels, deleted hotels included, or of every hotel.
     *
     * @return Builder<Reservation>
     */
    private function paidReservations(?User $user): Builder
    {
        return Reservation::query()
            ->whereNotNull('paid_at')
            ->when($user !== null && ! $user->isAdmin(), fn (Builder $query) => $query->whereIn(
                'hotel_id',
                Hotel::withTrashed()->where('owner_id', $user?->id)->select('id'),
            ));
    }

    private function revenueBetween(User $user, CarbonImmutable $from, CarbonImmutable $until): int
    {
        return (int) $this->paidReservations($user)
            ->whereDate('check_in', '>=', $from)
            ->whereDate('check_in', '<=', $until)
            ->sum(DB::raw('total_price - refund_amount'));
    }

    /**
     * @return Builder<Reservation>
     */
    private function arrivals(User $user): Builder
    {
        return Reservation::query()
            ->whereIn('hotel_id', $this->hotels($user)->select('id'))
            ->where('status', ReservationStatus::Confirmed)
            ->whereDate('check_in', '>=', $this->today())
            ->whereDate('check_in', '<', $this->today()->addDays(self::ARRIVAL_DAYS));
    }

    /**
     * The start of this month and of the previous one where the hotels are,
     * in UTC to compare with stored timestamps.
     *
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function monthStarts(): array
    {
        $month = Reservation::today()->startOfMonth();

        return [$month->utc(), $month->subMonth()->utc()];
    }

    private function chartStart(): CarbonImmutable
    {
        return $this->today()->startOfMonth()->subMonths(self::CHART_MONTHS - 1);
    }

    /**
     * Today where the hotels are, as a plain date.
     */
    private function today(): CarbonImmutable
    {
        return CarbonImmutable::parse(Reservation::today()->toDateString());
    }
}
