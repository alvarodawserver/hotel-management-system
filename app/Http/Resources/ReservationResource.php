<?php

namespace App\Http\Resources;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A reservation as shown to its customer and to the hotel. Expects the
 * hotel (with its images) and the room (with its type) to be loaded.
 *
 * @mixin Reservation
 */
class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'status' => $this->status,
            'status_label' => $this->status->label(),
            'awaits_payment' => $this->awaitsPayment(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'check_in' => $this->check_in->toDateString(),
            'check_out' => $this->check_out->toDateString(),
            'nights' => $this->nights(),
            'adults' => $this->adults,
            'children' => $this->children,
            'guest_name' => $this->guest_name,
            'guest_phone' => $this->guest_phone,
            'special_requests' => $this->special_requests,
            'price_breakdown' => $this->price_breakdown,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total_price' => $this->total_price,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancelled_by_customer' => $this->cancelled_by !== null && $this->cancelled_by === $this->user_id,
            'cancellation_reason' => $this->cancellation_reason,
            'refund_amount' => $this->refund_amount,
            'refund_status' => $this->refund_status,
            'refund_status_label' => $this->refund_status?->label(),
            'hotel' => [
                'name' => $this->hotel->name,
                'slug' => $this->hotel->slug,
                'province' => $this->hotel->province->label(),
                'municipality' => $this->hotel->municipality,
                'address' => $this->hotel->address,
                'cover_url' => $this->hotel->relationLoaded('images') ? $this->hotel->images->first()?->url : null,
                'cancellation_policy' => $this->hotel->cancellation_policy,
            ],
            'room' => [
                'name' => $this->room->name,
                'room_type' => $this->room->roomType->translation(),
            ],
            'customer' => $this->whenLoaded('user', fn (): array => [
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
        ];
    }
}
