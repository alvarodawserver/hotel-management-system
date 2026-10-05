<?php

namespace App\Http\Requests\Admin;

use App\Concerns\TranslatedNameRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RoomTypeRequest extends FormRequest
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
            ...$this->translatedNameRules('room_types', $this->route('room_type')),
            'default_capacity' => ['required', 'integer', 'between:1,20'],
        ];
    }
}
