import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { index as hotelsIndex } from '@/routes/hotels';

export type CoastProvince = {
    value: string;
    label: string;
    hotels_count: number;
};

/** The stretch of coast each province belongs to (proper names). */
const COAST_NAMES: Record<string, string> = {
    huelva: 'Costa de la Luz',
    cadiz: 'Costa de la Luz',
    malaga: 'Costa del Sol',
    granada: 'Costa Tropical',
    almeria: 'Costa de Almería',
};

/**
 * The five coastal provinces in geographic order, west to east, drawn along
 * a shoreline. Each stop opens the search for that province.
 */
export default function Coastline({
    provinces,
}: {
    provinces: CoastProvince[];
}) {
    const { t } = useTranslation();

    return (
        <nav aria-label={t('Provinces')} className="relative">
            <svg
                viewBox="0 0 1000 120"
                preserveAspectRatio="none"
                className="absolute inset-x-0 top-4 hidden h-16 w-full text-primary/30 md:block"
                aria-hidden
            >
                <path
                    d="M0 70 C 90 30, 160 95, 250 60 S 420 20, 500 55 S 660 100, 750 58 S 900 25, 1000 50"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="3"
                    strokeLinecap="round"
                    strokeDasharray="2 9"
                />
            </svg>

            <ol className="relative grid gap-4 md:grid-cols-5 md:gap-2">
                {provinces.map((province) => (
                    <li key={province.value}>
                        <Link
                            href={hotelsIndex({ query: { q: province.label } })}
                            className="group flex items-center gap-4 rounded-2xl p-3 transition-colors hover:bg-accent md:flex-col md:items-start md:gap-3 md:text-left"
                        >
                            <span
                                className="flex size-10 shrink-0 items-center justify-center rounded-full border-4 border-background bg-primary font-display text-sm font-bold text-primary-foreground shadow-md transition-colors group-hover:bg-sun group-hover:text-sun-foreground md:mt-4"
                                aria-hidden
                            >
                                {province.hotels_count}
                            </span>
                            <span>
                                <span className="block font-display text-xl font-bold">
                                    {province.label}
                                </span>
                                <span className="block text-sm text-muted-foreground">
                                    {COAST_NAMES[province.value]}
                                </span>
                                <span className="text-sm text-primary">
                                    {province.hotels_count === 1
                                        ? t('1 hotel')
                                        : t(':count hotels', {
                                              count: province.hotels_count,
                                          })}
                                </span>
                            </span>
                        </Link>
                    </li>
                ))}
            </ol>
        </nav>
    );
}
