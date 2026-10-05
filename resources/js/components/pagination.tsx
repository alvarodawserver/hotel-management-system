import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

const itemClasses =
    'inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm';

export default function Pagination<T>({
    paginator,
}: {
    paginator: Paginated<T>;
}) {
    const { t } = useTranslation();

    if (paginator.last_page <= 1) {
        return null;
    }

    // Laravel puts the "previous" and "next" links first and last.
    const lastIndex = paginator.links.length - 1;

    return (
        <nav
            aria-label={t('Pagination')}
            className="flex flex-col items-center justify-between gap-3 sm:flex-row"
        >
            <p className="text-sm text-muted-foreground">
                {t('Showing :from to :to of :total results', {
                    from: paginator.from ?? 0,
                    to: paginator.to ?? 0,
                    total: paginator.total,
                })}
            </p>

            <ul className="flex flex-wrap items-center gap-1">
                {paginator.links.map((link, index) => {
                    const isPrevious = index === 0;
                    const isNext = index === lastIndex;
                    const content = isPrevious ? (
                        <ChevronLeft className="size-4" />
                    ) : isNext ? (
                        <ChevronRight className="size-4" />
                    ) : (
                        link.label
                    );
                    const label = isPrevious
                        ? t('Previous page')
                        : isNext
                          ? t('Next page')
                          : undefined;

                    return (
                        <li key={`${index}-${link.label}`}>
                            {link.url ? (
                                <Link
                                    href={link.url}
                                    preserveScroll
                                    aria-label={label}
                                    aria-current={
                                        link.active ? 'page' : undefined
                                    }
                                    className={cn(
                                        itemClasses,
                                        link.active
                                            ? 'bg-primary text-primary-foreground'
                                            : 'hover:bg-muted',
                                    )}
                                >
                                    {content}
                                </Link>
                            ) : (
                                <span
                                    aria-label={label}
                                    className={cn(
                                        itemClasses,
                                        'text-muted-foreground opacity-50',
                                    )}
                                >
                                    {content}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
