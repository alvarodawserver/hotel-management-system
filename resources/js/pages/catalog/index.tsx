import { Form, Head, Link, router } from '@inertiajs/react';
import { List, Map as MapIcon, SlidersHorizontal } from 'lucide-react';
import { useState } from 'react';
import HotelSearchController from '@/actions/App/Http/Controllers/Catalog/HotelSearchController';
import CompareTray from '@/components/catalog/compare-tray';
import HotelCard from '@/components/catalog/hotel-card';
import HotelMap from '@/components/catalog/hotel-map';
import SearchBar from '@/components/catalog/search-bar';
import NativeSelect from '@/components/native-select';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCompare } from '@/hooks/use-compare';
import { useTranslation } from '@/hooks/use-translation';
import { amenityIcon } from '@/lib/amenity-icons';
import { cn } from '@/lib/utils';
import { index as hotelsIndex, show } from '@/routes/hotels';
import type {
    AmenityOption,
    CategoryOption,
    HotelCardData,
    MapPin,
    Paginated,
    SearchCriteria,
} from '@/types';

type Props = {
    results: Paginated<HotelCardData>;
    criteria: SearchCriteria;
    amenities: AmenityOption[];
    categories: CategoryOption[];
};

/** The search parameters that change prices, carried to hotel and compare pages. */
function stayQuery(criteria: SearchCriteria): Record<string, string | number> {
    return Object.fromEntries(
        Object.entries({
            check_in: criteria.check_in,
            check_out: criteria.check_out,
            adults: criteria.adults,
            children: criteria.children || null,
        }).filter(([, value]) => value !== null && value !== ''),
    ) as Record<string, string | number>;
}

/** Remove empty values so the URL only carries the filters in use. */
function dropEmpty<T extends Record<string, unknown>>(data: T): T {
    return Object.fromEntries(
        Object.entries(data).filter(
            ([, value]) =>
                value !== '' &&
                value !== null &&
                value !== undefined &&
                !(Array.isArray(value) && value.length === 0),
        ),
    ) as T;
}

