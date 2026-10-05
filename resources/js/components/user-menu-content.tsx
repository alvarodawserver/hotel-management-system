import { Link, router } from '@inertiajs/react';
import { CalendarCheck, LayoutGrid, LogOut, Settings } from 'lucide-react';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard, logout } from '@/routes';
import { edit } from '@/routes/profile';
import { index as reservationsIndex } from '@/routes/reservations';
import type { User } from '@/types';

type Props = {
    user: User;
    /** Link to the management dashboard for owners and admins (public layout). */
    showManagementLink?: boolean;
};

export function UserMenuContent({ user, showManagementLink = false }: Props) {
    const cleanup = useMobileNavigation();
    const { t } = useTranslation();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                {showManagementLink && user.role !== 'customer' && (
                    <DropdownMenuItem asChild>
                        <Link
                            className="block w-full cursor-pointer"
                            href={dashboard()}
                            onClick={cleanup}
                        >
                            <LayoutGrid className="mr-2" />
                            {t('Management panel')}
                        </Link>
                    </DropdownMenuItem>
                )}
                {user.role === 'customer' && (
                    <DropdownMenuItem asChild>
                        <Link
                            className="block w-full cursor-pointer"
                            href={reservationsIndex()}
                            onClick={cleanup}
                        >
                            <CalendarCheck className="mr-2" />
                            {t('My reservations')}
                        </Link>
                    </DropdownMenuItem>
                )}
                <DropdownMenuItem asChild>
                    <Link
                        className="block w-full cursor-pointer"
                        href={edit()}
                        prefetch
                        onClick={cleanup}
                    >
                        <Settings className="mr-2" />
                        {t('Settings')}
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link
                    className="block w-full cursor-pointer"
                    href={logout()}
                    as="button"
                    onClick={handleLogout}
                    data-test="logout-button"
                >
                    <LogOut className="mr-2" />
                    {t('Log out')}
                </Link>
            </DropdownMenuItem>
        </>
    );
}
