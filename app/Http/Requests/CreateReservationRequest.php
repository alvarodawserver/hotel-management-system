<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * The stay being booked: a hotel, a kind of room (type, capacity and
 * price) and the dates and guests. Shared by the booking page and the
 * booking submission.
 */
class CreateReservationRequest extends FormRequest
{
    public const MAX_NIGHTS = 30;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('create', Reservation::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'hotel' => ['required', 'string', 'exists:hotels,slug'],
            'room_type_id' => ['required', 'integer'],
            'capacity' => ['required', 'integer', 'between:1,20'],
            'price_per_night' => ['required', 'integer', 'min:1'],
            'check_in' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => [
                'required',
                'date_format:Y-m-d',
                'after:check_in',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $checkIn = $this->date('check_in');

                    if ($checkIn && $this->date('check_out')?->diffInDays($checkIn, absolute: true) > self::MAX_NIGHTS) {
                        $fail(__('A stay can last at most :nights nights.', ['nights' => self::MAX_NIGHTS]));
                    }
                },
            ],
            'adults' => ['required', 'integer', 'between:1,10'],
            'children' => ['nullable', 'integer', 'between:0,10'],
        ];
    }

    /**
     * @return array{room_type_id: int, capacity: int, price_per_night: int, check_in: string, check_out: string, adults: int, children: int}
     */
    public function stay(): array
    {
        return [
            'room_type_id' => $this->integer('room_type_id'),
            'capacity' => $this->integer('capacity'),
            'price_per_night' => $this->integer('price_per_night'),
            'check_in' => $this->string('check_in')->toString(),
            'check_out' => $this->string('check_out')->toString(),
            'adults' => $this->integer('adults'),
            'children' => $this->integer('children'),
        ];
    }
}
