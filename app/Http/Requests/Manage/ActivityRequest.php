<?php

namespace App\Http\Requests\Manage;

use App\Models\Hotel;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Creates or updates an informative hotel activity. The price is entered in
 * euros (0 for free activities) and stored in cents.
 */
class ActivityRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:10000'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'capacity' => ['nullable', 'integer', 'between:1,1000'],
        ];
    }

    /**
     * The activity attributes, with the price converted to cents.
     *
     * @return array<string, mixed>
     */
    public function activityAttributes(): array
    {
        return [
            ...$this->safe()->only(['name', 'description', 'starts_at', 'ends_at', 'capacity']),
            'price' => (int) round((float) $this->input('price') * 100),
        ];
    }
}
