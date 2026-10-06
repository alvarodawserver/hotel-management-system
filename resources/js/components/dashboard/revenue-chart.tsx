import { Skeleton } from '@/components/ui/skeleton';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTranslation } from '@/hooks/use-translation';
import { cn, formatPrice } from '@/lib/utils';
import type { MonthRevenue } from '@/types';

const TICKS = 4;

/**
 * The top of the axis and its step, rounded to 1, 2, 2.5 or 5 times a power
 * of ten so the gridlines land on clean amounts.
 */
function niceScale(max: number): { top: number; step: number } {
    if (max <= 0) {
        return { top: TICKS, step: 1 };
    }

    const rawStep = max / TICKS;
    const magnitude = 10 ** Math.floor(Math.log10(rawStep));
    const factor =
        [1, 2, 2.5, 5].find((option) => option * magnitude >= rawStep) ?? 10;
    const step = factor * magnitude;

    return { top: Math.ceil(max / step) * step, step };
}

function monthLabel(month: string, locale: string, format: 'short' | 'long') {
    return new Intl.DateTimeFormat(locale, {
        month: format,
        ...(format === 'long' && { year: 'numeric' }),
    }).format(new Date(`${month}-01T00:00:00`));
}

/**
 * Revenue by check-in month as columns, the current (still open) month
 * lighter. Hovering or focusing a month shows its revenue and stays.
 */
export function RevenueChart({ months }: { months: MonthRevenue[] }) {
    const { t, locale } = useTranslation();
    const { top, step } = niceScale(
        Math.max(...months.map((month) => month.revenue)),
    );
    const ticks = Array.from(
        { length: Math.round(top / step) + 1 },
        (_, index) => top - index * step,
    );
    const compact = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: 'EUR',
        notation: 'compact',
        maximumFractionDigits: 1,
    });

    return (
        <div className="grid grid-cols-[auto_1fr] gap-x-3 gap-y-2">
            <div
                className="flex h-56 flex-col justify-between text-right text-xs text-muted-foreground tabular-nums"
                aria-hidden
            >
                {ticks.map((tick) => (
                    <span key={tick} className="-my-2 leading-4">
                        {compact.format(tick / 100)}
                    </span>
                ))}
            </div>

            <div className="relative h-56 flex-1">
                <div
                    className="absolute inset-0 flex flex-col justify-between"
                    aria-hidden
                >
                    {ticks.map((tick) => (
                        <div key={tick} className="border-t border-border" />
                    ))}
                </div>

                <div className="absolute inset-0 flex items-end gap-0.5">
                    {months.map((month, index) => {
                        const isCurrent = index === months.length - 1;

                        return (
                            <Tooltip key={month.month}>
                                <TooltipTrigger asChild>
                                    <button
                                        type="button"
                                        className="group flex h-full flex-1 items-end justify-center rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        aria-label={`${monthLabel(month.month, locale, 'long')}: ${formatPrice(month.revenue, locale)}`}
                                    >
                                        <span
                                            className={cn(
                                                'w-full max-w-6 rounded-t-[4px] bg-chart-1 transition-opacity group-hover:opacity-80',
                                                isCurrent && 'opacity-50',
                                            )}
                                            style={{
                                                height: `${(month.revenue / top) * 100}%`,
                                            }}
                                        />
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p className="font-medium capitalize">
                                        {monthLabel(
                                            month.month,
                                            locale,
                                            'long',
                                        )}
                                        {isCurrent && ` · ${t('in progress')}`}
                                    </p>
                                    <p>
                                        {formatPrice(month.revenue, locale)}
                                        {' · '}
                                        {month.stays === 1
                                            ? t('1 stay')
                                            : t(':count stays', {
                                                  count: month.stays,
                                              })}
                                    </p>
                                </TooltipContent>
                            </Tooltip>
                        );
                    })}
                </div>
            </div>

            <div aria-hidden />
            <div className="flex gap-0.5" aria-hidden>
                {months.map((month) => (
                    <span
                        key={month.month}
                        className="flex-1 text-center text-xs text-muted-foreground capitalize"
                    >
                        {monthLabel(month.month, locale, 'short').replace(
                            '.',
                            '',
                        )}
                    </span>
                ))}
            </div>

            <table className="sr-only">
                <caption>{t('Revenue by month')}</caption>
                <thead>
                    <tr>
                        <th scope="col">{t('Month')}</th>
                        <th scope="col">{t('Revenue')}</th>
                        <th scope="col">{t('Stays')}</th>
                    </tr>
                </thead>
                <tbody>
                    {months.map((month) => (
                        <tr key={month.month}>
                            <th scope="row">
                                {monthLabel(month.month, locale, 'long')}
                            </th>
                            <td>{formatPrice(month.revenue, locale)}</td>
                            <td>{month.stays}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export function RevenueChartSkeleton() {
    return <Skeleton className="h-64 w-full rounded-lg" />;
}
