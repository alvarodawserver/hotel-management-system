<?php

namespace App\Http\Requests\Manage;

use App\Models\Hotel;
use App\Models\Offer;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Creates or updates a hotel offer. The room type must be one the hotel
 * actually has (an offer on suites makes no sense in a hotel without them).
 */
class OfferRequest extends FormRequest
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
        /** @var Offer|null $offer */
        $offer = $this->route('offer');

        return [
            'title' => ['required', 'string', 'max:255'],
            'room_type_id' => [
                'nullable',
                'integer',
                Rule::in($this->hotel()->rooms()->distinct()->pluck('room_type_id')->all()),
            ],
            'discount_percent' => ['required', 'integer', 'between:1,90'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            // A new offer cannot end in the past; existing ones keep their dates.
            'ends_on' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:starts_on',
                ...($offer === null ? ['after_or_equal:today'] : []),
            ],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function offerAttributes(): array
    {
        return [
            ...$this->safe()->only(['title', 'discount_percent', 'starts_on', 'ends_on']),
            'room_type_id' => $this->filled('room_type_id') ? $this->integer('room_type_id') : null,
            'is_active' => $this->boolean('is_active'),
        ];
    }

    private function hotel(): Hotel
    {
        /** @var Hotel */
        return $this->route('hotel');
    }
}
