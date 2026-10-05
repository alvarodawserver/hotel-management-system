import { Link, usePage } from '@inertiajs/react';
import {
    BedDouble,
    CalendarCheck,
    Globe,
    Hotel,
    LayoutGrid,
    Sparkles,
    Tags,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, home } from '@/routes';
import { index as adminAmenitiesIndex } from '@/routes/admin/amenities';
import { index as adminCategoriesIndex } from '@/routes/admin/categories';
import { index as adminHotelsIndex } from '@/routes/admin/hotels';
import { index as adminRoomTypesIndex } from '@/routes/admin/room-types';
import { index as adminUsersIndex } from '@/routes/admin/users';
import { index as manageHotelsIndex } from '@/routes/manage/hotels';
import { index as manageReservationsIndex } from '@/routes/manage/reservations';
import type { NavItem, UserRole } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Public website',
        href: home(),
        icon: Globe,
    },
];

type NavGroup = { label: string; items: NavItem[] };

const navGroupsByRole: Record<UserRole, NavGroup[]> = {
    admin: [
        {
            label: 'Administration',
            items: [
                { title: 'Users', href: adminUsersIndex(), icon: Users },
                { title: 'Hotels', href: adminHotelsIndex(), icon: Hotel },
                {
                    title: 'Reservations',
                    href: manageReservationsIndex(),
                    icon: CalendarCheck,
                },
            ],
        },
        {
            label: 'Catalogues',
            items: [
                {
                    title: 'Amenities',
                    href: adminAmenitiesIndex(),
                    icon: Sparkles,
                },
                {
                    title: 'Categories',
                    href: adminCategoriesIndex(),
                    icon: Tags,
                },
                {
                    title: 'Room types',
                    href: adminRoomTypesIndex(),
                    icon: BedDouble,
                },
            ],
        },
    ],
    owner: [
        {
            label: 'My business',
            items: [
                { title: 'My hotels', href: manageHotelsIndex(), icon: Hotel },
                {
                    title: 'Reservations',
                    href: manageReservationsIndex(),
                    icon: CalendarCheck,
                },
            ],
        },
    ],
    customer: [],
};

export function AppSidebar() {
    const { auth } = usePage().props;
    const roleNavGroups = auth.user ? navGroupsByRole[auth.user.role] : [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {roleNavGroups.map((group) => (
                    <NavMain
                        key={group.label}
                        items={group.items}
                        label={group.label}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
