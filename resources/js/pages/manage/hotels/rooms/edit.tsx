import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import RoomController from '@/actions/App/Http/Controllers/Manage/RoomController';
import RoomImageController from '@/actions/App/Http/Controllers/Manage/RoomImageController';
import Heading from '@/components/heading';
import HotelManageHeader from '@/components/manage/hotel-manage-header';
import ImageGalleryManager from '@/components/manage/image-gallery-manager';
import RoomFormFields from '@/components/manage/room-form-fields';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useHotelBreadcrumbs } from '@/hooks/use-hotel-breadcrumbs';
import { useTranslation } from '@/hooks/use-translation';
import { edit, index } from '@/routes/manage/hotels/rooms';
import type {
    GalleryImage,
    HotelSummary,
    ManagedRoom,
    RoomTypeOption,
} from '@/types';

type Props = {
    hotel: HotelSummary;
    room: ManagedRoom;
    images: GalleryImage[];
    maxImages: number;
    roomTypes: RoomTypeOption[];
};

export default function EditRoom({
    hotel,
    room,
    images,
    maxImages,
    roomTypes,
}: Props) {
    const { t } = useTranslation();
    useHotelBreadcrumbs(hotel, {
        title: t('Room :name', { name: room.name }),
        href: edit([hotel.id, room.id]),
    });

    return (
        <>
            <Head
                title={`${t('Room :name', { name: room.name })} · ${hotel.name}`}
            />

            <div className="max-w-5xl space-y-10 p-4">
                <HotelManageHeader hotel={hotel} />

                <Link
                    href={index(hotel.id)}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" />
                    {t('All rooms')}
                </Link>

                <section className="max-w-xl space-y-6">
                    <Heading
                        title={t('Room :name', { name: room.name })}
                        description={t('Update the room details')}
                    />
                    <Form
                        {...RoomController.update.form([hotel.id, room.id])}
                        options={{ preserveScroll: true }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <RoomFormFields
                                    room={room}
                                    roomTypes={roomTypes}
                                    errors={errors}
                                />
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {t('Save changes')}
                                </Button>
                            </>
                        )}
                    </Form>
                </section>

                <section className="space-y-6">
                    <Heading
                        variant="small"
                        title={t('Room photos')}
                        description={t(
                            'Optional. They are shown when guests look at this room.',
                        )}
                    />
                    <ImageGalleryManager
                        images={images}
                        maxImages={maxImages}
                        uploadForm={RoomImageController.store.form([
                            hotel.id,
                            room.id,
                        ])}
                        reorderUrl={RoomImageController.reorder.url([
                            hotel.id,
                            room.id,
                        ])}
                        destroyForm={(imageId) =>
                            RoomImageController.destroy.form([
                                hotel.id,
                                room.id,
                                imageId,
                            ])
                        }
                    />
                </section>
            </div>
        </>
    );
}
