import { Form, Head } from '@inertiajs/react';
import { Check, Eye, EyeOff, Trash2, X } from 'lucide-react';
import HotelController from '@/actions/App/Http/Controllers/Manage/HotelController';
import HotelVisibilityController from '@/actions/App/Http/Controllers/Manage/HotelVisibilityController';
import ConfirmActionDialog from '@/components/confirm-action-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import HotelFormFields from '@/components/manage/hotel-form-fields';
import type { HotelFormValues } from '@/components/manage/hotel-form-fields';
import HotelManageHeader from '@/components/manage/hotel-manage-header';
import type { SelectOption } from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useHotelBreadcrumbs } from '@/hooks/use-hotel-breadcrumbs';
import { useTranslation } from '@/hooks/use-translation';
import type { AmenityOption, CategoryOption, HotelSummary } from '@/types';

type Props = {
    hotel: HotelSummary & HotelFormValues;
    provinces: SelectOption[];
    amenities: AmenityOption[];
    categories: CategoryOption[];
    publishing: {
        has_active_rooms: boolean;
        has_images: boolean;
        has_location: boolean;
    };
    canDelete: boolean;
};

function Requirement({ met, label }: { met: boolean; label: string }) {
    return (
        <li className="flex items-center gap-2 text-sm">
            {met ? (
                <Check className="size-4 text-green-600" />
            ) : (
                <X className="size-4 text-destructive" />
            )}
            {label}
        </li>
    );
}

export default function EditHotel({
    hotel,
    provinces,
    amenities,
    categories,
    publishing,
    canDelete,
}: Props) {
    const { t } = useTranslation();
    useHotelBreadcrumbs(hotel);

    return (
        <>
            <Head title={hotel.name} />

            <div className="max-w-3xl space-y-10 p-4">
                <HotelManageHeader hotel={hotel} />

                <section className="space-y-4 rounded-xl border p-4">
                    <Heading
                        variant="small"
                        title={t('Visibility')}
                        description={
                            hotel.is_visible
                                ? t(
                                      'The hotel is visible in the catalogue. Hide it at any time, for example during maintenance; existing bookings are not cancelled.',
                                  )
                                : t(
                                      'The hotel is hidden. To publish it, it needs:',
                                  )
                        }
                    />
                    {!hotel.is_visible && (
                        <ul className="space-y-1">
                            <Requirement
                                met={publishing.has_active_rooms}
                                label={t('At least one active room')}
                            />
                            <Requirement
                                met={publishing.has_images}
                                label={t('At least one photo')}
                            />
                            <Requirement
                                met={publishing.has_location}
                                label={t('Its location on the map')}
                            />
                        </ul>
                    )}
                    <Form
                        {...HotelVisibilityController.update.form(hotel.id)}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing, errors }) => (
                            <div className="space-y-2">
                                <input
                                    type="hidden"
                                    name="is_visible"
                                    value={hotel.is_visible ? '0' : '1'}
                                />
                                <Button
                                    type="submit"
                                    variant={
                                        hotel.is_visible ? 'outline' : 'default'
                                    }
                                    disabled={processing}
                                >
                                    {hotel.is_visible ? <EyeOff /> : <Eye />}
                                    {hotel.is_visible
                                        ? t('Hide hotel')
                                        : t('Publish hotel')}
                                </Button>
                                <InputError message={errors.is_visible} />
                            </div>
                        )}
                    </Form>
                </section>

                <Form
                    {...HotelController.update.form(hotel.id)}
                    options={{ preserveScroll: true }}
                    className="space-y-8"
                >
                    {({ processing, errors }) => (
                        <>
                            <HotelFormFields
                                values={hotel}
                                errors={errors}
                                provinces={provinces}
                                amenities={amenities}
                                categories={categories}
                            />
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                {t('Save changes')}
                            </Button>
                        </>
                    )}
                </Form>

                {canDelete && (
                    <section className="space-y-4 rounded-xl border border-destructive/30 p-4">
                        <Heading
                            variant="small"
                            title={t('Delete hotel')}
                            description={t(
                                'The hotel disappears from your panel and from the catalogue. Past bookings keep their data.',
                            )}
                        />
                        <ConfirmActionDialog
                            trigger={
                                <Button variant="destructive">
                                    <Trash2 />
                                    {t('Delete hotel')}
                                </Button>
                            }
                            title={t('Delete :name?', { name: hotel.name })}
                            description={t(
                                'This cannot be undone from the panel.',
                            )}
                            confirmLabel={t('Delete hotel')}
                            form={HotelController.destroy.form(hotel.id)}
                        />
                    </section>
                )}
            </div>
        </>
    );
}
