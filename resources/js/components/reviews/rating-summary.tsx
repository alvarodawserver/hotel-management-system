import {
    formatRating,
    useReviewCount,
} from '@/components/reviews/rating-badge';
import { RatingStars } from '@/components/reviews/rating-input';
import { useTranslation } from '@/hooks/use-translation';
import type { RatingSummary as RatingSummaryData } from '@/types';

/**
 * The average rating in large type, with how many reviews gave each score.
 */
export default function RatingSummary({
    rating,
}: {
    rating: RatingSummaryData;
}) {
    const { t, locale } = useTranslation();
    const reviewCount = useReviewCount();

    if (rating.average === null) {
        return null;
    }

    return (
        <div className="grid gap-6 rounded-2xl bg-secondary/70 p-5 sm:grid-cols-[auto_1fr] sm:items-center">
            <div className="space-y-1 text-center sm:pr-6">
                <p className="font-display text-5xl font-extrabold tabular-nums">
                    {formatRating(rating.average, locale)}
                </p>
                <RatingStars rating={Math.round(rating.average)} />
                <p className="text-sm text-muted-foreground">
                    {reviewCount(rating.count)}
                </p>
            </div>
            <ul className="space-y-1.5">
                {rating.distribution.map(({ rating: score, count }) => (
                    <li
                        key={score}
                        className="grid grid-cols-[4.5rem_1fr_2rem] items-center gap-3 text-sm"
                    >
                        <span className="text-muted-foreground">
                            {score === 1
                                ? t('1 star')
                                : t(':count stars', { count: score })}
                        </span>
                        <span className="h-2 overflow-hidden rounded-full bg-background">
                            <span
                                className="block h-full rounded-full bg-sun"
                                style={{
                                    width: `${(count / rating.count) * 100}%`,
                                }}
                            />
                        </span>
                        <span className="text-right tabular-nums">{count}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
