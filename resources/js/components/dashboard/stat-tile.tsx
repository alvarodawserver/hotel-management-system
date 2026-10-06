import { ArrowDownRight, ArrowUpRight } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { MonthComparison } from '@/types';

/**
 * Percentage change from last month, or null when last month had nothing
 * to compare with.
 */
export function monthChange({ current, previous }: MonthComparison) {
    return previous > 0
        ? Math.round(((current - previous) / previous) * 100)
        : null;
}

/**
 * One headline figure: a label, the value, and an optional change against
 * last month or a short hint below.
 */
export function StatTile({
    icon: Icon,
    label,
    value,
    change,
    hint,
}: {
    icon: LucideIcon;
    label: string;
    value: string;
    change?: number | null;
    hint?: string;
}) {
    const { t } = useTranslation();
    const isUp = change !== undefined && change !== null && change >= 0;

    return (
        <div className="rounded-xl border bg-card p-5">
            <div className="flex items-center justify-between gap-2 text-sm text-muted-foreground">
                <span>{label}</span>
                <Icon className="size-4" aria-hidden />
            </div>
            <p className="mt-2 text-3xl font-semibold tracking-tight">
                {value}
            </p>
            {change !== undefined && change !== null ? (
                <p className="mt-1 flex items-center gap-1 text-sm">
                    <span
                        className={cn(
                            'inline-flex items-center gap-0.5 font-medium',
                            isUp ? 'text-primary' : 'text-destructive',
                        )}
                    >
                        {isUp ? (
                            <ArrowUpRight className="size-4" aria-hidden />
                        ) : (
                            <ArrowDownRight className="size-4" aria-hidden />
                        )}
                        {isUp ? '+' : ''}
                        {change} %
                    </span>
                    <span className="text-muted-foreground">
                        {t('vs. last month')}
                    </span>
                </p>
            ) : (
                hint && (
                    <p className="mt-1 text-sm text-muted-foreground">{hint}</p>
                )
            )}
        </div>
    );
}

export function StatTilesSkeleton() {
    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {[0, 1, 2, 3].map((index) => (
                <Skeleton key={index} className="h-[7.5rem] rounded-xl" />
            ))}
        </div>
    );
}
