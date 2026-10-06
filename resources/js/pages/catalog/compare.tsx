import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Check, Minus } from 'lucide-react';
import type { ReactNode } from 'react';
import CancellationPolicyText from '@/components/catalog/cancellation-policy-text';
import StarRating from '@/components/catalog/star-rating';
import RatingBadge from '@/components/reviews/rating-badge';
import { useTranslation } from '@/hooks/use-translation';
import { amenityIcon } from '@/lib/amenity-icons';
import { formatPrice } from '@/lib/utils';
import { index as hotelsIndex, show } from '@/routes/hotels';
import type {
    AmenityOption,
    CancellationTier,
    CardPrice,
    HotelRating,
    SearchCriteria,
} from '@/types';

type ComparedHotel = {
    id: number;
    name: string;
    slug: string;
    province: string;
    municipality: string;
    stars: number | null;
    rating: HotelRating;
    cover_url: string | null;
    price: CardPrice | null;
    amenity_ids: number[];
    categories: string[];
    cancellation_policy: CancellationTier[];
    activities_count: number;
};

type Props = {
    hotels: ComparedHotel[];
    amenities: AmenityOption[];
    criteria: SearchCriteria;
};

function CompareRow({
    columns,
    label,
    children,
}: {
    columns: string;
    label: string;
    children: ReactNode;
}) {
    return (
        <div
            className="grid gap-x-4 border-b py-3 text-sm"
            style={{ gridTemplateColumns: columns }}
        >
            <div className="pr-4 font-medium text-muted-foreground">
                {label}
            </div>
            {children}
        </div>
    );
}

export default function CatalogCompare({ hotels, amenities, criteria }: Props) {
    const { t, locale } = useTranslation();
    const columns = `12rem repeat(${hotels.length}, minmax(14rem, 1fr))`;

    return (
        <>
            <Head title={t('Compare hotels')} />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
                <Link
                    href={hotelsIndex()}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    {t('Back to the results')}
                </Link>
                <h1 className="font-display text-3xl font-extrabold tracking-tight">
                    {t('Compare hotels')}
                </h1>
                {criteria.check_in && criteria.check_out && (
                    <p className="text-muted-foreground">
                        {t('Prices for :nights nights, :guests guests.', {
                            nights:
                                hotels.find((hotel) => hotel.price)?.price
                                    ?.nights ?? 0,
                            guests: criteria.adults + criteria.children,
                        })}
                    </p>
                )}

                {hotels.length === 0 ? (
                    <p className="rounded-2xl border border-dashed p-10 text-center text-muted-foreground">
                        {t(
                            'These hotels are no longer available. Pick others from the results.',
                        )}
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        {/* Columns share the width; only narrow screens scroll. */}
                        <div
                            style={{
                                minWidth: `${12 + hotels.length * 15}rem`,
                            }}
                        >
                            <div
                                className="grid gap-x-4 pb-4"
                                style={{ gridTemplateColumns: columns }}
                            >
                                <div />
                                {hotels.map((hotel) => (
                                    <div key={hotel.id} className="space-y-2">
                                        <div className="aspect-[4/3] overflow-hidden rounded-2xl bg-secondary">
                                            {hotel.cover_url && (
                                                <img
                                                    src={hotel.cover_url}
                                                    alt=""
                                                    className="size-full object-cover"
                                                />
                                            )}
                                        </div>
                                        <StarRating stars={hotel.stars} />
                                        <Link
                                            href={show(hotel.slug, {
                                                query: {
                                                    ...(criteria.check_in
                                                        ? {
                                                              check_in:
                                                                  criteria.check_in,
                                                          }
                                                        : {}),
                                                    ...(criteria.check_out
                                                        ? {
                                                              check_out:
                                                                  criteria.check_out,
                                                          }
                                                        : {}),
                                                    adults: criteria.adults,
                                                },
                                            })}
                                            className="block font-display text-lg leading-tight font-semibold hover:underline"
                                        >
                                            {hotel.name}
                                        </Link>
                                    </div>
                                ))}
                            </div>

                            <CompareRow columns={columns} label={t('Location')}>
                                {hotels.map((hotel) => (
                                    <div key={hotel.id}>
                                        {hotel.municipality}, {hotel.province}
                                    </div>
                                ))}
                            </CompareRow>
                            <CompareRow
                                columns={columns}
                                label={
                                    criteria.check_in
                                        ? t('Total price')
                                        : t('From, per night')
                                }
                            >
                                {hotels.map((hotel) => (
                                    <div key={hotel.id}>
                                        {hotel.price ? (
                                            <span className="text-lg font-semibold text-primary">
                                                {formatPrice(
                                                    hotel.price.has_dates
                                                        ? hotel.price.total
                                                        : hotel.price.per_night,
                                                    locale,
                                                )}
                                                {hotel.price.discount_percent >
                                                    0 && (
                                                    <span className="ml-2 rounded-full bg-sun px-2 py-0.5 text-xs text-sun-foreground">
                                                        −
                                                        {
                                                            hotel.price
                                                                .discount_percent
                                                        }{' '}
                                                        %
                                                    </span>
                                                )}
                                            </span>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                {t('No room for your group')}
                                            </span>
                                        )}
                                    </div>
                                ))}
                            </CompareRow>
                            <CompareRow
                                columns={columns}
                                label={t('Guest rating')}
                            >
                                {hotels.map((hotel) => (
                                    <div key={hotel.id}>
                                        {hotel.rating.average === null ? (
                                            <span className="text-muted-foreground">
                                                {t('No reviews yet')}
                                            </span>
                                        ) : (
                                            <RatingBadge
                                                rating={hotel.rating}
                                            />
                                        )}
                                    </div>
                                ))}
                            </CompareRow>
                            <CompareRow
                                columns={columns}
                                label={t('Travel style')}
                            >
                                {hotels.map((hotel) => (
                                    <div key={hotel.id}>
                                        {hotel.categories.join(', ') || '—'}
                                    </div>
                                ))}
                            </CompareRow>
                            <CompareRow
                                columns={columns}
                                label={t('Activities')}
                            >
                                {hotels.map((hotel) => (
                                    <div key={hotel.id}>
                                        {hotel.activities_count}
                                    </div>
                                ))}
                            </CompareRow>
                            {amenities.map((amenity) => {
                                const Icon = amenityIcon(amenity.icon);

                                return (
                                    <CompareRow
                                        columns={columns}
                                        key={amenity.id}
                                        label={amenity.name}
                                    >
                                        {hotels.map((hotel) =>
                                            hotel.amenity_ids.includes(
                                                amenity.id,
                                            ) ? (
                                                <span
                                                    key={hotel.id}
                                                    className="inline-flex items-center gap-1 text-primary"
                                                >
                                                    <Check className="size-4" />
                                                    <Icon
                                                        className="size-4"
                                                        aria-hidden
                                                    />
                                                    <span className="sr-only">
                                                        {t('Yes')}
                                                    </span>
                                                </span>
                                            ) : (
                                                <span
                                                    key={hotel.id}
                                                    className="text-muted-foreground"
                                                >
                                                    <Minus className="size-4" />
                                                    <span className="sr-only">
                                                        {t('No')}
                                                    </span>
                                                </span>
                                            ),
                                        )}
                                    </CompareRow>
                                );
                            })}
                            <CompareRow
                                columns={columns}
                                label={t('Cancellation policy')}
                            >
                                {hotels.map((hotel) => (
                                    <div key={hotel.id} className="pr-4">
                                        <CancellationPolicyText
                                            tiers={hotel.cancellation_policy}
                                        />
                                    </div>
                                ))}
                            </CompareRow>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
