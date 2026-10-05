<?php

namespace App\Http\Resources;

use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hotel data shown in the header of every hotel management page.
 *
 * @mixin Hotel
 */
class HotelSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_visible' => $this->is_visible,
            'is_blocked' => $this->isBlocked(),
            'blocked_reason' => $this->blocked_reason,
            'owner_name' => $this->whenLoaded('owner', fn (): string => $this->owner->name),
        ];
    }
}
