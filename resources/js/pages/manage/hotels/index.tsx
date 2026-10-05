import { Head, Link } from '@inertiajs/react';
import { BedDouble, Hotel as HotelIcon, MapPin, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import HotelStatusBadge from '@/components/manage/hotel-status-badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { create, edit, index } from '@/routes/manage/hotels';
import type { HotelSummary } from '@/types';

type OwnerHotel = HotelSummary & {
    province: string;
    municipality: string;
    cover_url: string | null;
    rooms_count: number;
    active_rooms_count: number;
};

type Props = {
    hotels: OwnerHotel[];
    canCreate: boolean;
};

export default function MyHotels({ hotels, canCreate }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('My hotels')} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={t('My hotels')}
                        description={t(
                            'Create your hotels, add rooms and photos, and publish them when they are ready',
                        )}
                    />
                    {canCreate && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus />
                                {t('New hotel')}
                            </Link>
                        </Button>
                    )}
                </div>

                {hotels.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed p-10 text-center">
                        <HotelIcon className="size-8 text-muted-foreground" />
                        <p className="text-muted-foreground">
                            {t(
                                'You have no hotels yet. Create your first one to start receiving bookings.',
                            )}
                        </p>
                        {canCreate && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus />
                                    {t('New hotel')}
                                </Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {hotels.map((hotel) => (
                            <li key={hotel.id}>
                                <Link
                                    href={edit(hotel.id)}
                                    className="group block overflow-hidden rounded-xl border transition-shadow hover:shadow-md"
                                >
                                    <div className="aspect-[16/9] bg-muted">
                                        {hotel.cover_url ? (
                                            <img
                                                src={hotel.cover_url}
                                                alt=""
                                                loading="lazy"
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <div className="flex size-full items-center justify-center text-sm text-muted-foreground">
                                                {t('No photos yet')}
                                            </div>
                                        )}
                                    </div>
                                    <div className="space-y-2 p-4">
                                        <div className="flex items-start justify-between gap-2">
                                            <h2 className="font-medium group-hover:underline">
                                                {hotel.name}
                                            </h2>
                                            <HotelStatusBadge hotel={hotel} />
                                        </div>
                                        <p className="flex items-center gap-1 text-sm text-muted-foreground">
                                            <MapPin className="size-4" />
                                            {hotel.municipality},{' '}
                                            {hotel.province}
                                        </p>
                                        <p className="flex items-center gap-1 text-sm text-muted-foreground">
                                            <BedDouble className="size-4" />
                                            {t(
                                                ':active of :total rooms active',
                                                {
                                                    active: hotel.active_rooms_count,
                                                    total: hotel.rooms_count,
                                                },
                                            )}
                                        </p>
                                        {hotel.is_blocked && (
                                            <p className="text-sm text-destructive">
                                                {t('Blocked: :reason', {
                                                    reason:
                                                        hotel.blocked_reason ??
                                                        '',
                                                })}
                                            </p>
                                        )}
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

MyHotels.layout = {
    breadcrumbs: [{ title: 'My hotels', href: index() }],
};
