import { Form, Head, Link } from '@inertiajs/react';
import HotelController from '@/actions/App/Http/Controllers/Manage/HotelController';
import Heading from '@/components/heading';
import HotelFormFields from '@/components/manage/hotel-form-fields';
import type { SelectOption } from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import { create, index } from '@/routes/manage/hotels';
import type { AmenityOption, CancellationTier, CategoryOption } from '@/types';

type Props = {
    provinces: SelectOption[];
    amenities: AmenityOption[];
    categories: CategoryOption[];
    defaultCancellationPolicy: CancellationTier[];
};

export default function CreateHotel({
    provinces,
    amenities,
    categories,
    defaultCancellationPolicy,
}: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('New hotel')} />

            <div className="max-w-3xl space-y-6 p-4">
                <Heading
                    title={t('New hotel')}
                    description={t(
                        'The hotel stays hidden until you add rooms and photos and publish it.',
                    )}
                />

                <Form {...HotelController.store.form()} className="space-y-8">
                    {({ processing, errors }) => (
                        <>
                            <HotelFormFields
                                values={{
                                    name: '',
                                    description: '',
                                    province: '',
                                    municipality: '',
                                    address: '',
                                    latitude: null,
                                    longitude: null,
                                    stars: null,
                                    cancellation_policy:
                                        defaultCancellationPolicy,
                                    amenity_ids: [],
                                    category_ids: [],
                                }}
                                errors={errors}
                                provinces={provinces}
                                amenities={amenities}
                                categories={categories}
                            />

                            <div className="flex items-center gap-2">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {t('Create hotel')}
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href={index()}>{t('Cancel')}</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreateHotel.layout = {
    breadcrumbs: [
        { title: 'My hotels', href: index() },
        { title: 'New hotel', href: create() },
    ],
};
