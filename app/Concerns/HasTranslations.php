<?php

namespace App\Concerns;

/**
 * Stores admin-managed names as a JSON column keyed by locale,
 * e.g. {"es": "Piscina", "en": "Swimming pool"}.
 *
 * The model must cast the "name" attribute to "array".
 */
trait HasTranslations
{
    /**
     * Get the name in the given (or current) locale, falling back to the
     * fallback locale and then to any available translation.
     */
    public function translation(?string $locale = null): string
    {
        $translations = $this->translations();
        $locale ??= app()->getLocale();

        return $translations[$locale]
            ?? $translations[config('app.fallback_locale')]
            ?? (string) (reset($translations) ?: '');
    }

    /**
     * Get every non-empty translation of the name keyed by locale.
     *
     * @return array<string, string>
     */
    public function translations(): array
    {
        /** @var array<string, string|null> $name */
        $name = $this->getAttribute('name') ?? [];

        return array_filter($name, fn (?string $value): bool => filled($value));
    }
}
