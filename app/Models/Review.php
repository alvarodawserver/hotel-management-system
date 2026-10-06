<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A guest's opinion of a past stay, with a 1–5 rating. Reviews removed by an
 * admin are soft deleted, so they drop out of every public list and average
 * while their author can still see why.
 *
 * @property int $id
 * @property int $reservation_id
 * @property int $user_id
 * @property int $hotel_id
 * @property int $rating
 * @property string $comment
 * @property CarbonImmutable|null $edited_at
 * @property string|null $reply
 * @property CarbonImmutable|null $replied_at
 * @property CarbonImmutable|null $reported_at
 * @property int|null $reported_by
 * @property string|null $report_reason
 * @property int|null $deleted_by
 * @property string|null $deletion_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable(['rating', 'comment'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory, SoftDeletes;

    public const MIN_COMMENT_LENGTH = 10;

    public const MAX_COMMENT_LENGTH = 2000;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'edited_at' => 'datetime',
            'replied_at' => 'datetime',
            'reported_at' => 'datetime',
        ];
    }

    public function isReported(): bool
    {
        return $this->reported_at !== null;
    }

    /**
     * The author as shown in public, first name and the initial of the first
     * surname (e.g. "Álvaro V."), so guests are not identified by their full name.
     */
    public function authorDisplayName(): string
    {
        $parts = preg_split('/\s+/', trim($this->user->name)) ?: [];
        $firstName = array_shift($parts) ?? '';
        $surname = $parts[0] ?? null;

        return $surname === null ? $firstName : $firstName.' '.Str::upper(Str::substr($surname, 0, 1)).'.';
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Including soft-deleted hotels, so reviews keep their hotel's data.
     *
     * @return BelongsTo<Hotel, $this>
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
