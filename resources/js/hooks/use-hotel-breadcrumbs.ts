import { setLayoutProps, usePage } from '@inertiajs/react';
import { index as adminHotelsIndex } from '@/routes/admin/hotels';
import { edit, index as manageHotelsIndex } from '@/routes/manage/hotels';
import type { BreadcrumbItem, HotelSummary } from '@/types';

/**
 * Set the breadcrumbs of a hotel management page: hotel list → hotel →
 * optional section. Admins come from the admin hotel list.
 */
export function useHotelBreadcrumbs(
    hotel: Pick<HotelSummary, 'id' | 'name'>,
    section?: BreadcrumbItem,
): void {
    const { auth } = usePage().props;
    const isAdmin = auth.user.role === 'admin';

    setLayoutProps({
        breadcrumbs: [
            isAdmin
                ? { title: 'Hotels', href: adminHotelsIndex() }
                : { title: 'My hotels', href: manageHotelsIndex() },
            { title: hotel.name, href: edit(hotel.id) },
            ...(section ? [section] : []),
        ],
    });
}
