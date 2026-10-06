import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { HotelRating } from '@/types';

export function formatRating(average: number, locale: string): string {
    return new Intl.NumberFormat(locale, {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    }).format(average);
}

export function useReviewCount() {
    const { t } = useTranslation();

    return (count: number) =>
        count === 1 ? t('1 review') : t(':count reviews', { count });
}

/**
 * The guests' average rating, e.g. "4,6 · 23 reviews". Unlike the hotel's
 * stars (its official category), it comes from the reviews. Renders nothing
 * while the hotel has no reviews.
 */
export default function RatingBadge({
    rating,
    className,
    showCount = true,
}: {
    rating: HotelRating;
    className?: string;
    showCount?: boolean;
}) {
    const { t, locale } = useTranslation();
    const reviewCount = useReviewCount();

    if (rating.average === null) {
        return null;
    }

    return (
        <span className={cn('inline-flex items-center gap-1.5', className)}>
            <span
                className="rounded-md bg-primary px-1.5 py-0.5 text-sm font-bold text-primary-foreground tabular-nums"
                aria-label={t('Rated :rating out of 5', {
                    rating: formatRating(rating.average, locale),
                })}
            >
                {formatRating(rating.average, locale)}
            </span>
            {showCount && (
                <span className="text-sm text-muted-foreground">
                    {reviewCount(rating.count)}
                </span>
            )}
        </span>
    );
}
