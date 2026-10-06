import { Head, usePage } from '@inertiajs/react';
import {
    BedDouble,
    CalendarCheck,
    Euro,
    Hotel,
    Star,
    UserPlus,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { ArrivalsList } from '@/components/dashboard/arrivals-list';
import {
    AttentionList,
    ListSkeleton,
} from '@/components/dashboard/attention-list';
import { BarList } from '@/components/dashboard/bar-list';
import {
    RevenueChart,
    RevenueChartSkeleton,
} from '@/components/dashboard/revenue-chart';
import {
    monthChange,
    StatTile,
    StatTilesSkeleton,
} from '@/components/dashboard/stat-tile';
import { useTranslation } from '@/hooks/use-translation';
import { cn, formatPrice } from '@/lib/utils';
import { dashboard } from '@/routes';
import type {
    AdminKpis,
    AttentionItem,
    MonthRevenue,
    OwnerKpis,
    ProvinceStays,
    TopHotel,
    UpcomingArrival,
} from '@/types';

type Props = {
    today: string;
    kpis?: OwnerKpis | AdminKpis;
    months?: MonthRevenue[];
    attention?: AttentionItem[];
    arrivals?: UpcomingArrival[];
    topHotels?: TopHotel[];
    provinces?: ProvinceStays[];
};

function Panel({
    title,
    description,
    className,
    children,
}: {
    title: string;
    description?: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <section className={cn('rounded-xl border bg-card p-5', className)}>
            <h2 className="font-semibold">{title}</h2>
            {description && (
                <p className="text-sm text-muted-foreground">{description}</p>
            )}
            <div className="mt-4">{children}</div>
        </section>
    );
}

function OwnerTiles({ kpis }: { kpis: OwnerKpis }) {
    const { t, locale } = useTranslation();

    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatTile
                icon={Euro}
                label={t('Revenue this month')}
                value={formatPrice(kpis.revenue.current, locale)}
                change={monthChange(kpis.revenue)}
            />
            <StatTile
                icon={BedDouble}
                label={t('Occupancy, next 30 days')}
                value={`${kpis.occupancy.percent} %`}
                hint={t(':booked of :available nights booked', {
                    booked: kpis.occupancy.booked_nights,
                    available: kpis.occupancy.available_nights,
                })}
            />
            <StatTile
                icon={CalendarCheck}
                label={t('Arrivals today')}
                value={String(kpis.arrivals.today)}
                hint={t(':count in the next 7 days', {
                    count: kpis.arrivals.week,
                })}
            />
            <StatTile
                icon={Star}
                label={t('Guest rating')}
                value={
                    kpis.rating.average === null
                        ? '–'
                        : kpis.rating.average.toLocaleString(locale, {
                              minimumFractionDigits: 1,
                          })
                }
                hint={
                    kpis.rating.count === 1
                        ? t('1 review')
                        : t(':count reviews', { count: kpis.rating.count })
                }
            />
        </div>
    );
}

function AdminTiles({ kpis }: { kpis: AdminKpis }) {
    const { t, locale } = useTranslation();

    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatTile
                icon={Euro}
                label={t('Revenue this month')}
                value={formatPrice(kpis.revenue.current, locale)}
                change={monthChange(kpis.revenue)}
            />
            <StatTile
                icon={CalendarCheck}
                label={t('Bookings this month')}
                value={String(kpis.bookings.current)}
                change={monthChange(kpis.bookings)}
            />
            <StatTile
                icon={Hotel}
                label={t('Published hotels')}
                value={String(kpis.hotels.published)}
                hint={t(':count on the platform', {
                    count: kpis.hotels.total,
                })}
            />
            <StatTile
                icon={UserPlus}
                label={t('New users this month')}
                value={String(kpis.new_users.current)}
                change={monthChange(kpis.new_users)}
            />
        </div>
    );
}

export default function Dashboard({
    today,
    kpis,
    months,
    attention,
    arrivals,
    topHotels,
    provinces,
}: Props) {
    const { t, locale } = useTranslation();
    const { user } = usePage().props.auth;
    const isAdmin = user.role === 'admin';

    return (
        <>
            <Head title={t('Dashboard')} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <header>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {t('Hello, :name', { name: user.name.split(' ')[0] })}
                    </h1>
                    <p className="text-muted-foreground">
                        {isAdmin
                            ? t('This is how the platform is doing.')
                            : t('This is how your hotels are doing.')}
                    </p>
                </header>

                {!kpis ? (
                    <StatTilesSkeleton />
                ) : isAdmin ? (
                    <AdminTiles kpis={kpis as AdminKpis} />
                ) : (
                    <OwnerTiles kpis={kpis as OwnerKpis} />
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <Panel
                        title={t('Revenue by month')}
                        description={t(
                            'Last 12 months, by check-in date, after refunds.',
                        )}
                        className="lg:col-span-2"
                    >
                        {months ? (
                            <RevenueChart months={months} />
                        ) : (
                            <RevenueChartSkeleton />
                        )}
                    </Panel>

                    <Panel title={t('Needs your attention')}>
                        {attention ? (
                            <AttentionList items={attention} />
                        ) : (
                            <ListSkeleton />
                        )}
                    </Panel>
                </div>

                {isAdmin ? (
                    <div className="grid gap-6 lg:grid-cols-2">
                        <Panel
                            title={t('Top hotels')}
                            description={t(
                                'By revenue over the last 12 months.',
                            )}
                        >
                            {topHotels ? (
                                <BarList
                                    rows={topHotels.map((hotel) => ({
                                        label: hotel.hotel,
                                        value: hotel.revenue,
                                        display: formatPrice(
                                            hotel.revenue,
                                            locale,
                                        ),
                                    }))}
                                />
                            ) : (
                                <ListSkeleton rows={5} />
                            )}
                        </Panel>
                        <Panel
                            title={t('Stays by province')}
                            description={t(
                                'Confirmed stays over the last 12 months.',
                            )}
                        >
                            {provinces ? (
                                <BarList
                                    rows={provinces.map((province) => ({
                                        label: province.province,
                                        value: province.stays,
                                        display: String(province.stays),
                                    }))}
                                />
                            ) : (
                                <ListSkeleton rows={5} />
                            )}
                        </Panel>
                    </div>
                ) : (
                    <Panel
                        title={t('Upcoming arrivals')}
                        description={t('Confirmed guests in the next 7 days.')}
                    >
                        {arrivals ? (
                            <ArrivalsList arrivals={arrivals} today={today} />
                        ) : (
                            <ListSkeleton />
                        )}
                    </Panel>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
