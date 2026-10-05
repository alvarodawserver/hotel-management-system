import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLayout from '@/layouts/app-layout';
import PublicLayout from '@/layouts/public-layout';
import type { BreadcrumbItem } from '@/types';

/**
 * For pages every role uses (settings): customers get the public layout,
 * owners and admins the management layout with the sidebar.
 */
export default function RoleLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: ReactNode;
}) {
    const { auth } = usePage().props;

    if (!auth.user || auth.user.role === 'customer') {
        return (
            <PublicLayout>
                <div className="mx-auto max-w-5xl">{children}</div>
            </PublicLayout>
        );
    }

    return <AppLayout breadcrumbs={breadcrumbs}>{children}</AppLayout>;
}
