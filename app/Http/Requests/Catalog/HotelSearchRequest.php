<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Public search parameters, shared by the results, hotel and compare pages.
 */
class HotelSearchRequest extends FormRequest
{
    public const MAX_NIGHTS = 30;

    public const SORTS = ['recommended', 'rating', 'price_asc', 'price_desc'];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'check_in' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today', 'required_with:check_out'],
            'check_out' => [
                'nullable',
                'date_format:Y-m-d',
                'after:check_in',
                'required_with:check_in',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $checkIn = $this->date('check_in');

                    if ($checkIn && $this->date('check_out')?->diffInDays($checkIn, absolute: true) > self::MAX_NIGHTS) {
                        $fail(__('A stay can last at most :nights nights.', ['nights' => self::MAX_NIGHTS]));
                    }
                },
            ],
            'adults' => ['nullable', 'integer', 'between:1,10'],
            'children' => ['nullable', 'integer', 'between:0,10'],
            'price_min' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'price_max' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'stars' => ['nullable', 'integer', 'between:1,5'],
            'amenities' => ['array'],
            'amenities.*' => ['integer'],
            'categories' => ['array'],
            'categories.*' => ['integer'],
            'sort' => ['nullable', Rule::in(self::SORTS)],
        ];
    }

    /**
     * The validated criteria with defaults and proper types.
     *
     * @return array{q: string|null, check_in: string|null, check_out: string|null, adults: int, children: int, price_min: int|null, price_max: int|null, stars: int|null, amenities: list<int>, categories: list<int>, sort: string}
     */
    public function criteria(): array
    {
        return [
            'q' => $this->filled('q') ? $this->string('q')->trim()->toString() : null,
            'check_in' => $this->filled('check_in') ? $this->string('check_in')->toString() : null,
            'check_out' => $this->filled('check_out') ? $this->string('check_out')->toString() : null,
            'adults' => $this->filled('adults') ? $this->integer('adults') : 2,
            'children' => $this->integer('children'),
            'price_min' => $this->filled('price_min') ? $this->integer('price_min') : null,
            'price_max' => $this->filled('price_max') ? $this->integer('price_max') : null,
            'stars' => $this->filled('stars') ? $this->integer('stars') : null,
            'amenities' => array_values(array_map(intval(...), (array) $this->input('amenities', []))),
            'categories' => array_values(array_map(intval(...), (array) $this->input('categories', []))),
            'sort' => $this->input('sort', 'recommended'),
        ];
    }
}
