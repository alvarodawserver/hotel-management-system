import { Star } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

export default function StarRating({
    stars,
    className,
}: {
    stars: number | null;
    className?: string;
}) {
    const { t } = useTranslation();

    if (!stars) {
        return null;
    }

    return (
        <span
            className={cn(
                'inline-flex items-center gap-0.5 text-sun',
                className,
            )}
            role="img"
            aria-label={t(':count stars', { count: stars })}
        >
            {Array.from({ length: stars }, (_, index) => (
                <Star key={index} className="size-3.5 fill-current" />
            ))}
        </span>
    );
}
