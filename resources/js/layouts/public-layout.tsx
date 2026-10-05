import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import LanguageSwitcher from '@/components/language-switcher';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/hooks/use-translation';
import { home, login, register } from '@/routes';
import { index as hotelsIndex } from '@/routes/hotels';

const PROVINCES = ['Huelva', 'Cádiz', 'Málaga', 'Granada', 'Almería'];

/**
 * Header and footer for the public pages and the customer's own pages.
 * The management sidebar is never shown here.
 */
export default function PublicLayout({ children }: { children: ReactNode }) {
    const { auth, name } = usePage().props;
    const { t } = useTranslation();
    const getInitials = useInitials();

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="sticky top-0 z-40 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
                <div className="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6">
                    <Link
                        href={home()}
                        className="flex items-center gap-2 font-display text-lg font-bold tracking-tight text-primary"
                    >
                        <AppLogoIcon className="size-7 fill-current" />
                        {name}
                    </Link>

                    <nav className="hidden sm:block">
                        <Link
                            href={hotelsIndex()}
                            className="text-sm font-medium text-muted-foreground hover:text-foreground"
                        >
                            {t('Hotels')}
                        </Link>
                    </nav>

                    <div className="ml-auto flex items-center gap-3">
                        <LanguageSwitcher className="hidden sm:inline-flex" />

                        {auth.user ? (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        className="size-10 rounded-full p-1"
                                        aria-label={t('Account menu')}
                                    >
                                        <Avatar className="size-8">
                                            <AvatarFallback className="bg-secondary text-secondary-foreground">
                                                {getInitials(auth.user.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    className="w-56"
                                    align="end"
                                >
                                    <UserMenuContent
                                        user={auth.user}
                                        showManagementLink
                                    />
                                </DropdownMenuContent>
                            </DropdownMenu>
                        ) : (
                            <>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href={login()}>{t('Log in')}</Link>
                                </Button>
                                <Button size="sm" asChild>
                                    <Link href={register()}>
                                        {t('Register')}
                                    </Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="bg-sidebar text-sidebar-foreground">
                <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-[2fr_1fr_1fr] sm:px-6">
                    <div className="space-y-3">
                        <p className="font-display text-xl font-bold">{name}</p>
                        <p className="max-w-sm text-sm text-sidebar-foreground/75">
                            {t(
                                'Hotels on the Andalusian coast, from the Costa de la Luz to the Costa de Almería.',
                            )}
                        </p>
                    </div>
                    <div className="space-y-3">
                        <p className="text-sm font-semibold">
                            {t('Destinations')}
                        </p>
                        <ul className="space-y-1.5 text-sm text-sidebar-foreground/75">
                            {PROVINCES.map((province) => (
                                <li key={province}>
                                    <Link
                                        href={hotelsIndex({
                                            query: { q: province },
                                        })}
                                        className="hover:text-sidebar-foreground hover:underline"
                                    >
                                        {province}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className="space-y-3">
                        <p className="text-sm font-semibold">{t('Language')}</p>
                        <LanguageSwitcher />
                    </div>
                </div>
                <div className="border-t border-sidebar-border">
                    <p className="mx-auto max-w-7xl px-4 py-4 text-xs text-sidebar-foreground/60 sm:px-6">
                        © {new Date().getFullYear()} {name}. {t('Maps by')}{' '}
                        <a
                            href="https://www.openstreetmap.org/copyright"
                            className="underline"
                            target="_blank"
                            rel="noreferrer"
                        >
                            OpenStreetMap
                        </a>
                        .
                    </p>
                </div>
            </footer>
        </div>
    );
}
