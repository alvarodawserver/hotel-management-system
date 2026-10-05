import { Link } from '@inertiajs/react';
import { Scale, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { MAX_COMPARED_HOTELS, useCompare } from '@/hooks/use-compare';
import { useTranslation } from '@/hooks/use-translation';
import { compare } from '@/routes/hotels';

/**
 * Floating bar listing the hotels picked for comparison.
 */
export default function CompareTray({
    query = {},
}: {
    /** Search parameters (dates, guests) to price the compared hotels. */
    query?: Record<string, string | number>;
}) {
    const { t } = useTranslation();
    const { hotels, toggle, clear } = useCompare();

    if (hotels.length === 0) {
        return null;
    }

    return (
        <div className="fixed inset-x-0 bottom-4 z-40 px-4">
            <div className="mx-auto flex max-w-3xl flex-wrap items-center gap-3 rounded-2xl bg-sidebar p-3 text-sidebar-foreground shadow-2xl">
                <Scale className="size-5 shrink-0" aria-hidden />
                <ul className="flex flex-1 flex-wrap gap-2">
                    {hotels.map((hotel) => (
                        <li
                            key={hotel.id}
                            className="flex items-center gap-1 rounded-full bg-sidebar-accent py-1 pr-1 pl-3 text-sm"
                        >
                            {hotel.name}
                            <button
                                type="button"
                                onClick={() => toggle(hotel)}
                                className="rounded-full p-0.5 hover:bg-sidebar-border"
                                aria-label={t('Remove :name', {
                                    name: hotel.name,
                                })}
                            >
                                <X className="size-3.5" />
                            </button>
                        </li>
                    ))}
                </ul>
                <span className="text-xs text-sidebar-foreground/70">
                    {hotels.length}/{MAX_COMPARED_HOTELS}
                </span>
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={clear}
                    className="text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-foreground"
                >
                    {t('Clear')}
                </Button>
                <Button
                    size="sm"
                    asChild
                    disabled={hotels.length < 2}
                    className="bg-sun text-sun-foreground hover:bg-sun/90"
                >
                    <Link
                        href={compare({
                            query: {
                                ...query,
                                ids: hotels.map((hotel) => hotel.id),
                            },
                        })}
                    >
                        {t('Compare')}
                    </Link>
                </Button>
            </div>
        </div>
    );
}
