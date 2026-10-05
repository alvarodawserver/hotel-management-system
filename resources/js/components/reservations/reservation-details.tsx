import type { ReactNode } from 'react';
import PriceBreakdown from '@/components/reservations/price-breakdown';
import { useTranslation } from '@/hooks/use-translation';
import { formatDay } from '@/lib/dates';
import { formatPrice } from '@/lib/utils';
import type { Reservation } from '@/types';

function Detail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="space-y-0.5">
            <dt className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {label}
            </dt>
            <dd>{children}</dd>
        </div>
    );
}

/**
 * The stay, the guest and the price paid, shared by the customer's and the
 * hotel's reservation pages.
 */
export function ReservationDetails({
    reservation,
    showRoomName = false,
}: {
    reservation: Reservation;
    showRoomName?: boolean;
}) {
    const { t, locale } = useTranslation();

    return (
        <div className="grid gap-8 md:grid-cols-2">
            <dl className="grid content-start gap-4 sm:grid-cols-2">
                <Detail label={t('Check-in')}>
                    {formatDay(reservation.check_in, locale)}
                </Detail>
                <Detail label={t('Check-out')}>
                    {formatDay(reservation.check_out, locale)}
                </Detail>
                <Detail label={t('Room')}>
                    {reservation.room.room_type}
                    {showRoomName && (
                        <span className="text-muted-foreground">
                            {' '}
                            · {t('No. :name', { name: reservation.room.name })}
                        </span>
                    )}
                </Detail>
                <Detail label={t('Guests')}>
                    {t(':adults adults, :children children', {
                        adults: reservation.adults,
                        children: reservation.children,
                    })}
                </Detail>
                <Detail label={t('Main guest')}>
                    {reservation.guest_name}
                </Detail>
                <Detail label={t('Contact phone')}>
                    <a
                        href={`tel:${reservation.guest_phone}`}
                        className="hover:underline"
                    >
                        {reservation.guest_phone}
                    </a>
                </Detail>
                {reservation.special_requests && (
                    <div className="sm:col-span-2">
                        <Detail label={t('Special requests')}>
                            <p className="whitespace-pre-line">
                                {reservation.special_requests}
                            </p>
                        </Detail>
                    </div>
                )}
            </dl>

            <div className="rounded-2xl border p-5">
                <PriceBreakdown
                    nights={reservation.price_breakdown}
                    subtotal={reservation.subtotal}
                    discount={reservation.discount}
                    total={reservation.total_price}
                    totalLabel={
                        reservation.paid_at ? t('Total paid') : t('Total')
                    }
                />
            </div>
        </div>
    );
}

/**
 * What happened to the money after a cancellation.
 */
export function RefundSummary({ reservation }: { reservation: Reservation }) {
    const { t, locale } = useTranslation();

    if (reservation.status !== 'cancelled') {
        return null;
    }

    if (reservation.refund_amount === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                {reservation.paid_at
                    ? t('This cancellation was not refunded.')
                    : t('Nothing was charged for this reservation.')}
            </p>
        );
    }

    return (
        <p className="text-sm">
            {t('Refund of :amount', {
                amount: formatPrice(reservation.refund_amount, locale),
            })}
            {reservation.refund_status_label && (
                <span
                    className={
                        reservation.refund_status === 'failed'
                            ? 'font-medium text-destructive'
                            : 'text-muted-foreground'
                    }
                >
                    {' '}
                    · {reservation.refund_status_label}
                </span>
            )}
        </p>
    );
}
