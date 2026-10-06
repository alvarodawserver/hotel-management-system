import { MessageSquareReply } from 'lucide-react';
import type { ReactNode } from 'react';
import { RatingStars } from '@/components/reviews/rating-input';
import { useTranslation } from '@/hooks/use-translation';
import { formatDay } from '@/lib/dates';
import { cn } from '@/lib/utils';
import type { Review } from '@/types';

type Props = {
    review: Review;
    /** Shown above the comment, e.g. the author's full name for the hotel. */
    meta?: ReactNode;
    /** Buttons (reply, report, remove…) shown below the review. */
    actions?: ReactNode;
    className?: string;
};

/**
 * One review: the score, who stayed and when, the comment and the hotel's
 * public reply.
 */
export default function ReviewCard({
    review,
    meta,
    actions,
    className,
}: Props) {
    const { t, locale } = useTranslation();
    const monthYear = { month: 'long', year: 'numeric' } as const;

    return (
        <article className={cn('space-y-3 rounded-2xl border p-5', className)}>
            <header className="flex flex-wrap items-start justify-between gap-2">
                <div className="flex items-center gap-3">
                    <span
                        aria-hidden
                        className="flex size-10 items-center justify-center rounded-full bg-accent font-semibold text-primary"
                    >
                        {review.author.charAt(0).toUpperCase()}
                    </span>
                    <div>
                        <p className="font-medium">{review.author}</p>
                        <p className="text-xs text-muted-foreground">
                            {t('Stayed in :date · :room', {
                                date: formatDay(
                                    review.stayed_on,
                                    locale,
                                    monthYear,
                                ),
                                room: review.room_type,
                            })}
                        </p>
                    </div>
                </div>
                <div className="text-right">
                    <RatingStars rating={review.rating} />
                    {review.created_at && (
                        <p className="text-xs text-muted-foreground">
                            {formatDay(review.created_at.slice(0, 10), locale, {
                                day: 'numeric',
                                month: 'short',
                                year: 'numeric',
                            })}
                            {review.edited_at && ` · ${t('edited')}`}
                        </p>
                    )}
                </div>
            </header>

            {meta}

            <p className="leading-relaxed break-words whitespace-pre-line">
                {review.comment}
            </p>

            {review.reply && (
                <div className="space-y-1 rounded-xl bg-secondary/70 p-4 text-sm">
                    <p className="flex items-center gap-1.5 font-medium">
                        <MessageSquareReply className="size-4 text-primary" />
                        {t('Response from the hotel')}
                    </p>
                    <p className="break-words whitespace-pre-line text-muted-foreground">
                        {review.reply}
                    </p>
                </div>
            )}

            {actions && (
                <div className="flex flex-wrap gap-2 border-t pt-3">
                    {actions}
                </div>
            )}
        </article>
    );
}
