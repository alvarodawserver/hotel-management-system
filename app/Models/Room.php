<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $hotel_id
 * @property int $room_type_id
 * @property string $name
 * @property int $capacity
 * @property int $price_per_night Price in euro cents.
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['room_type_id', 'name', 'capacity', 'price_per_night', 'description', 'is_active'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory, SoftDeletes;

    public const MAX_IMAGES = 6;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'price_per_night' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Active rooms that can host the given number of guests and, when dates
     * are given, are free for the whole stay.
     *
     * @param  Builder<Room>  $query
     */
    #[Scope]
    protected function bookableFor(Builder $query, int $guests, ?CarbonInterface $checkIn = null, ?CarbonInterface $checkOut = null): void
    {
        $query->where('is_active', true)->where('capacity', '>=', $guests);

        if ($checkIn !== null && $checkOut !== null) {
            $query->availableBetween($checkIn, $checkOut);
        }
    }

    /**
     * Rooms without a reservation holding them on any of the given nights.
     *
     * @param  Builder<Room>  $query
     */
    #[Scope]
    protected function availableBetween(Builder $query, CarbonInterface $checkIn, CarbonInterface $checkOut): void
    {
        $query->whereDoesntHave('reservations', fn (Builder $query) => $query
            ->blocking()
            ->overlapping($checkIn, $checkOut));
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
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

    /**
     * @return MorphMany<Image, $this>
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('position')->orderBy('id');
    }
}
