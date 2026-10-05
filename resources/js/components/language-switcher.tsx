import { router, usePage } from '@inertiajs/react';
import type { HTMLAttributes } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { update } from '@/routes/locale';

const localeNames: Record<string, string> = {
    es: 'Español',
    en: 'English',
};

export default function LanguageSwitcher({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { availableLocales } = usePage().props;
    const { t, locale } = useTranslation();

    const switchLocale = (newLocale: string) => {
        if (newLocale === locale) {
            return;
        }

        router.post(
            update.url(),
            { locale: newLocale },
            { preserveScroll: true },
        );
    };

    return (
        <div
            role="group"
            aria-label={t('Language')}
            className={cn(
                'inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800',
                className,
            )}
            {...props}
        >
            {availableLocales.map((availableLocale) => (
                <button
                    key={availableLocale}
                    type="button"
                    lang={availableLocale}
                    title={localeNames[availableLocale] ?? availableLocale}
                    aria-pressed={locale === availableLocale}
                    onClick={() => switchLocale(availableLocale)}
                    className={cn(
                        'rounded-md px-2.5 py-1 text-xs font-medium uppercase transition-colors',
                        locale === availableLocale
                            ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                            : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                    )}
                >
                    {availableLocale}
                </button>
            ))}
        </div>
    );
}