export default function CatalogIndex({
    results,
    criteria,
    amenities,
    categories,
}: Props) {
    const { t } = useTranslation();
    const [hoveredId, setHoveredId] = useState<number | null>(null);
    const [mobileView, setMobileView] = useState<'list' | 'map'>('list');
    const [filtersOpen, setFiltersOpen] = useState(false);
    const compare = useCompare();
    const query = stayQuery(criteria);

    const pins: MapPin[] = results.data
        .filter((hotel) => hotel.latitude !== null && hotel.longitude !== null)
        .map((hotel) => ({
            id: hotel.id,
            name: hotel.name,
            latitude: hotel.latitude as number,
            longitude: hotel.longitude as number,
            href: show.url(hotel.slug, { query }),
            label: `${hotel.municipality}, ${hotel.province}`,
        }));

    const changeSort = (sort: string) => {
        router.get(hotelsIndex.url(), dropEmpty({ ...criteria, sort }), {
            preserveScroll: true,
        });
    };

    const filters = (
        <Form
            {...HotelSearchController.form()}
            transform={(data) => dropEmpty(data)}
            options={{ preserveScroll: true }}
            className="space-y-6"
        >
            {criteria.q && <input type="hidden" name="q" value={criteria.q} />}
            {criteria.check_in && (
                <input
                    type="hidden"
                    name="check_in"
                    value={criteria.check_in}
                />
            )}
            {criteria.check_out && (
                <input
                    type="hidden"
                    name="check_out"
                    value={criteria.check_out}
                />
            )}
            <input type="hidden" name="adults" value={criteria.adults} />
            {criteria.children > 0 && (
                <input
                    type="hidden"
                    name="children"
                    value={criteria.children}
                />
            )}
            <input type="hidden" name="sort" value={criteria.sort} />

            <fieldset className="space-y-2">
                <legend className="text-sm font-semibold">
                    {t('Price per night (€)')}
                </legend>
                <div className="flex items-center gap-2">
                    <Input
                        name="price_min"
                        type="number"
                        min={0}
                        placeholder={t('Min')}
                        aria-label={t('Minimum price')}
                        defaultValue={criteria.price_min ?? ''}
                    />
                    <span className="text-muted-foreground">–</span>
                    <Input
                        name="price_max"
                        type="number"
                        min={0}
                        placeholder={t('Max')}
                        aria-label={t('Maximum price')}
                        defaultValue={criteria.price_max ?? ''}
                    />
                </div>
            </fieldset>

            <div className="grid gap-2">
                <Label htmlFor="stars" className="font-semibold">
                    {t('Stars')}
                </Label>
                <NativeSelect
                    id="stars"
                    name="stars"
                    defaultValue={criteria.stars ? String(criteria.stars) : ''}
                    placeholder={t('Any')}
                    options={[2, 3, 4, 5].map((stars) => ({
                        value: String(stars),
                        label: t(':count stars or more', { count: stars }),
                    }))}
                />
            </div>

            <fieldset className="space-y-2">
                <legend className="text-sm font-semibold">
                    {t('Travel style')}
                </legend>
                {categories.map((category) => (
                    <Label
                        key={category.id}
                        className="flex items-center gap-2 font-normal"
                    >
                        <Checkbox
                            name="categories[]"
                            value={String(category.id)}
                            defaultChecked={criteria.categories.includes(
                                category.id,
                            )}
                        />
                        {category.name}
                    </Label>
                ))}
            </fieldset>

            <fieldset className="space-y-2">
                <legend className="text-sm font-semibold">
                    {t('Amenities')}
                </legend>
                {amenities.map((amenity) => {
                    const Icon = amenityIcon(amenity.icon);

                    return (
                        <Label
                            key={amenity.id}
                            className="flex items-center gap-2 font-normal"
                        >
                            <Checkbox
                                name="amenities[]"
                                value={String(amenity.id)}
                                defaultChecked={criteria.amenities.includes(
                                    amenity.id,
                                )}
                            />
                            <Icon className="size-4 text-muted-foreground" />
                            {amenity.name}
                        </Label>
                    );
                })}
            </fieldset>

            <div className="flex gap-2">
                <Button type="submit" className="flex-1">
                    {t('Apply filters')}
                </Button>
                <Button variant="ghost" asChild>
                    <Link
                        href={hotelsIndex({
                            query: dropEmpty({
                                q: criteria.q,
                                ...query,
                            }) as Record<string, string>,
                        })}
                    >
                        {t('Clear')}
                    </Link>
                </Button>
            </div>
        </Form>
    );

    return (
        <>
            <Head
                title={
                    criteria.q
                        ? t('Hotels in :place', { place: criteria.q })
                        : t('Hotels')
                }
            />

            <div className="mx-auto max-w-[96rem] space-y-6 px-4 py-6 sm:px-6">
                <SearchBar
                    criteria={criteria}
                    keep={{
                        price_min: criteria.price_min,
                        price_max: criteria.price_max,
                        stars: criteria.stars,
                        amenities: criteria.amenities,
                        categories: criteria.categories,
                        sort: criteria.sort,
                    }}
                />

                <div className="grid gap-6 lg:grid-cols-[16rem_1fr] xl:grid-cols-[16rem_1fr_30rem]">
                    <aside className="lg:sticky lg:top-20 lg:self-start">
                        <Button
                            variant="outline"
                            className="w-full lg:hidden"
                            onClick={() => setFiltersOpen((open) => !open)}
                            aria-expanded={filtersOpen}
                        >
                            <SlidersHorizontal />
                            {t('Filters')}
                        </Button>
                        <div
                            className={cn(
                                'mt-4 lg:mt-0 lg:block',
                                filtersOpen ? 'block' : 'hidden',
                            )}
                        >
                            {filters}
                        </div>
                    </aside>

                    <section
                        aria-label={t('Results')}
                        className="min-w-0 space-y-4"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h1 className="font-display text-xl font-bold">
                                {results.total === 1
                                    ? t('1 hotel found')
                                    : t(':count hotels found', {
                                          count: results.total,
                                      })}
                            </h1>
                            <div className="flex items-center gap-2">
                                <Label htmlFor="sort" className="sr-only">
                                    {t('Sort by')}
                                </Label>
                                <NativeSelect
                                    id="sort"
                                    value={criteria.sort}
                                    onChange={(event) =>
                                        changeSort(event.target.value)
                                    }
                                    className="w-auto"
                                    options={[
                                        {
                                            value: 'recommended',
                                            label: t('Recommended'),
                                        },
                                        {
                                            value: 'rating',
                                            label: t('Top rated'),
                                        },
                                        {
                                            value: 'price_asc',
                                            label: t('Price: low to high'),
                                        },
                                        {
                                            value: 'price_desc',
                                            label: t('Price: high to low'),
                                        },
                                    ]}
                                />
                                <Button
                                    variant="outline"
                                    size="icon"
                                    className="xl:hidden"
                                    onClick={() =>
                                        setMobileView((view) =>
                                            view === 'list' ? 'map' : 'list',
                                        )
                                    }
                                    aria-label={
                                        mobileView === 'list'
                                            ? t('Show map')
                                            : t('Show list')
                                    }
                                >
                                    {mobileView === 'list' ? (
                                        <MapIcon />
                                    ) : (
                                        <List />
                                    )}
                                </Button>
                            </div>
                        </div>

                        {mobileView === 'map' && (
                            <HotelMap
                                pins={pins}
                                className="h-[60svh] xl:hidden"
                            />
                        )}

                        {results.data.length === 0 ? (
                            <div className="rounded-2xl border border-dashed p-10 text-center">
                                <p className="font-medium">
                                    {t('No hotel matches this search.')}
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {t(
                                        'Try other dates, fewer filters or a nearby town.',
                                    )}
                                </p>
                            </div>
                        ) : (
                            <div
                                className={cn(
                                    'grid gap-5 sm:grid-cols-2',
                                    mobileView === 'map' && 'hidden xl:grid',
                                )}
                            >
                                {results.data.map((hotel) => {
                                    const selected = compare.isSelected(
                                        hotel.id,
                                    );

                                    return (
                                        <HotelCard
                                            key={hotel.id}
                                            hotel={hotel}
                                            query={query}
                                            highlighted={hoveredId === hotel.id}
                                            onHover={setHoveredId}
                                            action={
                                                <Label className="flex cursor-pointer items-center gap-2 text-sm font-normal">
                                                    <Checkbox
                                                        checked={selected}
                                                        disabled={
                                                            !selected &&
                                                            compare.isFull
                                                        }
                                                        onCheckedChange={() =>
                                                            compare.toggle({
                                                                id: hotel.id,
                                                                name: hotel.name,
                                                            })
                                                        }
                                                    />
                                                    {t('Compare')}
                                                </Label>
                                            }
                                        />
                                    );
                                })}
                            </div>
                        )}

                        <Pagination paginator={results} />
                    </section>

                    <div className="hidden xl:block">
                        <div className="sticky top-20">
                            <HotelMap
                                pins={pins}
                                highlightedId={hoveredId}
                                className="h-[calc(100svh-7rem)]"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <CompareTray query={query} />
        </>
    );
}
