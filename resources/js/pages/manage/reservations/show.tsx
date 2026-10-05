import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowLeft, Mail, RotateCcw } from 'lucide-react';
import Heading from '@/components/heading';
import CancelReservationDialog from '@/components/reservations/cancel-reservation-dialog';
import {
    RefundSummary,
    ReservationDetails,
} from '@/components/reservations/reservation-details';
import ReservationStatusBadge from '@/components/reservations/reservation-status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/dates';
import { formatPrice } from '@/lib/utils';
import { show as showHotel } from '@/routes/hotels';
import { cancel, index, refund, show } from '@/routes/manage/reservations';
import type { Reservation } from '@/types';

type Props = {
    reservation: Reservation;
    cancelledByName: string | null;
    canRetryRefund: boolean;
};

export default function ManageReservationShow({
    reservation,
    cancelledByName,
    canRetryRefund,
}: Props) {
    const { t, locale } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            { title: 'Reservations', href: index() },
            { title: reservation.code, href: show(reservation.code) },
        ],
    });

    return (
        <>
            <Head title={t('Reservation :code', { code: reservation.code })} />

            <div className="flex max-w-5xl flex-col gap-8 p-4">
                <Link
                    href={index()}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    {t('All reservations')}
                </Link>

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-2">
                        <div className="flex items-center gap-3">
                            <ReservationStatusBadge reservation={reservation} />
                            <span className="font-mono text-sm text-muted-foreground">
                                {reservation.code}
                            </span>
                        </div>
                        <Heading
                            title={reservation.hotel.name}
                            description={
                                reservation.created_at
                                    ? t('Booked on :date', {
                                          date: formatDateTime(
                                              reservation.created_at,
                                              locale,
                                          ),
                                      })
                                    : undefined
                            }
                        />
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={showHotel(reservation.hotel.slug)}>
                            {t('View hotel page')}
                        </Link>
                    </Button>
                </div>

                {reservation.customer && (
                    <p className="flex items-center gap-2 text-sm">
                        <Mail className="size-4 text-muted-foreground" />
                        {t('Booked by :name', {
                            name: reservation.customer.name,
                        })}{' '}
                        ·{' '}
                        <a
                            href={`mailto:${reservation.customer.email}`}
                            className="text-primary hover:underline"
                        >
                            {reservation.customer.email}
                        </a>
                    </p>
                )}

                {reservation.status === 'cancelled' && (
                    <Alert>
                        <AlertTitle>
                            {cancelledByName
                                ? t('Cancelled by :name', {
                                      name: cancelledByName,
                                  })
                                : t('Cancelled')}
                            {reservation.cancelled_at &&
                                ` · ${formatDateTime(reservation.cancelled_at, locale)}`}
                        </AlertTitle>
                        <AlertDescription>
                            {reservation.cancellation_reason && (
                                <p>{reservation.cancellation_reason}</p>
                            )}
                            <RefundSummary reservation={reservation} />
                            {canRetryRefund && (
                                <Button size="sm" className="mt-3" asChild>
                                    <Link
                                        href={refund(reservation.code)}
                                        as="button"
                                        preserveScroll
                                    >
                                        <RotateCcw />
                                        {t('Retry refund')}
                                    </Link>
                                </Button>
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                <ReservationDetails reservation={reservation} showRoomName />

                {reservation.can_be_cancelled && (
                    <div className="flex flex-col gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <p className="max-w-prose text-sm text-muted-foreground">
                            {t(
                                'If the hotel cannot honour this booking, cancel it: the guest gets a full refund and sees your reason.',
                            )}
                        </p>
                        <CancelReservationDialog
                            form={cancel.form(reservation.code)}
                            askForReason
                            refundNotice={
                                reservation.status === 'confirmed'
                                    ? t(
                                          'The guest gets a full refund of :amount.',
                                          {
                                              amount: formatPrice(
                                                  reservation.total_price,
                                                  locale,
                                              ),
                                          },
                                      )
                                    : t(
                                          'Nothing has been charged yet, so there is nothing to refund.',
                                      )
                            }
                        />
                    </div>
                )}
            </div>
        </>
    );
}
