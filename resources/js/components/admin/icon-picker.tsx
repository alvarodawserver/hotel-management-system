import { useState } from 'react';
import InputError from '@/components/input-error';
import { useTranslation } from '@/hooks/use-translation';
import { amenityIcon } from '@/lib/amenity-icons';
import { cn } from '@/lib/utils';

/**
 * Grid of the allowed amenity icons; the selected one is submitted as "icon".
 */
export default function IconPicker({
    icons,
    defaultValue,
    error,
}: {
    icons: string[];
    defaultValue?: string;
    error?: string;
}) {
    const { t } = useTranslation();
    const [selected, setSelected] = useState(defaultValue ?? '');

    return (
        <fieldset className="space-y-2">
            <legend className="text-sm font-medium">{t('Icon')}</legend>
            <input type="hidden" name="icon" value={selected} />
            <div className="grid grid-cols-8 gap-1 sm:grid-cols-9">
                {icons.map((name) => {
                    const Icon = amenityIcon(name);

                    return (
                        <button
                            key={name}
                            type="button"
                            title={name}
                            aria-label={name}
                            aria-pressed={selected === name}
                            onClick={() => setSelected(name)}
                            className={cn(
                                'flex aspect-square items-center justify-center rounded-md border transition-colors',
                                selected === name
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'hover:bg-accent',
                            )}
                        >
                            <Icon className="size-5" />
                        </button>
                    );
                })}
            </div>
            <InputError message={error} />
        </fieldset>
    );
}
