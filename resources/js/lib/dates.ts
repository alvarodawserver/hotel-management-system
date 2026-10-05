/**
 * Format a "Y-m-d" date (no time zone) for the given locale, e.g.
 * "2026-10-15" → "jue, 15 oct 2026".
 */
export function formatDay(
    date: string,
    locale: string,
    options: Intl.DateTimeFormatOptions = {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    },
): string {
    return new Intl.DateTimeFormat(locale, options).format(
        new Date(`${date}T00:00:00`),
    );
}

/**
 * Format an ISO date-time in the browser's time zone.
 */
export function formatDateTime(dateTime: string, locale: string): string {
    return new Intl.DateTimeFormat(locale, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(dateTime));
}
