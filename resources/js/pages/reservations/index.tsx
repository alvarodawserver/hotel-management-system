import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Luggage, Star } from 'lucide-react';
import Pagination from '@/components/pagination';
import { RefundSummary } from '@/components/reservations/reservation-details';
import ReservationStatusBadge from '@/components/reservations/reservation-status-badge';
import { RatingStars } from '@/components/reviews/rating-input';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { formatDay } from '@/lib/dates';
import { cn, formatPrice } from '@/lib/utils';
import { index as hotelsIndex } from '@/routes/hotels';
import { index, show } from '@/routes/reservations';
import type { Paginated, Reservation } from '@/types';

type Tab = 'upcoming' | 'past' | 'cancelled';

type Props = {
    reservations: Paginated<Reservation>;
    tab: Tab;
};

const TABS: { value: Tab; label: string; empty: string }[] = [
    {
        value: 'upcoming',
        label: 'Upcoming trips',
        empty: 'You have no upcoming trips. Time to find your spot on the coast!',
    },
    {
        value: 'past',
        label: 'Past stays',
        empty: 'Your past stays will appear here.',
    },
    {
        value: 'cancelled',
        label: 'Cancelled and expired',
        empty: 'You have no cancelled or expired reservations.',
    },
];

function ReservationCard({ reservation }: { reservation: Reservation }) {
    const { t, locale } = useTranslation();

    return (
        <li>
            <Link
                href={show(reservation.code)}
                className="grid overflow-hidden rounded-2xl border bg-card transition-colors hover:border-primary/60 sm:grid-cols-[12rem_1fr]"
            >
                <div className="aspect-[16/9] bg-secondary sm:aspect-auto">
                    {reservation.hotel.cover_url && (
                        <img
                            src={reservation.hotel.cover_url}
                            alt=""
                            loading="lazy"
                            className="size-full object-cover"
                        />
                    )}
                </div>
                <div className="flex flex-col gap-3 p-5 sm:flex-row sm:justify-between">
                    <div className="space-y-1.5">
                        <div className="flex flex-wrap items-center gap-2">
                            <ReservationStatusBadge reservation={reservation} />
                            <span className="font-mono text-xs text-muted-foreground">
                                {reservation.code}
                            </span>
                        </div>
                        <h2 className="font-display text-lg font-semibold">
                            {reservation.hotel.name}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {reservation.hotel.municipality},{' '}
                            {reservation.hotel.province} ·{' '}
                            {reservation.room.room_type}
                        </p>
                        <p className="flex items-center gap-1.5 text-sm">
                            <CalendarDays className="size-4 text-muted-foreground" />
                            {formatDay(reservation.check_in, locale)} →{' '}
                            {formatDay(reservation.check_out, locale)}
                        </p>
                        <RefundSummary reservation={reservation} />
                    </div>
                    <div className="sm:text-right">
                        <p className="text-lg font-semibold">
                            {formatPrice(reservation.total_price, locale)}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {t(':count nights', { count: reservation.nights })}
                        </p>
                        {reservation.awaits_payment && (
                            <p className="mt-2 text-sm font-medium text-primary">
                                {t('Complete the payment')} →
                            </p>
                        )}
                        {reservation.can_be_reviewed && (
                            <p className="mt-2 inline-flex items-center gap-1 text-sm font-medium text-primary">
                                <Star className="size-4 fill-sun text-sun" />
                                {t('Review your stay')} →
                            </p>
                        )}
                        {reservation.review &&
                            !reservation.review.removed_at && (
                                <RatingStars
                                    rating={reservation.review.rating}
                                    className="mt-2"
                                />
                            )}
                    </div>
                </div>
            </Link>
        </li>
    );
}

export default function ReservationsIndex({ reservations, tab }: Props) {
    const { t } = useTranslation();
    const current = TABS.find((item) => item.value === tab) ?? TABS[0];

    return (
        <>
            <Head title={t('My reservations')} />

            <div className="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6">
                <h1 className="font-display text-3xl font-extrabold tracking-tight sm:text-4xl">
                    {t('My reservations')}
                </h1>

                <nav
                    aria-label={t('Reservation lists')}
                    className="flex gap-6 border-b"
                >
                    {TABS.map((item) => (
                        <Link
                            key={item.value}
                            href={index({ query: { tab: item.value } })}
                            preserveScroll
                            className={cn(
                                '-mb-px border-b-2 pb-3 text-sm font-medium',
                                item.value === tab
                                    ? 'border-primary text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {t(item.label)}
                        </Link>
                    ))}
                </nav>

                {reservations.data.length > 0 ? (
                    <>
                        <ul className="space-y-4">
                            {reservations.data.map((reservation) => (
                                <ReservationCard
                                    key={reservation.code}
                                    reservation={reservation}
                                />
                            ))}
                        </ul>
                        <Pagination paginator={reservations} />
                    </>
                ) : (
                    <div className="flex flex-col items-center gap-4 rounded-2xl border border-dashed px-6 py-16 text-center">
                        <Luggage className="size-10 text-muted-foreground" />
                        <p className="max-w-sm text-muted-foreground">
                            {t(current.empty)}
                        </p>
                        {tab === 'upcoming' && (
                            <Button asChild>
                                <Link href={hotelsIndex()}>
                                    {t('Browse hotels')}
                                </Link>
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}
