<?php

namespace App\Http\Requests\Admin;

use App\Concerns\TranslatedNameRules;
use App\Enums\AmenityIcon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AmenityRequest extends FormRequest
{
    use TranslatedNameRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->translatedNameRules('amenities', $this->route('amenity')),
            'icon' => ['required', Rule::enum(AmenityIcon::class)],
        ];
    }
}
