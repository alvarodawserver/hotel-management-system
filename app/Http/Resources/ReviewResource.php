<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A review as shown on the hotel page, to its author and to the hotel.
 * Expects the user and the reservation (with its room type) to be loaded.
 * Report and moderation details are only included with withModeration().
 *
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    private bool $withModeration = false;

    /**
     * Include who reported or removed the review and why, for the hotel's
     * management area and the admins.
     */
    public function withModeration(): static
    {
        $this->withModeration = true;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'author' => $this->authorDisplayName(),
            'stayed_on' => $this->reservation->check_in->toDateString(),
            'room_type' => $this->reservation->room->roomType->translation(),
            'created_at' => $this->created_at?->toIso8601String(),
            'edited_at' => $this->edited_at?->toIso8601String(),
            'reply' => $this->reply,
            'replied_at' => $this->replied_at?->toIso8601String(),
            'removed_at' => $this->deleted_at?->toIso8601String(),
            'removal_reason' => $this->deletion_reason,
            ...$this->withModeration ? [
                'author_name' => $this->user->name,
                'author_email' => $this->user->email,
                'reservation_code' => $this->reservation->code,
                'is_reported' => $this->isReported(),
                'reported_at' => $this->reported_at?->toIso8601String(),
                'report_reason' => $this->report_reason,
                'hotel' => $this->whenLoaded('hotel', fn (): array => [
                    'id' => $this->hotel->id,
                    'name' => $this->hotel->name,
                    'slug' => $this->hotel->slug,
                ]),
            ] : [],
        ];
    }
}
