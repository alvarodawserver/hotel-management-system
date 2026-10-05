<?php

namespace App\Http\Requests\Manage;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Creates or updates a single room. The price is entered in euros and
 * stored in cents.
 */
class RoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->hotel());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Room|null $room */
        $room = $this->route('room');

        return [
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'name')
                    ->where('hotel_id', $this->hotel()->id)
                    ->whereNull('deleted_at')
                    ->ignore($room),
            ],
            'capacity' => ['required', 'integer', 'between:1,20'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:100000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * The room attributes, with the price converted to cents.
     *
     * @return array<string, mixed>
     */
    public function roomAttributes(): array
    {
        return [
            ...$this->safe()->only(['room_type_id', 'name', 'capacity', 'description']),
            'price_per_night' => (int) round((float) $this->input('price') * 100),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    private function hotel(): Hotel
    {
        /** @var Hotel */
        return $this->route('hotel');
    }
}
