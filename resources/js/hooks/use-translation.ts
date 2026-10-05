import { usePage } from '@inertiajs/react';

export type TranslateFn = (
    key: string,
    replacements?: Record<string, string | number>,
) => string;

export type UseTranslationReturn = {
    t: TranslateFn;
    locale: string;
};

/**
 * Translate UI strings using Laravel's JSON convention: the English text is
 * the key, and missing translations fall back to that key. Placeholders use
 * Laravel's ":name" syntax.
 */
export function useTranslation(): UseTranslationReturn {
    const { translations, locale } = usePage().props;

    const t: TranslateFn = (key, replacements = {}) => {
        const translated = translations?.[key] ?? key;

        return Object.entries(replacements).reduce(
            (text, [name, value]) => text.replaceAll(`:${name}`, String(value)),
            translated,
        );
    };

    return { t, locale };
}
