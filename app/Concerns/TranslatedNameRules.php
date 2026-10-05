<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

trait TranslatedNameRules
{
    /**
     * Rules for a JSON "name" column with one required, unique translation
     * per available locale (name.es, name.en, …).
     *
     * @return array<string, array<int, ValidationRule|string>|string>
     */
    protected function translatedNameRules(string $table, mixed $ignore = null): array
    {
        $rules = ['name' => ['required', 'array']];

        /** @var list<string> $locales */
        $locales = config('app.available_locales');

        foreach ($locales as $locale) {
            $rules["name.{$locale}"] = [
                'required',
                'string',
                'max:100',
                Rule::unique($table, "name->{$locale}")->ignore($ignore instanceof Model ? $ignore->getKey() : null),
            ];
        }

        return $rules;
    }

    /**
     * The submitted translations, limited to the available locales.
     *
     * @return array<string, string>
     */
    public function translatedName(): array
    {
        /** @var list<string> $locales */
        $locales = config('app.available_locales');

        /** @var array<string, string> $name */
        $name = $this->safe()->only(['name'])['name'];

        return array_intersect_key($name, array_flip($locales));
    }
}
