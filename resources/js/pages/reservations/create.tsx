import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Lock, MapPin, Users } from 'lucide-react';
import CancellationPolicyText from '@/components/catalog/cancellation-policy-text';
import StarRating from '@/components/catalog/star-rating';
import InputError from '@/components/input-error';
import PriceBreakdown from '@/components/reservations/price-breakdown';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import { formatDay } from '@/lib/dates';
import { formatPrice } from '@/lib/utils';
import { show as showHotel } from '@/routes/hotels';
import { store } from '@/routes/reservations';
import type { CancellationTier, StayBreakdown } from '@/types';

type Props = {
    hotel: {
        name: string;
        slug: string;
        province: string;
        municipality: string;
        stars: number | null;
        cover_url: string | null;
        cancellation_policy: CancellationTier[];
    };
    room: {
        room_type: string;
        capacity: number;
        description: string | null;
        image_url: string | null;
    };
    stay: {
        hotel: string;
        room_type_id: number;
        capacity: number;
        price_per_night: number;
        check_in: string;
        check_out: string;
        adults: number;
        children: number;
    };
    price: StayBreakdown;
    guestName: string;
    paymentWindowMinutes: number;
};

const textareaClasses =
    'flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

/** Fields that describe the stay; their errors are shown above the form. */
const STAY_FIELDS = [
    'room',
    'hotel',
    'room_type_id',
    'capacity',
    'price_per_night',
    'check_in',
    'check_out',
    'adults',
    'children',
];

