<?php

namespace App\Http\Requests\Manage;

use App\Actions\Rooms\CreateRoomsInBulk;
use App\Models\Hotel;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class BulkStoreRoomsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        /** @var Hotel $hotel */
        $hotel = $this->route('hotel');

        return Gate::inspect('update', $hotel);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'capacity' => ['required', 'integer', 'between:1,20'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:100000'],
            'quantity' => ['required', 'integer', 'between:1,'.CreateRoomsInBulk::MAX_ROOMS],
            'first_number' => ['required', 'integer', 'between:1,99999'],
        ];
    }

    /**
     * @return array{room_type_id: int, capacity: int, price_per_night: int, quantity: int, first_number: int}
     */
    public function bulkData(): array
    {
        return [
            'room_type_id' => $this->integer('room_type_id'),
            'capacity' => $this->integer('capacity'),
            'price_per_night' => (int) round((float) $this->input('price') * 100),
            'quantity' => $this->integer('quantity'),
            'first_number' => $this->integer('first_number'),
        ];
    }
}
