import { Head } from '@inertiajs/react';
import HotelImageController from '@/actions/App/Http/Controllers/Manage/HotelImageController';
import HotelManageHeader from '@/components/manage/hotel-manage-header';
import ImageGalleryManager from '@/components/manage/image-gallery-manager';
import { useHotelBreadcrumbs } from '@/hooks/use-hotel-breadcrumbs';
import { useTranslation } from '@/hooks/use-translation';
import { index as imagesIndex } from '@/routes/manage/hotels/images';
import type { GalleryImage, HotelSummary } from '@/types';

type Props = {
    hotel: HotelSummary;
    images: GalleryImage[];
    maxImages: number;
};

export default function HotelPhotos({ hotel, images, maxImages }: Props) {
    const { t } = useTranslation();
    useHotelBreadcrumbs(hotel, {
        title: 'Photos',
        href: imagesIndex(hotel.id),
    });

    return (
        <>
            <Head title={`${t('Photos')} · ${hotel.name}`} />

            <div className="max-w-5xl space-y-8 p-4">
                <HotelManageHeader hotel={hotel} />

                <ImageGalleryManager
                    images={images}
                    maxImages={maxImages}
                    uploadForm={HotelImageController.store.form(hotel.id)}
                    reorderUrl={HotelImageController.reorder.url(hotel.id)}
                    destroyForm={(imageId) =>
                        HotelImageController.destroy.form([hotel.id, imageId])
                    }
                />
            </div>
        </>
    );
}
