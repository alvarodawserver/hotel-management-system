import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Clock, Eye, MapPin, Navigation, Users } from 'lucide-react';
import { useState } from 'react';
import CancellationPolicyText from '@/components/catalog/cancellation-policy-text';
import HotelGallery from '@/components/catalog/hotel-gallery';
import HotelMap from '@/components/catalog/hotel-map';
import StarRating from '@/components/catalog/star-rating';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { amenityIcon } from '@/lib/amenity-icons';
import { formatPrice } from '@/lib/utils';
import { index as hotelsIndex, show } from '@/routes/hotels';
import { edit } from '@/routes/manage/hotels';
import type { PublicHotel, RoomGroup, SearchCriteria } from '@/types';

type Props = {
    hotel: PublicHotel;
    roomGroups: RoomGroup[];
    criteria: SearchCriteria;
    isPreview: boolean;
};

const SECTIONS = [
    { id: 'overview', title: 'Overview' },
    { id: 'rooms', title: 'Rooms and prices' },
    { id: 'amenities', title: 'Amenities and activities' },
    { id: 'location', title: 'Location' },
    { id: 'policy', title: 'Cancellation policy' },
];

function StayForm({
    hotel,
    criteria,
}: {
    hotel: PublicHotel;
    criteria: SearchCriteria;
}) {
    const { t } = useTranslation();
    const [checkIn, setCheckIn] = useState(criteria.check_in ?? '');
    const [checkOut, setCheckOut] = useState(criteria.check_out ?? '');
    const [adults, setAdults] = useState(String(criteria.adults));
    const [children, setChildren] = useState(String(criteria.children));

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(
            show.url(hotel.slug),
            Object.fromEntries(
                Object.entries({
                    check_in: checkIn,
                    check_out: checkOut,
                    adults,
                    children: children === '0' ? '' : children,
                }).filter(([, value]) => value !== ''),
            ),
            {
                preserveScroll: true,
                preserveState: true,
                only: ['roomGroups', 'criteria'],
            },
        );
    };

    return (
        <form
            onSubmit={submit}
            className="grid gap-3 rounded-2xl bg-secondary/70 p-4 sm:grid-cols-[1fr_1fr_0.6fr_0.6fr_auto] sm:items-end"
        >
            <div className="grid gap-1.5">
                <Label htmlFor="stay-in">{t('Check-in')}</Label>
                <Input
                    id="stay-in"
                    type="date"
                    value={checkIn}
                    onChange={(event) => setCheckIn(event.target.value)}
                    required={checkOut !== ''}
                    className="bg-background"
                />
            </div>
            <div className="grid gap-1.5">
                <Label htmlFor="stay-out">{t('Check-out')}</Label>
                <Input
                    id="stay-out"
                    type="date"
                    min={checkIn}
                    value={checkOut}
                    onChange={(event) => setCheckOut(event.target.value)}
                    required={checkIn !== ''}
                    className="bg-background"
                />
            </div>
            <div className="grid gap-1.5">
                <Label htmlFor="stay-adults">{t('Adults')}</Label>
                <Input
                    id="stay-adults"
                    type="number"
                    min={1}
                    max={10}
                    value={adults}
                    onChange={(event) => setAdults(event.target.value)}
                    className="bg-background"
                />
            </div>
            <div className="grid gap-1.5">
                <Label htmlFor="stay-children">{t('Children')}</Label>
                <Input
                    id="stay-children"
                    type="number"
                    min={0}
                    max={10}
                    value={children}
                    onChange={(event) => setChildren(event.target.value)}
                    className="bg-background"
                />
            </div>
            <Button type="submit">{t('See prices')}</Button>
        </form>
    );
}

