import { Head } from '@inertiajs/react';
import AmenityController from '@/actions/App/Http/Controllers/Admin/AmenityController';
import CataloguePage from '@/components/admin/catalogue-page';
import IconPicker from '@/components/admin/icon-picker';
import TranslatedNameFields from '@/components/admin/translated-name-fields';
import { useTranslation } from '@/hooks/use-translation';
import { amenityIcon } from '@/lib/amenity-icons';
import { index } from '@/routes/admin/amenities';
import type { CatalogueEntry } from '@/types';

type Amenity = CatalogueEntry & { icon: string };

type Props = {
    amenities: Amenity[];
    icons: string[];
};

export default function Amenities({ amenities, icons }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Amenities')} />

            <CataloguePage
                title={t('Amenities')}
                description={t(
                    'Services owners can tick for their hotels. Each one is shown with its icon on the hotel page.',
                )}
                entries={amenities}
                newLabel={t('New amenity')}
                usageHeader={t('Hotels')}
                deleteDescription={(amenity) =>
                    amenity.usage_count > 0
                        ? t(
                              ':count hotels use it; it will be removed from them.',
                              { count: amenity.usage_count },
                          )
                        : t('No hotel uses it.')
                }
                extraColumn={{
                    header: t('Icon'),
                    cell: (amenity) => {
                        const Icon = amenityIcon(amenity.icon);

                        return (
                            <Icon
                                className="size-5 text-primary"
                                aria-label={amenity.icon}
                            />
                        );
                    },
                }}
                fields={(amenity, errors) => (
                    <div className="space-y-4">
                        <TranslatedNameFields
                            translations={amenity?.translations}
                            errors={errors}
                        />
                        <IconPicker
                            icons={icons}
                            defaultValue={amenity?.icon}
                            error={errors.icon}
                        />
                    </div>
                )}
                storeForm={AmenityController.store.form()}
                updateForm={(amenity) =>
                    AmenityController.update.form(amenity.id)
                }
                destroyForm={(amenity) =>
                    AmenityController.destroy.form(amenity.id)
                }
            />
        </>
    );
}

Amenities.layout = {
    breadcrumbs: [{ title: 'Amenities', href: index() }],
};