export default function CreateReservation({
    hotel,
    room,
    stay,
    price,
    guestName,
    paymentWindowMinutes,
}: Props) {
    const { t, locale } = useTranslation();
    const hotelUrl = showHotel(hotel.slug, {
        query: {
            check_in: stay.check_in,
            check_out: stay.check_out,
            adults: stay.adults,
            children: stay.children,
        },
    });

    return (
        <>
            <Head title={t('Complete your booking')} />

            <div className="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6">
                <Link
                    href={hotelUrl}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    {t('Back to the hotel')}
                </Link>

                <h1 className="font-display text-3xl font-extrabold tracking-tight sm:text-4xl">
                    {t('Complete your booking')}
                </h1>

                <div className="grid gap-10 lg:grid-cols-[1fr_24rem]">
                    <Form
                        {...store.form()}
                        className="order-2 space-y-8 lg:order-1"
                    >
                        {({ processing, errors }) => {
                            const stayErrors = STAY_FIELDS.map(
                                (field) => errors[field],
                            ).filter(Boolean);

                            return (
                                <>
                                    {Object.entries(stay).map(
                                        ([name, value]) => (
                                            <input
                                                key={name}
                                                type="hidden"
                                                name={name}
                                                value={value}
                                            />
                                        ),
                                    )}

                                    {stayErrors.length > 0 && (
                                        <div className="space-y-1 rounded-xl border border-destructive/40 bg-destructive/5 p-4">
                                            {stayErrors.map((message) => (
                                                <InputError
                                                    key={message}
                                                    message={message}
                                                />
                                            ))}
                                            <Link
                                                href={hotelUrl}
                                                className="text-sm font-medium underline"
                                            >
                                                {t('Choose another room')}
                                            </Link>
                                        </div>
                                    )}

                                    <section className="space-y-5">
                                        <h2 className="font-display text-xl font-bold">
                                            {t('Guest details')}
                                        </h2>
                                        <div className="grid gap-5 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="guest_name">
                                                    {t(
                                                        'Name of the main guest',
                                                    )}
                                                </Label>
                                                <Input
                                                    id="guest_name"
                                                    name="guest_name"
                                                    required
                                                    maxLength={255}
                                                    autoComplete="name"
                                                    defaultValue={guestName}
                                                />
                                                <InputError
                                                    message={errors.guest_name}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="guest_phone">
                                                    {t('Contact phone')}
                                                </Label>
                                                <Input
                                                    id="guest_phone"
                                                    name="guest_phone"
                                                    type="tel"
                                                    required
                                                    maxLength={30}
                                                    autoComplete="tel"
                                                    placeholder="+34 600 000 000"
                                                />
                                                <InputError
                                                    message={errors.guest_phone}
                                                />
                                            </div>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="special_requests">
                                                {t('Special requests')}{' '}
                                                <span className="font-normal text-muted-foreground">
                                                    ({t('optional')})
                                                </span>
                                            </Label>
                                            <textarea
                                                id="special_requests"
                                                name="special_requests"
                                                maxLength={1000}
                                                className={textareaClasses}
                                                placeholder={t(
                                                    'Late arrival, cot for a baby, quiet room…',
                                                )}
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                {t(
                                                    'The hotel will do its best, but requests are not guaranteed.',
                                                )}
                                            </p>
                                            <InputError
                                                message={
                                                    errors.special_requests
                                                }
                                            />
                                        </div>
                                    </section>

                                    <section className="space-y-3">
                                        <h2 className="font-display text-xl font-bold">
                                            {t('Cancellation policy')}
                                        </h2>
                                        <CancellationPolicyText
                                            tiers={hotel.cancellation_policy}
                                        />
                                        <p className="text-sm text-muted-foreground">
                                            {t(
                                                'Refunds go back automatically to the card you pay with.',
                                            )}
                                        </p>
                                    </section>

                                    <div className="space-y-3 rounded-2xl bg-secondary p-5">
                                        <Button
                                            type="submit"
                                            size="lg"
                                            className="w-full"
                                            disabled={processing}
                                        >
                                            {processing ? (
                                                <Spinner />
                                            ) : (
                                                <Lock />
                                            )}
                                            {t('Pay :amount', {
                                                amount: formatPrice(
                                                    price.total,
                                                    locale,
                                                ),
                                            })}
                                        </Button>
                                        <p className="text-center text-xs text-muted-foreground">
                                            {t(
                                                'You will pay on Stripe’s secure page. We hold the room for :minutes minutes while you pay.',
                                                {
                                                    minutes:
                                                        paymentWindowMinutes,
                                                },
                                            )}
                                        </p>
                                    </div>
                                </>
                            );
                        }}
                    </Form>

                    <aside className="order-1 space-y-5 self-start rounded-2xl border bg-card p-5 lg:sticky lg:top-24 lg:order-2">
                        {(room.image_url ?? hotel.cover_url) && (
                            <img
                                src={room.image_url ?? hotel.cover_url ?? ''}
                                alt=""
                                className="aspect-[16/9] w-full rounded-xl object-cover"
                            />
                        )}
                        <div className="space-y-1">
                            <StarRating stars={hotel.stars} />
                            <h2 className="font-display text-xl font-bold">
                                {hotel.name}
                            </h2>
                            <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                <MapPin className="size-4" />
                                {hotel.municipality}, {hotel.province}
                            </p>
                        </div>
                        <dl className="space-y-2 text-sm">
                            <div className="flex gap-2">
                                <dt className="sr-only">{t('Room')}</dt>
                                <dd className="font-medium">
                                    {room.room_type}
                                </dd>
                            </div>
                            <div className="flex items-center gap-2">
                                <CalendarDays className="size-4 text-muted-foreground" />
                                <dt className="sr-only">{t('Dates')}</dt>
                                <dd>
                                    {formatDay(stay.check_in, locale)} →{' '}
                                    {formatDay(stay.check_out, locale)}
                                </dd>
                            </div>
                            <div className="flex items-center gap-2">
                                <Users className="size-4 text-muted-foreground" />
                                <dt className="sr-only">{t('Guests')}</dt>
                                <dd>
                                    {t(':adults adults, :children children', {
                                        adults: stay.adults,
                                        children: stay.children,
                                    })}
                                </dd>
                            </div>
                        </dl>
                        <div className="border-t pt-4">
                            <PriceBreakdown
                                nights={price.nights}
                                subtotal={price.subtotal}
                                discount={price.discount}
                                total={price.total}
                                totalLabel={t('Total for :count nights', {
                                    count: price.night_count,
                                })}
                            />
                        </div>
                    </aside>
                </div>
            </div>
        </>
    );
}