function RoomOption({ group }: { group: RoomGroup }) {
    const { t, locale } = useTranslation();
    const dateFormatter = new Intl.DateTimeFormat(locale, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });

    return (
        <li className="grid overflow-hidden rounded-2xl border bg-card sm:grid-cols-[14rem_1fr]">
            <div className="aspect-[4/3] bg-secondary sm:aspect-auto">
                {group.image_url && (
                    <img
                        src={group.image_url}
                        alt=""
                        loading="lazy"
                        className="size-full object-cover"
                    />
                )}
            </div>
            <div className="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between">
                <div className="space-y-2">
                    <h3 className="font-display text-lg font-semibold">
                        {group.room_type}
                    </h3>
                    <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                        <Users className="size-4" />
                        {t('Up to :count guests', { count: group.capacity })}
                    </p>
                    {group.description && (
                        <p className="max-w-prose text-sm">
                            {group.description}
                        </p>
                    )}
                    <p className="text-xs text-muted-foreground">
                        {group.rooms_count === 1
                            ? t('1 room of this type')
                            : t(':count rooms of this type', {
                                  count: group.rooms_count,
                              })}
                    </p>
                </div>

                <div className="space-y-2 sm:min-w-48 sm:text-right">
                    {!group.fits_guests ? (
                        <p className="text-sm text-muted-foreground">
                            {t('Too small for your group')}
                        </p>
                    ) : group.stay ? (
                        <>
                            {group.stay.best_discount_percent > 0 && (
                                <Badge className="bg-sun text-sun-foreground">
                                    −{group.stay.best_discount_percent} %
                                </Badge>
                            )}
                            {group.stay.discount > 0 && (
                                <p className="text-sm text-muted-foreground line-through">
                                    {formatPrice(group.stay.subtotal, locale)}
                                </p>
                            )}
                            <p className="text-2xl font-semibold text-primary">
                                {formatPrice(group.stay.total, locale)}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {t('Total for :count nights', {
                                    count: group.stay.night_count,
                                })}
                            </p>
                            <details className="text-left text-xs">
                                <summary className="cursor-pointer text-primary">
                                    {t('Price per night')}
                                </summary>
                                <ul className="mt-2 space-y-1">
                                    {group.stay.nights.map((night) => (
                                        <li
                                            key={night.date}
                                            className="flex justify-between gap-4"
                                        >
                                            <span>
                                                {dateFormatter.format(
                                                    new Date(
                                                        `${night.date}T00:00:00`,
                                                    ),
                                                )}
                                            </span>
                                            <span>
                                                {night.discount_percent > 0 && (
                                                    <span className="mr-1 text-sun">
                                                        −
                                                        {night.discount_percent}{' '}
                                                        %
                                                    </span>
                                                )}
                                                {formatPrice(
                                                    night.price,
                                                    locale,
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </details>
                        </>
                    ) : (
                        <>
                            <p className="text-2xl font-semibold text-primary">
                                {formatPrice(group.price_per_night, locale)}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {t('per night, before offers')}
                            </p>
                        </>
                    )}
                    {group.fits_guests && (
                        <Button
                            disabled
                            className="w-full sm:w-auto"
                            title={t('Online booking is coming soon')}
                        >
                            {t('Booking opens soon')}
                        </Button>
                    )}
                </div>
            </div>
        </li>
    );
}

export default function CatalogShow({
    hotel,
    roomGroups,
    criteria,
    isPreview,
}: Props) {
    const { t, locale } = useTranslation();
    const dateFormatter = new Intl.DateTimeFormat(locale, {
        day: 'numeric',
        month: 'long',
    });
    const formatDate = (date: string) =>
        dateFormatter.format(new Date(`${date}T00:00:00`));
    const hasLocation = hotel.latitude !== null && hotel.longitude !== null;

    return (
        <>
            <Head title={hotel.name} />

            {isPreview && (
                <div className="mx-auto max-w-7xl px-4 pt-6 sm:px-6">
                    <Alert>
                        <Eye />
                        <AlertTitle>{t('Preview')}</AlertTitle>
                        <AlertDescription>
                            <span>
                                {t(
                                    'This hotel is not published, so only you can see this page.',
                                )}{' '}
                                <Link
                                    href={edit(hotel.id)}
                                    className="font-medium underline"
                                >
                                    {t('Back to the management panel')}
                                </Link>
                            </span>
                        </AlertDescription>
                    </Alert>
                </div>
            )}

            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6">
                <Link
                    href={hotelsIndex()}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    {t('All hotels')}
                </Link>

                <header className="mt-4 mb-6 space-y-2">
                    <StarRating stars={hotel.stars} />
                    <h1 className="font-display text-3xl font-extrabold tracking-tight sm:text-5xl">
                        {hotel.name}
                    </h1>
                    <p className="flex items-center gap-1.5 text-muted-foreground">
                        <MapPin className="size-4" />
                        {hotel.municipality}, {hotel.province}
                    </p>
                </header>

                <HotelGallery images={hotel.images} hotelName={hotel.name} />
            </div>

            <nav
                aria-label={t('Hotel sections')}
                className="sticky top-16 z-30 border-y bg-background/95 backdrop-blur"
            >
                <ul className="mx-auto flex max-w-7xl gap-6 overflow-x-auto px-4 sm:px-6">
                    {SECTIONS.map((section) => (
                        <li key={section.id}>
                            <a
                                href={`#${section.id}`}
                                className="block border-b-2 border-transparent py-3 text-sm font-medium whitespace-nowrap text-muted-foreground hover:border-primary hover:text-foreground"
                            >
                                {t(section.title)}
                            </a>
                        </li>
                    ))}
                </ul>
            </nav>

            <div className="mx-auto max-w-7xl space-y-16 px-4 py-12 sm:px-6">
                <section
                    id="overview"
                    className="grid scroll-mt-32 gap-10 lg:grid-cols-[2fr_1fr]"
                >
                    <div className="space-y-4">
                        <h2 className="font-display text-2xl font-bold">
                            {t('Overview')}
                        </h2>
                        {hotel.categories.length > 0 && (
                            <ul className="flex flex-wrap gap-2">
                                {hotel.categories.map((category) => (
                                    <li key={category}>
                                        <Badge variant="secondary">
                                            {category}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <p className="max-w-prose leading-relaxed whitespace-pre-line">
                            {hotel.description}
                        </p>
                    </div>
                    {hotel.offers.length > 0 && (
                        <aside className="space-y-3 self-start rounded-2xl bg-sun/15 p-5">
                            <h3 className="font-display text-lg font-bold">
                                {t('Current offers')}
                            </h3>
                            <ul className="space-y-3">
                                {hotel.offers.map((offer) => (
                                    <li
                                        key={`${offer.title}-${offer.starts_on}`}
                                        className="text-sm"
                                    >
                                        <span className="font-semibold">
                                            −{offer.discount_percent} %{' '}
                                            {offer.title}
                                        </span>
                                        <span className="block text-muted-foreground">
                                            {t('Nights from :from to :to', {
                                                from: formatDate(
                                                    offer.starts_on,
                                                ),
                                                to: formatDate(offer.ends_on),
                                            })}
                                            {offer.room_type_name &&
                                                `, ${offer.room_type_name}`}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </aside>
                    )}
                </section>

                <section id="rooms" className="scroll-mt-32 space-y-6">
                    <h2 className="font-display text-2xl font-bold">
                        {t('Rooms and prices')}
                    </h2>
                    <StayForm hotel={hotel} criteria={criteria} />
                    {!criteria.check_in && (
                        <p className="text-sm text-muted-foreground">
                            {t(
                                'Choose your dates to see the total price, with any offer already applied.',
                            )}
                        </p>
                    )}
                    <ul className="space-y-4">
                        {roomGroups.map((group) => (
                            <RoomOption key={group.key} group={group} />
                        ))}
                    </ul>
                </section>

                <section id="amenities" className="scroll-mt-32 space-y-6">
                    <h2 className="font-display text-2xl font-bold">
                        {t('Amenities and activities')}
                    </h2>
                    {hotel.amenities.length > 0 ? (
                        <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            {hotel.amenities.map((amenity) => {
                                const Icon = amenityIcon(amenity.icon);

                                return (
                                    <li
                                        key={amenity.name}
                                        className="flex items-center gap-3"
                                    >
                                        <span className="flex size-9 items-center justify-center rounded-full bg-accent text-primary">
                                            <Icon className="size-4" />
                                        </span>
                                        {amenity.name}
                                    </li>
                                );
                            })}
                        </ul>
                    ) : (
                        <p className="text-muted-foreground">
                            {t('The hotel has not listed its amenities yet.')}
                        </p>
                    )}

                    {hotel.activities.length > 0 && (
                        <div className="space-y-3">
                            <h3 className="font-display text-lg font-semibold">
                                {t('Activities')}
                            </h3>
                            <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {hotel.activities.map((activity) => (
                                    <li
                                        key={activity.id}
                                        className="space-y-2 rounded-2xl border p-4"
                                    >
                                        <div className="flex items-start justify-between gap-2">
                                            <p className="font-medium">
                                                {activity.name}
                                            </p>
                                            <span className="text-sm font-medium text-primary">
                                                {activity.price === 0
                                                    ? t('Free')
                                                    : formatPrice(
                                                          activity.price,
                                                          locale,
                                                      )}
                                            </span>
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {activity.description}
                                        </p>
                                        {activity.starts_at && (
                                            <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                                <Clock className="size-3.5" />
                                                {activity.starts_at}
                                                {activity.ends_at &&
                                                    ` – ${activity.ends_at}`}
                                            </p>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </section>

                <section id="location" className="scroll-mt-32 space-y-4">
                    <h2 className="font-display text-2xl font-bold">
                        {t('Location')}
                    </h2>
                    <p className="text-muted-foreground">
                        {hotel.address}, {hotel.municipality} ({hotel.province})
                    </p>
                    {hasLocation ? (
                        <>
                            <HotelMap
                                pins={[
                                    {
                                        id: hotel.id,
                                        name: hotel.name,
                                        latitude: hotel.latitude as number,
                                        longitude: hotel.longitude as number,
                                    },
                                ]}
                                zoom={15}
                                className="h-96"
                            />
                            <Button variant="outline" asChild>
                                <a
                                    href={`https://www.openstreetmap.org/directions?route=%3B${hotel.latitude}%2C${hotel.longitude}`}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <Navigation />
                                    {t('Get directions')}
                                </a>
                            </Button>
                        </>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            {t('The hotel has not set its location yet.')}
                        </p>
                    )}
                </section>

                <section id="policy" className="scroll-mt-32 space-y-4">
                    <h2 className="font-display text-2xl font-bold">
                        {t('Cancellation policy')}
                    </h2>
                    <CancellationPolicyText tiers={hotel.cancellation_policy} />
                </section>
            </div>
        </>
    );
}
