import { Form, Head, Link, usePoll } from '@inertiajs/react';
import { ArrowLeft, CircleCheck, CreditCard, MapPin } from 'lucide-react';
import { useEffect } from 'react';
import CancellationPolicyText from '@/components/catalog/cancellation-policy-text';
import InputError from '@/components/input-error';
import CancelReservationDialog from '@/components/reservations/cancel-reservation-dialog';
import {
    RefundSummary,
    ReservationDetails,
} from '@/components/reservations/reservation-details';
import ReservationStatusBadge from '@/components/reservations/reservation-status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/dates';
import { formatPrice } from '@/lib/utils';
import { show as showHotel } from '@/routes/hotels';
import { cancel, index, pay } from '@/routes/reservations';
import type { RefundQuote, Reservation } from '@/types';

type Props = {
    reservation: Reservation;
    refundQuote: RefundQuote | null;
};

function RefundNotice({
    reservation,
    quote,
}: {
    reservation: Reservation;
    quote: RefundQuote;
}) {
    const { t, locale } = useTranslation();

    if (reservation.status === 'pending') {
        return (
            <>
                {t(
                    'Nothing has been charged yet, so there is nothing to refund.',
                )}
            </>
        );
    }

    if (quote.amount === 0) {
        return (
            <p className="font-medium text-destructive">
                {t(
                    'You cancel :days days before check-in, after the last refund tier: you will get no refund.',
                    { days: quote.days_before },
                )}
            </p>
        );
    }

    return (
        <>
            {t(
                'You will get :amount back (:percent %), because you cancel :days days before check-in. It goes back to your card automatically.',
                {
                    amount: formatPrice(quote.amount, locale),
                    percent: quote.percent,
                    days: quote.days_before,
                },
            )}
        </>
    );
}

export default function ShowReservation({ reservation, refundQuote }: Props) {
    const { t, locale } = useTranslation();
    const returnedFromPayment =
        typeof window !== 'undefined' &&
        new URLSearchParams(window.location.search).get('checkout') ===
            'success';
    const isConfirmingPayment =
        reservation.status === 'pending' && returnedFromPayment;

    // Stripe confirms the payment through a webhook a few seconds after
    // the customer comes back, so the page checks until it arrives.
    const { start, stop } = usePoll(
        3000,
        { only: ['reservation', 'refundQuote'] },
        { autoStart: false, mode: 'rest' },
    );

    useEffect(() => {
        if (isConfirmingPayment) {
            start();
        } else {
            stop();
        }
    }, [isConfirmingPayment, start, stop]);

    return (
        <>
            <Head title={t('Reservation :code', { code: reservation.code })} />

            <div className="mx-auto max-w-5xl space-y-8 px-4 py-8 sm:px-6">
                <Link
                    href={index()}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    {t('My reservations')}
                </Link>

                <header className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-2">
                        <div className="flex flex-wrap items-center gap-3">
                            <ReservationStatusBadge reservation={reservation} />
                            <span className="font-mono text-sm text-muted-foreground">
                                {reservation.code}
                            </span>
                        </div>
                        <h1 className="font-display text-3xl font-extrabold tracking-tight sm:text-4xl">
                            <Link
                                href={showHotel(reservation.hotel.slug)}
                                className="hover:underline"
                            >
                                {reservation.hotel.name}
                            </Link>
                        </h1>
                        <p className="flex items-center gap-1.5 text-muted-foreground">
                            <MapPin className="size-4" />
                            {reservation.hotel.address},{' '}
                            {reservation.hotel.municipality} (
                            {reservation.hotel.province})
                        </p>
                    </div>
                    {reservation.hotel.cover_url && (
                        <img
                            src={reservation.hotel.cover_url}
                            alt=""
                            className="aspect-[16/10] w-full rounded-2xl object-cover sm:w-56"
                        />
                    )}
                </header>

                {isConfirmingPayment && (
                    <Alert>
                        <Spinner />
                        <AlertTitle>
                            {t('We are confirming your payment…')}
                        </AlertTitle>
                        <AlertDescription>
                            {t(
                                'This usually takes a few seconds. The page updates by itself.',
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                {reservation.status === 'confirmed' && returnedFromPayment && (
                    <Alert>
                        <CircleCheck />
                        <AlertTitle>
                            {t('Your booking is confirmed!')}
                        </AlertTitle>
                        <AlertDescription>
                            {t(
                                'Keep your code :code; the hotel will ask for it at check-in.',
                                { code: reservation.code },
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                {reservation.awaits_payment && !returnedFromPayment && (
                    <Alert>
                        <CreditCard />
                        <AlertTitle>
                            {t('The payment is not complete')}
                        </AlertTitle>
                        <AlertDescription>
                            <p>
                                {reservation.expires_at &&
                                    t(
                                        'We hold the room for you until :time. After that the booking expires.',
                                        {
                                            time: formatDateTime(
                                                reservation.expires_at,
                                                locale,
                                            ),
                                        },
                                    )}
                            </p>
                            <Form
                                {...pay.form(reservation.code)}
                                className="mt-3"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            {t('Complete the payment')}
                                        </Button>
                                        <InputError
                                            message={errors.reservation}
                                        />
                                    </>
                                )}
                            </Form>
                        </AlertDescription>
                    </Alert>
                )}

                {reservation.status === 'expired' && (
                    <Alert>
                        <AlertTitle>{t('This booking expired')}</AlertTitle>
                        <AlertDescription>
                            {t(
                                'The payment was not completed in time, so the room was released and nothing was charged.',
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                {reservation.status === 'cancelled' && (
                    <Alert>
                        <AlertTitle>
                            {reservation.cancelled_by_customer
                                ? t('You cancelled this reservation')
                                : t('The hotel cancelled this reservation')}
                        </AlertTitle>
                        <AlertDescription>
                            {reservation.cancellation_reason && (
                                <p>{reservation.cancellation_reason}</p>
                            )}
                            <RefundSummary reservation={reservation} />
                        </AlertDescription>
                    </Alert>
                )}

                <ReservationDetails reservation={reservation} />

                <section className="space-y-3">
                    <h2 className="font-display text-xl font-bold">
                        {t('Cancellation policy')}
                    </h2>
                    <CancellationPolicyText
                        tiers={reservation.hotel.cancellation_policy}
                    />
                </section>

                {reservation.can_be_cancelled && refundQuote && (
                    <div className="flex flex-col gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-muted-foreground">
                            {t(
                                'Plans changed? You can cancel up to the check-in day.',
                            )}
                        </p>
                        <CancelReservationDialog
                            form={cancel.form(reservation.code)}
                            refundNotice={
                                <RefundNotice
                                    reservation={reservation}
                                    quote={refundQuote}
                                />
                            }
                        />
                    </div>
                )}
            </div>
        </>
    );
}
