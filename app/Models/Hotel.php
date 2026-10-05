<?php

namespace App\Models;

use App\Enums\Province;
use Database\Factories\HotelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property Province $province
 * @property string $municipality
 * @property string $address
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int|null $stars
 * @property list<array{days_before: int, refund_percent: int}> $cancellation_policy
 * @property bool $is_visible
 * @property Carbon|null $blocked_at
 * @property string|null $blocked_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read int|null $rooms_count Loaded with withCount('rooms').
 * @property-read int|null $active_rooms_count Loaded with withCount('rooms as active_rooms_count').
 * @property-read int|null $activities_count Loaded with withCount('activities').
 */
#[Fillable(['name', 'description', 'province', 'municipality', 'address', 'latitude', 'longitude', 'stars', 'cancellation_policy'])]
class Hotel extends Model
{
    /** @use HasFactory<HotelFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Refund tiers applied to new hotels: full refund up to 7 days before
     * check-in, half refund up to 3 days before, nothing afterwards.
     *
     * @var list<array{days_before: int, refund_percent: int}>
     */
    public const DEFAULT_CANCELLATION_POLICY = [
        ['days_before' => 7, 'refund_percent' => 100],
        ['days_before' => 3, 'refund_percent' => 50],
    ];

    public const MAX_IMAGES = 15;

    /**
     * Generate the URL slug once, on creation, so public links never break
     * when the hotel is renamed.
     */
    protected static function booted(): void
    {
        static::creating(function (Hotel $hotel): void {
            if (blank($hotel->slug)) {
                $hotel->slug = static::uniqueSlug($hotel->name);
            }
        });
    }

    /**
     * Slugify the name, appending a counter if it is already taken (deleted
     * hotels included, since their old links must not point to a new hotel).
     */
    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'hotel';
        $slug = $base;
        $suffix = 2;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'province' => Province::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'stars' => 'integer',
            'cancellation_policy' => 'array',
            'is_visible' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }

    /**
     * Hotels shown to the public: visible and not blocked by an admin.
     *
     * @param  Builder<Hotel>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_visible', true)->whereNull('blocked_at');
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function isPublished(): bool
    {
        return $this->is_visible && ! $this->isBlocked();
    }

    /**
     * A hotel can only be made visible once it has something to book, to show
     * and a place on the map.
     */
    public function canBePublished(): bool
    {
        return $this->hasActiveRooms() && $this->images()->exists() && $this->hasLocation();
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * The biggest discount among the offers covering today, used for the
     * "-20 %" badge in the catalogue. Uses the loaded "offers" relation when
     * available to avoid a query per hotel.
     */
    public function bestCurrentOffer(): ?Offer
    {
        $today = today();

        $offers = $this->relationLoaded('offers')
            ? $this->offers
            : $this->offers()->activeBetween($today, $today)->get();

        return $offers
            ->filter(fn (Offer $offer): bool => $offer->is_active
                && $today->betweenIncluded($offer->starts_on, $offer->ends_on))
            ->sortByDesc('discount_percent')
            ->first();
    }

    public function hasActiveRooms(): bool
    {
        return $this->rooms()->where('is_active', true)->exists();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * @return MorphMany<Image, $this>
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('position')->orderBy('id');
    }

    /**
     * @return MorphOne<Image, $this>
     */
    public function coverImage(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->oldestOfMany('position');
    }

    /**
     * @return BelongsToMany<Amenity, $this>
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class);
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
}
