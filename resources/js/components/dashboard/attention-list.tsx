import { Link } from '@inertiajs/react';
import {
    Ban,
    ChevronRight,
    CircleCheck,
    CreditCard,
    EyeOff,
    Flag,
    MessageSquareText,
    Wrench,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from '@/hooks/use-translation';
import type { TranslateFn } from '@/hooks/use-translation';
import type { AttentionItem } from '@/types';

const MISSING_LABELS = {
    active_room: 'an active room',
    photo: 'a photo',
    location: 'its location on the map',
} as const;

/**
 * The icon, headline and detail line of each kind of pending task.
 */
function describe(
    item: AttentionItem,
    t: TranslateFn,
): { icon: LucideIcon; title: string; detail?: string } {
    switch (item.type) {
        case 'unanswered_reviews':
            return {
                icon: MessageSquareText,
                title:
                    item.count === 1
                        ? t('1 review without a reply')
                        : t(':count reviews without a reply', {
                              count: item.count,
                          }),
                detail: item.hotel,
            };
        case 'blocked_hotel':
            return {
                icon: Ban,
                title: t(':hotel is blocked', { hotel: item.hotel }),
                detail: item.reason,
            };
        case 'hidden_hotel':
            return {
                icon: EyeOff,
                title: t(':hotel is hidden', { hotel: item.hotel }),
                detail: t('It is ready to publish whenever you want.'),
            };
        case 'incomplete_hotel':
            return {
                icon: Wrench,
                title: t(':hotel is not published yet', { hotel: item.hotel }),
                detail: t('To publish it, add :missing.', {
                    missing: item.missing
                        .map((missing) => t(MISSING_LABELS[missing]))
                        .join(', '),
                }),
            };
        case 'reported_reviews':
            return {
                icon: Flag,
                title:
                    item.count === 1
                        ? t('1 reported review to moderate')
                        : t(':count reported reviews to moderate', {
                              count: item.count,
                          }),
            };
        case 'failed_refunds':
            return {
                icon: CreditCard,
                title:
                    item.count === 1
                        ? t('1 refund failed')
                        : t(':count refunds failed', { count: item.count }),
                detail: t('Retry them from each reservation.'),
            };
        case 'blocked_hotels':
            return {
                icon: Ban,
                title:
                    item.count === 1
                        ? t('1 blocked hotel')
                        : t(':count blocked hotels', { count: item.count }),
            };
    }
}

/**
 * Things to act on, each linking to the page where it is done. When there
 * is nothing left it says so instead of showing an empty box.
 */
export function AttentionList({ items }: { items: AttentionItem[] }) {
    const { t } = useTranslation();

    if (items.length === 0) {
        return (
            <div className="flex flex-col items-center gap-2 py-8 text-center text-sm text-muted-foreground">
                <CircleCheck className="size-8 text-primary" aria-hidden />
                {t('All caught up. Nothing needs your attention.')}
            </div>
        );
    }

    return (
        <ul className="-mx-2 space-y-1">
            {items.map((item, index) => {
                const { icon: Icon, title, detail } = describe(item, t);

                return (
                    <li key={`${item.type}-${index}`}>
                        <Link
                            href={item.href}
                            className="flex items-start gap-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-muted"
                        >
                            <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-sun/15 text-sun-foreground dark:text-sun">
                                <Icon className="size-4" aria-hidden />
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="block text-sm font-medium">
                                    {title}
                                </span>
                                {detail && (
                                    <span className="block text-sm text-muted-foreground">
                                        {detail}
                                    </span>
                                )}
                            </span>
                            <ChevronRight
                                className="mt-2 size-4 shrink-0 text-muted-foreground"
                                aria-hidden
                            />
                        </Link>
                    </li>
                );
            })}
        </ul>
    );
}

export function ListSkeleton({ rows = 3 }: { rows?: number }) {
    return (
        <div className="space-y-3">
            {Array.from({ length: rows }, (_, index) => (
                <Skeleton key={index} className="h-12 w-full" />
            ))}
        </div>
    );
}
