<?php

namespace App\Http\Requests\Manage;

use App\Enums\Province;
use App\Models\Hotel;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHotelRequest extends FormRequest
{
    public const MAX_CANCELLATION_TIERS = 4;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('create', Hotel::class);
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
            'description' => ['required', 'string', 'max:5000'],
            'province' => ['required', Rule::enum(Province::class)],
            'municipality' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'stars' => ['nullable', 'integer', 'between:1,5'],
            'cancellation_policy' => ['required', 'array', 'min:1', 'max:'.self::MAX_CANCELLATION_TIERS],
            'cancellation_policy.*.days_before' => ['required', 'integer', 'between:0,365', 'distinct'],
            'cancellation_policy.*.refund_percent' => ['required', 'integer', 'between:1,100'],
            'amenity_ids' => ['array'],
            'amenity_ids.*' => ['integer', 'exists:amenities,id'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ];
    }

    /**
     * Cancelling earlier must never refund less than cancelling later.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['cancellation_policy', 'cancellation_policy.*'])) {
                    return;
                }

                $previousPercent = PHP_INT_MAX;

                foreach ($this->cancellationPolicy() as $tier) {
                    if ($tier['refund_percent'] > $previousPercent) {
                        $validator->errors()->add(
                            'cancellation_policy',
                            __('Cancelling earlier must refund at least as much as cancelling later.'),
                        );

                        return;
                    }

                    $previousPercent = $tier['refund_percent'];
                }
            },
        ];
    }

    /**
     * The refund tiers sorted from the most to the least days before check-in.
     *
     * @return list<array{days_before: int, refund_percent: int}>
     */
    public function cancellationPolicy(): array
    {
        /** @var array<int, array{days_before: int|string, refund_percent: int|string}> $input */
        $input = $this->input('cancellation_policy', []);

        $tiers = array_map(fn (array $tier): array => [
            'days_before' => (int) $tier['days_before'],
            'refund_percent' => (int) $tier['refund_percent'],
        ], array_values($input));

        usort($tiers, fn (array $a, array $b): int => $b['days_before'] <=> $a['days_before']);

        return $tiers;
    }

    /**
     * The hotel attributes, ready to be filled into the model.
     *
     * @return array<string, mixed>
     */
    public function hotelAttributes(): array
    {
        return [
            ...$this->safe()->only(['name', 'description', 'province', 'municipality', 'address', 'stars']),
            'latitude' => $this->filled('latitude') ? (float) $this->input('latitude') : null,
            'longitude' => $this->filled('longitude') ? (float) $this->input('longitude') : null,
            'cancellation_policy' => $this->cancellationPolicy(),
        ];
    }
}
