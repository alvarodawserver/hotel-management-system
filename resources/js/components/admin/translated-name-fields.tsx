import { usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';

const languageNames: Record<string, string> = {
    es: 'Name in Spanish',
    en: 'Name in English',
};

/**
 * One name input per available locale, submitted as name[es], name[en]…
 */
export default function TranslatedNameFields({
    translations,
    errors,
}: {
    translations?: Record<string, string>;
    errors: Partial<Record<string, string>>;
}) {
    const { availableLocales } = usePage().props;
    const { t } = useTranslation();

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            {availableLocales.map((locale) => (
                <div key={locale} className="grid gap-2">
                    <Label htmlFor={`name-${locale}`}>
                        {t(languageNames[locale] ?? locale)}
                    </Label>
                    <Input
                        id={`name-${locale}`}
                        name={`name[${locale}]`}
                        lang={locale}
                        required
                        maxLength={100}
                        defaultValue={translations?.[locale] ?? ''}
                    />
                    <InputError message={errors[`name.${locale}`]} />
                </div>
            ))}
        </div>
    );
}
