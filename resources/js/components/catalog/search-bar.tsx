import { router, useHttp } from '@inertiajs/react';
import { Building2, MapPin, Search } from 'lucide-react';
import { useEffect, useId, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { index as destinationsIndex } from '@/routes/destinations';
import { index as hotelsIndex, show } from '@/routes/hotels';
import type { SearchCriteria } from '@/types';

type Suggestion = {
    type: 'province' | 'municipality' | 'hotel';
    label: string;
    value: string;
    slug?: string;
};

type Props = {
    criteria?: Partial<SearchCriteria>;
    /** "hero" is the large home page version; "compact" sits above results. */
    variant?: 'hero' | 'compact';
    /** Extra filters to keep when searching again from the results page. */
    keep?: Record<string, unknown>;
};

function todayIso(): string {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

    return now.toISOString().slice(0, 10);
}

/**
 * Destination (with suggestions), dates and guests. Submitting opens the
 * results page with everything in the URL, so searches can be shared.
 */
export default function SearchBar({
    criteria = {},
    variant = 'compact',
    keep = {},
}: Props) {
    const { t } = useTranslation();
    const listId = useId();
    const [destination, setDestination] = useState(criteria.q ?? '');
    const [checkIn, setCheckIn] = useState(criteria.check_in ?? '');
    const [checkOut, setCheckOut] = useState(criteria.check_out ?? '');
    const [adults, setAdults] = useState(String(criteria.adults ?? 2));
    const [children, setChildren] = useState(String(criteria.children ?? 0));
    const [suggestions, setSuggestions] = useState<Suggestion[]>([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const http = useHttp<Record<string, never>, { suggestions: Suggestion[] }>(
        {},
    );

    useEffect(() => {
        const query = destination.trim();

        if (query.length < 2) {
            setSuggestions([]);

            return;
        }

        const timer = setTimeout(() => {
            http.get(destinationsIndex.url({ query: { q: query } }))
                .then((response) => setSuggestions(response.suggestions))
                .catch(() => setSuggestions([]));
        }, 250);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [destination]);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const query = Object.fromEntries(
            Object.entries({
                ...keep,
                q: destination.trim(),
                check_in: checkIn,
                check_out: checkOut,
                adults,
                children: children === '0' ? '' : children,
            }).filter(([, value]) => value !== '' && value !== null),
        );

        router.get(hotelsIndex.url(), query);
    };

    const choose = (suggestion: Suggestion) => {
        setShowSuggestions(false);

        if (suggestion.type === 'hotel' && suggestion.slug) {
            router.visit(show.url(suggestion.slug));

            return;
        }

        setDestination(suggestion.value);
    };

    const isHero = variant === 'hero';
    const fieldClass = isHero ? 'h-12 text-base' : '';

    return (
        <form
            onSubmit={submit}
            role="search"
            className={cn(
                'grid gap-3 rounded-2xl bg-card text-card-foreground',
                isHero
                    ? 'border p-4 shadow-sm sm:p-5 lg:grid-cols-[2fr_1fr_1fr_0.7fr_0.7fr_auto] lg:items-end'
                    : 'border p-3 md:grid-cols-[2fr_1fr_1fr_0.6fr_0.6fr_auto] md:items-end',
            )}
        >
            <div className="relative grid gap-1.5">
                <Label htmlFor={`${listId}-q`}>
                    {t('Where are you going?')}
                </Label>
                <Input
                    id={`${listId}-q`}
                    value={destination}
                    onChange={(event) => {
                        setDestination(event.target.value);
                        setShowSuggestions(true);
                    }}
                    onFocus={() => setShowSuggestions(true)}
                    onBlur={() =>
                        setTimeout(() => setShowSuggestions(false), 150)
                    }
                    placeholder={t('Town, province or hotel')}
                    autoComplete="off"
                    role="combobox"
                    aria-expanded={showSuggestions && suggestions.length > 0}
                    aria-controls={`${listId}-list`}
                    className={fieldClass}
                />
                {showSuggestions && suggestions.length > 0 && (
                    <ul
                        id={`${listId}-list`}
                        role="listbox"
                        className="absolute top-full right-0 left-0 z-50 mt-1 overflow-hidden rounded-xl border bg-popover py-1 text-popover-foreground shadow-lg"
                    >
                        {suggestions.map((suggestion) => (
                            <li
                                key={`${suggestion.type}-${suggestion.label}`}
                                role="option"
                                aria-selected={false}
                            >
                                <button
                                    type="button"
                                    onMouseDown={(event) =>
                                        event.preventDefault()
                                    }
                                    onClick={() => choose(suggestion)}
                                    className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-accent"
                                >
                                    {suggestion.type === 'hotel' ? (
                                        <Building2 className="size-4 text-muted-foreground" />
                                    ) : (
                                        <MapPin className="size-4 text-muted-foreground" />
                                    )}
                                    {suggestion.label}
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor={`${listId}-in`}>{t('Check-in')}</Label>
                <Input
                    id={`${listId}-in`}
                    type="date"
                    min={todayIso()}
                    value={checkIn}
                    onChange={(event) => {
                        setCheckIn(event.target.value);

                        if (checkOut && checkOut <= event.target.value) {
                            setCheckOut('');
                        }
                    }}
                    required={checkOut !== ''}
                    className={fieldClass}
                />
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor={`${listId}-out`}>{t('Check-out')}</Label>
                <Input
                    id={`${listId}-out`}
                    type="date"
                    min={checkIn || todayIso()}
                    value={checkOut}
                    onChange={(event) => setCheckOut(event.target.value)}
                    required={checkIn !== ''}
                    className={fieldClass}
                />
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor={`${listId}-adults`}>{t('Adults')}</Label>
                <Input
                    id={`${listId}-adults`}
                    type="number"
                    min={1}
                    max={10}
                    value={adults}
                    onChange={(event) => setAdults(event.target.value)}
                    className={fieldClass}
                />
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor={`${listId}-children`}>{t('Children')}</Label>
                <Input
                    id={`${listId}-children`}
                    type="number"
                    min={0}
                    max={10}
                    value={children}
                    onChange={(event) => setChildren(event.target.value)}
                    className={fieldClass}
                />
            </div>

            <Button
                type="submit"
                size={isHero ? 'lg' : 'default'}
                className={isHero ? 'h-12 px-6' : ''}
            >
                <Search />
                {t('Search')}
            </Button>
        </form>
    );
}
