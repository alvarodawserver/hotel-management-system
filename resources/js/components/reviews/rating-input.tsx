import { Star } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

const SCORES = [1, 2, 3, 4, 5];

/**
 * A review's score as five stars, the first `rating` of them filled.
 */
export function RatingStars({
    rating,
    className,
}: {
    rating: number;
    className?: string;
}) {
    const { t } = useTranslation();

    return (
        <span
            className={cn('inline-flex items-center gap-0.5', className)}
            role="img"
            aria-label={t('Rated :rating out of 5', { rating })}
        >
            {SCORES.map((score) => (
                <Star
                    key={score}
                    className={cn(
                        'size-4',
                        score <= rating
                            ? 'fill-sun text-sun'
                            : 'text-muted-foreground/40',
                    )}
                />
            ))}
        </span>
    );
}

/**
 * Pick a 1–5 score. Native radio buttons styled as stars, so the form posts
 * a "rating" field and the keyboard (arrows) works out of the box.
 */
export default function RatingInput({
    name = 'rating',
    defaultValue,
}: {
    name?: string;
    defaultValue?: number;
}) {
    const { t } = useTranslation();
    const [value, setValue] = useState(defaultValue ?? 0);
    const [hovered, setHovered] = useState(0);
    const shown = hovered || value;

    const labels: Record<number, string> = {
        1: t('Very poor'),
        2: t('Poor'),
        3: t('Okay'),
        4: t('Good'),
        5: t('Excellent'),
    };

    return (
        <fieldset className="space-y-1.5">
            <legend className="text-sm font-medium">{t('Your rating')}</legend>
            <div className="flex items-center gap-3">
                <div className="flex" onMouseLeave={() => setHovered(0)}>
                    {SCORES.map((score) => (
                        <label
                            key={score}
                            className="cursor-pointer rounded-sm p-0.5 has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-ring/50"
                            onMouseEnter={() => setHovered(score)}
                        >
                            <input
                                type="radio"
                                name={name}
                                value={score}
                                checked={value === score}
                                onChange={() => setValue(score)}
                                className="sr-only"
                                required
                            />
                            <Star
                                aria-hidden
                                className={cn(
                                    'size-7 transition-colors',
                                    score <= shown
                                        ? 'fill-sun text-sun'
                                        : 'text-muted-foreground/40',
                                )}
                            />
                            <span className="sr-only">{labels[score]}</span>
                        </label>
                    ))}
                </div>
                {shown > 0 && (
                    <span className="text-sm text-muted-foreground">
                        {labels[shown]}
                    </span>
                )}
            </div>
        </fieldset>
    );
}
