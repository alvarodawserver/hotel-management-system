import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import { formatDay } from '@/lib/dates';
import { show } from '@/routes/manage/reservations';
import type { UpcomingArrival } from '@/types';

/**
 * The next confirmed guests, with their stay and a link to the booking.
 * Today's arrivals say "Today" instead of the date.
 */
export function ArrivalsList({
    arrivals,
    today,
}: {
    arrivals: UpcomingArrival[];
    today: string;
}) {
    const { t, locale } = useTranslation();

    if (arrivals.length === 0) {
        return (
            <p className="py-8 text-center text-sm text-muted-foreground">
                {t('No guests arriving in the next 7 days.')}
            </p>
        );
    }

    return (
        <ul className="-mx-2 divide-y">
            {arrivals.map((arrival) => (
                <li key={arrival.code}>
                    <Link
                        href={show(arrival.code)}
                        className="flex items-center gap-4 rounded-lg px-2 py-3 transition-colors hover:bg-muted"
                    >
                        <span className="w-24 shrink-0 text-sm font-medium capitalize">
                            {arrival.check_in === today
                                ? t('Today')
                                : formatDay(arrival.check_in, locale, {
                                      weekday: 'short',
                                      day: 'numeric',
                                      month: 'short',
                                  })}
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className="block truncate text-sm font-medium">
                                {arrival.guest_name}
                            </span>
                            <span className="block truncate text-sm text-muted-foreground">
                                {arrival.hotel} ·{' '}
                                {t('Room :room', { room: arrival.room })}
                            </span>
                        </span>
                        <span className="hidden shrink-0 text-right text-sm text-muted-foreground sm:block">
                            {arrival.nights === 1
                                ? t('1 night')
                                : t(':count nights', { count: arrival.nights })}
                            {' · '}
                            {arrival.guests === 1
                                ? t('1 guest')
                                : t(':count guests', { count: arrival.guests })}
                        </span>
                        <ChevronRight
                            className="size-4 shrink-0 text-muted-foreground"
                            aria-hidden
                        />
                    </Link>
                </li>
            ))}
        </ul>
    );
}
