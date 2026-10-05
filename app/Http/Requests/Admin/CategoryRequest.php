<?php

namespace App\Http\Requests\Admin;

use App\Concerns\TranslatedNameRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    use TranslatedNameRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->translatedNameRules('categories', $this->route('category'));
    }
}
