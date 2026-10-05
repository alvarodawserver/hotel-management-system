import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import StarRating from '@/components/catalog/star-rating';
import { useTranslation } from '@/hooks/use-translation';
import { amenityIcon } from '@/lib/amenity-icons';
import { cn, formatPrice } from '@/lib/utils';
import { show } from '@/routes/hotels';
import type { HotelCardData } from '@/types';

type Props = {
    hotel: HotelCardData;
    /** Search parameters (dates, guests) carried over to the hotel page. */
    query?: Record<string, string | number>;
    highlighted?: boolean;
    onHover?: (hotelId: number | null) => void;
    action?: ReactNode;
};

export default function HotelCard({
    hotel,
    query = {},
    highlighted = false,
    onHover,
    action,
}: Props) {
    const { t, locale } = useTranslation();
    const { price } = hotel;

    return (
        <article
            className={cn(
                'group relative flex flex-col overflow-hidden rounded-2xl border bg-card transition-colors',
                highlighted && 'border-primary',
            )}
            onMouseEnter={() => onHover?.(hotel.id)}
            onMouseLeave={() => onHover?.(null)}
        >
            <div className="relative aspect-[4/3] overflow-hidden bg-secondary">
                {hotel.cover_url && (
                    <img
                        src={hotel.cover_url}
                        alt=""
                        loading="lazy"
                        className="size-full object-cover"
                    />
                )}
                {price.discount_percent > 0 && (
                    <span className="absolute top-3 left-3 rounded-full bg-sun px-2.5 py-1 text-sm font-semibold text-sun-foreground">
                        −{price.discount_percent} %
                    </span>
                )}
            </div>

            <div className="flex flex-1 flex-col gap-3 p-4">
                <div className="space-y-1">
                    <StarRating stars={hotel.stars} />
                    <h3 className="font-display text-lg leading-tight font-semibold">
                        <Link
                            href={show(hotel.slug, { query })}
                            className="group-hover:underline after:absolute after:inset-0"
                        >
                            {hotel.name}
                        </Link>
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        {hotel.municipality}, {hotel.province}
                    </p>
                </div>

                {hotel.amenities.length > 0 && (
                    <ul className="flex gap-2 text-muted-foreground">
                        {hotel.amenities.map((amenity) => {
                            const Icon = amenityIcon(amenity.icon);

                            return (
                                <li key={amenity.name} title={amenity.name}>
                                    <Icon className="size-4" aria-hidden />
                                    <span className="sr-only">
                                        {amenity.name}
                                    </span>
                                </li>
                            );
                        })}
                    </ul>
                )}

                <div className="mt-auto flex items-end justify-between gap-3">
                    <div className="relative z-10">{action}</div>
                    <p className="text-right">
                        {price.has_dates ? (
                            <>
                                <span className="block text-xl font-semibold text-primary">
                                    {formatPrice(price.total, locale)}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    {t('Total for :count nights', {
                                        count: price.nights,
                                    })}
                                </span>
                            </>
                        ) : (
                            <>
                                <span className="text-xs text-muted-foreground">
                                    {t('from')}{' '}
                                </span>
                                <span className="text-xl font-semibold text-primary">
                                    {formatPrice(price.per_night, locale)}
                                </span>
                                <span className="block text-xs text-muted-foreground">
                                    {t('per night')}
                                </span>
                            </>
                        )}
                    </p>
                </div>
            </div>
        </article>
    );
}
