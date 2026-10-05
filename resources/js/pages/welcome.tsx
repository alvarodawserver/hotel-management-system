import { Head, Link } from '@inertiajs/react';
import Coastline from '@/components/catalog/coastline';
import type { CoastProvince } from '@/components/catalog/coastline';
import HotelCard from '@/components/catalog/hotel-card';
import SearchBar from '@/components/catalog/search-bar';
import { useTranslation } from '@/hooks/use-translation';
import heroImage from '@/images/arc_18577_m.webp';
import { index as hotelsIndex } from '@/routes/hotels';
import type { HotelCardData } from '@/types';

type Props = {
    provinces: CoastProvince[];
    offers: HotelCardData[];
    newHotels: HotelCardData[];
};

function HotelRow({
    title,
    hotels,
    action,
}: {
    title: string;
    hotels: HotelCardData[];
    action?: React.ReactNode;
}) {
    return (
        <section className="mx-auto max-w-6xl px-4 py-14 sm:px-6">
            <div className="flex items-baseline justify-between gap-4 border-b pb-3">
                <h2 className="font-display text-2xl font-bold tracking-tight">
                    {title}
                </h2>
                {action}
            </div>
            <div className="mt-8 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                {hotels.map((hotel) => (
                    <HotelCard key={hotel.id} hotel={hotel} />
                ))}
            </div>
        </section>
    );
}

export default function Welcome({ provinces, offers, newHotels }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Hotels on the Andalusian coast')} />

            <section className="mx-auto max-w-6xl px-4 pt-10 sm:px-6 lg:pt-16">
                <div className="grid items-end gap-10 lg:grid-cols-[1.1fr_1fr]">
                    <div className="pb-2 lg:pb-10">
                        <h1 className="font-display text-4xl leading-[1.05] font-extrabold tracking-tight text-primary sm:text-5xl lg:text-6xl">
                            {t('Hotels by the sea, from Huelva to Almería')}
                        </h1>
                        <p className="mt-6 max-w-md text-lg leading-relaxed text-muted-foreground">
                            {t(
                                "Pick a destination and dates and see each hotel's final price, offers included.",
                            )}
                        </p>
                    </div>

                    <figure className="mx-auto w-full max-w-[24rem] lg:mr-0">
                        <div className="overflow-hidden rounded-t-full border-8 border-secondary bg-secondary">
                            <img
                                src={heroImage}
                                alt={t(
                                    'A white hotel with tiled roofs among palm trees, facing the sea',
                                )}
                                width={480}
                                height={240}
                                className="aspect-[4/3] w-full object-cover"
                            />
                        </div>
                    </figure>
                </div>

                <div className="relative z-10 -mt-px">
                    <SearchBar variant="hero" />
                </div>
            </section>

            <section className="mx-auto max-w-6xl px-4 pt-16 pb-6 sm:px-6">
                <h2 className="font-display text-2xl font-bold tracking-tight">
                    {t('Choose your stretch of coast')}
                </h2>
                <div className="mt-6">
                    <Coastline provinces={provinces} />
                </div>
            </section>

            {offers.length > 0 && (
                <HotelRow
                    title={t('Offers for tonight')}
                    hotels={offers}
                    action={
                        <span className="text-sm text-muted-foreground">
                            {t('Discount already included in the price')}
                        </span>
                    }
                />
            )}

            {newHotels.length > 0 && (
                <HotelRow
                    title={t('New on the coast')}
                    hotels={newHotels}
                    action={
                        <Link
                            href={hotelsIndex()}
                            className="text-sm font-medium text-primary hover:underline"
                        >
                            {t('See all hotels')}
                        </Link>
                    }
                />
            )}
        </>
    );
}
