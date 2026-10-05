<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The booking submission: the stay plus the guest's details.
 */
class StoreReservationRequest extends CreateReservationRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9 +().-]{6,30}$/'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array{room_type_id: int, capacity: int, price_per_night: int, check_in: string, check_out: string, adults: int, children: int, guest_name: string, guest_phone: string, special_requests: string|null}
     */
    public function booking(): array
    {
        return [
            ...$this->stay(),
            'guest_name' => $this->string('guest_name')->trim()->toString(),
            'guest_phone' => $this->string('guest_phone')->trim()->toString(),
            'special_requests' => $this->filled('special_requests') ? $this->string('special_requests')->trim()->toString() : null,
        ];
    }
}
