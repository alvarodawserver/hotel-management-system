import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Ban, ExternalLink } from 'lucide-react';
import HotelStatusBadge from '@/components/manage/hotel-status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { index as adminHotelsIndex } from '@/routes/admin/hotels';
import { show } from '@/routes/hotels';
import { edit, index as manageHotelsIndex } from '@/routes/manage/hotels';
import { index as activitiesIndex } from '@/routes/manage/hotels/activities';
import { index as imagesIndex } from '@/routes/manage/hotels/images';
import { index as offersIndex } from '@/routes/manage/hotels/offers';
import { index as roomsIndex } from '@/routes/manage/hotels/rooms';
import type { HotelSummary } from '@/types';

/**
 * Title, status and section tabs shown at the top of every hotel
 * management page.
 */
export default function HotelManageHeader({ hotel }: { hotel: HotelSummary }) {
    const { auth } = usePage().props;
    const { t } = useTranslation();
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const tabs = [
        { title: t('Details'), href: edit(hotel.id) },
        { title: t('Rooms'), href: roomsIndex(hotel.id) },
        { title: t('Photos'), href: imagesIndex(hotel.id) },
        { title: t('Activities'), href: activitiesIndex(hotel.id) },
        { title: t('Offers'), href: offersIndex(hotel.id) },
    ];

    const backHref =
        auth.user.role === 'admin' ? adminHotelsIndex() : manageHotelsIndex();

    return (
        <div className="space-y-4">
            <Link
                href={backHref}
                className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft className="size-4" />
                {auth.user.role === 'admin' ? t('All hotels') : t('My hotels')}
            </Link>

            <div className="flex flex-wrap items-center gap-3">
                <h1 className="text-xl font-semibold tracking-tight">
                    {hotel.name}
                </h1>
                <HotelStatusBadge hotel={hotel} />
                {hotel.owner_name && auth.user.role === 'admin' && (
                    <span className="text-sm text-muted-foreground">
                        {t('Owner: :name', { name: hotel.owner_name })}
                    </span>
                )}
                <Link
                    href={show(hotel.slug)}
                    className="ml-auto inline-flex items-center gap-1 text-sm text-primary hover:underline"
                >
                    <ExternalLink className="size-4" />
                    {hotel.is_visible && !hotel.is_blocked
                        ? t('View public page')
                        : t('Preview public page')}
                </Link>
            </div>

            {hotel.is_blocked && (
                <Alert variant="destructive">
                    <Ban />
                    <AlertTitle>
                        {t('This hotel has been blocked by an administrator')}
                    </AlertTitle>
                    <AlertDescription>
                        {t(
                            'It is not shown in the catalogue even if it is visible. Reason: :reason',
                            { reason: hotel.blocked_reason ?? '' },
                        )}
                    </AlertDescription>
                </Alert>
            )}

            <nav
                aria-label={t('Hotel sections')}
                className="flex gap-1 overflow-x-auto border-b"
            >
                {tabs.map((tab) => {
                    const isActive = isCurrentOrParentUrl(tab.href);

                    return (
                        <Link
                            key={tab.title}
                            href={tab.href}
                            aria-current={isActive ? 'page' : undefined}
                            className={cn(
                                '-mb-px border-b-2 px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors',
                                isActive
                                    ? 'border-primary text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {tab.title}
                        </Link>
                    );
                })}
            </nav>
        </div>
    );
}
