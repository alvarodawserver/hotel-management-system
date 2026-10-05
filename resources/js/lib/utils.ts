import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * Format an amount stored in euro cents for the given locale,
 * e.g. 8950 → "89,50 €" (es) or "€89.50" (en).
 */
export function formatPrice(cents: number, locale: string): string {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: 'EUR',
    }).format(cents / 100);
}

/**
 * Convert euro cents to the plain decimal string used by price inputs.
 */
export function centsToInput(cents: number): string {
    return (cents / 100).toFixed(2);
}
