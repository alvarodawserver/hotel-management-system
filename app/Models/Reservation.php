<?php

namespace App\Models;

use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A booking of one room for a range of nights. Prices are a snapshot in
 * euro cents taken when booking.
 *
 * @property int $id
 * @property string $code
 * @property int $user_id
 * @property int $hotel_id
 * @property int $room_id
 * @property Carbon $check_in
 * @property Carbon $check_out
 * @property int $adults
 * @property int $children
 * @property string $guest_name
 * @property string $guest_phone
 * @property string|null $special_requests
 * @property ReservationStatus $status
 * @property list<array{date: string, base: int, discount_percent: int, price: int}> $price_breakdown
 * @property int $subtotal
 * @property int $discount
 * @property int $total_price
 * @property string|null $stripe_checkout_session_id
 * @property string|null $stripe_payment_intent_id
 * @property Carbon|null $expires_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string|null $cancellation_reason
 * @property int $refund_amount
 * @property RefundStatus|null $refund_status
 * @property string|null $stripe_refund_id
 * @property Carbon|null $refunded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['check_in', 'check_out', 'adults', 'children', 'guest_name', 'guest_phone', 'special_requests'])]
class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    /** How long a pending reservation holds its room while the customer pays. */
    public const PAYMENT_WINDOW_MINUTES = 30;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'adults' => 'integer',
            'children' => 'integer',
            'status' => ReservationStatus::class,
            'price_breakdown' => 'array',
            'subtotal' => 'integer',
            'discount' => 'integer',
            'total_price' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refund_amount' => 'integer',
            'refund_status' => RefundStatus::class,
            'refunded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation): void {
            if (blank($reservation->code)) {
                $reservation->code = static::uniqueCode();
            }
        });
    }

    /**
     * A short code customers and hotels can read out, e.g. "RDM-7F3K2Q".
     */
    public static function uniqueCode(): string
    {
        do {
            $code = 'RDM-'.Str::upper(Str::random(6));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * Reservations that hold their room: confirmed ones and pending ones
     * still inside their payment window.
     *
     * @param  Builder<Reservation>  $query
     */
    #[Scope]
    protected function blocking(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('status', ReservationStatus::Confirmed)
            ->orWhere(fn (Builder $query) => $query
                ->where('status', ReservationStatus::Pending)
                ->where('expires_at', '>', now())));
    }

    /**
     * Reservations whose nights overlap the given stay. The check-out day of
     * one stay can be the check-in day of the next.
     *
     * @param  Builder<Reservation>  $query
     */
    #[Scope]
    protected function overlapping(Builder $query, CarbonInterface $checkIn, CarbonInterface $checkOut): void
    {
        $query->whereDate('check_in', '<', $checkOut)->whereDate('check_out', '>', $checkIn);
    }

    /**
     * Reservations still to be honoured: blocking ones whose check-out has
     * not passed. They prevent deleting hotels/rooms and deactivating users.
     *
     * @param  Builder<Reservation>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->blocking()->whereDate('check_out', '>=', static::today());
    }

    /**
     * Today's date where the hotels are, used for check-in/out rules.
     */
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.hotel_timezone'))->startOfDay();
    }

    public function isPending(): bool
    {
        return $this->status === ReservationStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === ReservationStatus::Confirmed;
    }

    /**
     * Pending reservations can still be paid until the window closes.
     */
    public function awaitsPayment(): bool
    {
        return $this->isPending() && $this->expires_at?->isFuture();
    }

    /**
     * Cancellation is possible up to (and including) the check-in day.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)
            && $this->check_in->toDateString() >= static::today()->toDateString();
    }

    /**
     * A confirmed stay can be reviewed from its check-out day, once. A review
     * removed by an admin still counts, so the stay cannot be reviewed again.
     */
    public function canBeReviewed(): bool
    {
        if (! $this->isConfirmed() || $this->check_out->toDateString() > static::today()->toDateString()) {
            return false;
        }

        return $this->relationLoaded('reviewIncludingRemoved')
            ? $this->reviewIncludingRemoved === null
            : ! $this->reviewIncludingRemoved()->exists();
    }

    public function nights(): int
    {
        return (int) $this->check_in->diffInDays($this->check_out);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Including soft-deleted hotels, so past reservations keep their data.
     *
     * @return BelongsTo<Hotel, $this>
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class)->withTrashed();
    }

    /**
     * The guest's review of the stay, excluding one removed by an admin.
     *
     * @return HasOne<Review, $this>
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * The guest's review even if an admin removed it, so its author can see
     * why, and the stay cannot be reviewed again.
     *
     * @return HasOne<Review, $this>
     */
    public function reviewIncludingRemoved(): HasOne
    {
        return $this->hasOne(Review::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
