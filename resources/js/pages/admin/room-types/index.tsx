import { Head } from '@inertiajs/react';
import RoomTypeController from '@/actions/App/Http/Controllers/Admin/RoomTypeController';
import CataloguePage from '@/components/admin/catalogue-page';
import TranslatedNameFields from '@/components/admin/translated-name-fields';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { index } from '@/routes/admin/room-types';
import type { CatalogueEntry } from '@/types';

type RoomType = CatalogueEntry & { default_capacity: number };

type Props = {
    roomTypes: RoomType[];
};

export default function RoomTypes({ roomTypes }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Room types')} />

            <CataloguePage
                title={t('Room types')}
                description={t(
                    'Types owners choose from when they create rooms. Add new ones when an owner asks for them.',
                )}
                entries={roomTypes}
                newLabel={t('New room type')}
                usageHeader={t('Rooms')}
                deleteDescription={(roomType) =>
                    roomType.usage_count > 0
                        ? t(':count rooms use it, so it cannot be deleted.', {
                              count: roomType.usage_count,
                          })
                        : t('No room uses it.')
                }
                extraColumn={{
                    header: t('Default guests'),
                    cell: (roomType) => roomType.default_capacity,
                }}
                fields={(roomType, errors) => (
                    <div className="space-y-4">
                        <TranslatedNameFields
                            translations={roomType?.translations}
                            errors={errors}
                        />
                        <div className="grid gap-2">
                            <Label htmlFor="default_capacity">
                                {t('Default guests')}
                            </Label>
                            <Input
                                id="default_capacity"
                                name="default_capacity"
                                type="number"
                                min={1}
                                max={20}
                                required
                                defaultValue={roomType?.default_capacity ?? 2}
                                className="w-32"
                            />
                            <InputError message={errors.default_capacity} />
                        </div>
                    </div>
                )}
                storeForm={RoomTypeController.store.form()}
                updateForm={(roomType) =>
                    RoomTypeController.update.form(roomType.id)
                }
                destroyForm={(roomType) =>
                    RoomTypeController.destroy.form(roomType.id)
                }
            />
        </>
    );
}

RoomTypes.layout = {
    breadcrumbs: [{ title: 'Room types', href: index() }],
};
