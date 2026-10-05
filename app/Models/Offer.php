<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A percentage discount on the nights between two dates (both inclusive),
 * for the whole hotel (room_type_id null) or for one room type.
 *
 * @property int $id
 * @property int $hotel_id
 * @property int|null $room_type_id
 * @property string $title
 * @property int $discount_percent
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['room_type_id', 'title', 'discount_percent', 'starts_on', 'ends_on', 'is_active'])]
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_percent' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Active offers covering at least one night between the given dates.
     *
     * @param  Builder<Offer>  $query
     */
    #[Scope]
    protected function activeBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): void
    {
        $query->where('is_active', true)
            ->whereDate('starts_on', '<=', $to)
            ->whereDate('ends_on', '>=', $from);
    }

    /**
     * Whether the offer discounts the given night for the given room type.
     */
    public function appliesTo(CarbonInterface $night, int $roomTypeId): bool
    {
        return $this->is_active
            && ($this->room_type_id === null || $this->room_type_id === $roomTypeId)
            && $night->betweenIncluded($this->starts_on->startOfDay(), $this->ends_on->startOfDay());
    }

    /**
     * Display status: active, upcoming, finished or disabled.
     */
    public function status(): string
    {
        $today = today();

        return match (true) {
            ! $this->is_active => 'disabled',
            $this->ends_on->lt($today) => 'finished',
            $this->starts_on->gt($today) => 'upcoming',
            default => 'active',
        };
    }

    /**
     * @return BelongsTo<Hotel, $this>
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
