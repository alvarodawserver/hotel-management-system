import { Form, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, ImageUp, Star, Trash2 } from 'lucide-react';
import ConfirmActionDialog from '@/components/confirm-action-dialog';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import type { GalleryImage } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    images: GalleryImage[];
    maxImages: number;
    uploadForm: RouteFormDefinition<'post'>;
    reorderUrl: string;
    destroyForm: (imageId: number) => RouteFormDefinition<'post'>;
};

/**
 * Upload, reorder and delete photos. The first photo is the cover; photos
 * are optimised (resized, converted to WebP) on the server.
 */
export default function ImageGalleryManager({
    images,
    maxImages,
    uploadForm,
    reorderUrl,
    destroyForm,
}: Props) {
    const { t } = useTranslation();
    const remaining = maxImages - images.length;

    const saveOrder = (orderedIds: number[]) => {
        router.put(
            reorderUrl,
            { images: orderedIds },
            { preserveScroll: true },
        );
    };

    const move = (index: number, offset: -1 | 1) => {
        const ids = images.map((image) => image.id);
        const target = index + offset;
        [ids[index], ids[target]] = [ids[target], ids[index]];
        saveOrder(ids);
    };

    const makeCover = (index: number) => {
        const ids = images.map((image) => image.id);
        const [cover] = ids.splice(index, 1);
        saveOrder([cover, ...ids]);
    };

    return (
        <div className="space-y-6">
            <Form
                {...uploadForm}
                options={{ preserveScroll: true }}
                resetOnSuccess
                className="space-y-3 rounded-xl border border-dashed p-4"
            >
                {({ processing, progress, errors }) => (
                    <>
                        <Label htmlFor="images">
                            {t('Add photos (:count of :max used)', {
                                count: images.length,
                                max: maxImages,
                            })}
                        </Label>
                        <p className="text-sm text-muted-foreground">
                            {t(
                                'JPG, PNG or WebP up to 10 MB each. Photos are resized and compressed automatically.',
                            )}
                        </p>
                        <div className="flex flex-wrap items-center gap-2">
                            <Input
                                id="images"
                                name="images[]"
                                type="file"
                                multiple
                                accept="image/jpeg,image/png,image/webp"
                                disabled={remaining <= 0}
                                className="max-w-sm"
                            />
                            <Button
                                type="submit"
                                disabled={processing || remaining <= 0}
                            >
                                <ImageUp />
                                {t('Upload')}
                            </Button>
                        </div>
                        {progress && (
                            <progress
                                value={progress.percentage}
                                max={100}
                                className="h-2 w-full max-w-sm"
                            />
                        )}
                        {Object.entries(errors)
                            .filter(([field]) => field.startsWith('images'))
                            .map(([field, message]) => (
                                <InputError key={field} message={message} />
                            ))}
                    </>
                )}
            </Form>

            {images.length === 0 ? (
                <p className="rounded-xl border p-8 text-center text-sm text-muted-foreground">
                    {t(
                        'No photos yet. The first photo you upload becomes the cover.',
                    )}
                </p>
            ) : (
                <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {images.map((image, index) => (
                        <li
                            key={image.id}
                            className="overflow-hidden rounded-xl border"
                        >
                            <div className="relative aspect-[4/3] bg-muted">
                                <img
                                    src={image.url}
                                    alt=""
                                    loading="lazy"
                                    className="size-full object-cover"
                                />
                                {index === 0 && (
                                    <Badge className="absolute top-2 left-2">
                                        <Star />
                                        {t('Cover')}
                                    </Badge>
                                )}
                            </div>
                            <div className="flex items-center gap-1 p-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    disabled={index === 0}
                                    onClick={() => move(index, -1)}
                                    aria-label={t('Move left')}
                                >
                                    <ChevronLeft />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    disabled={index === images.length - 1}
                                    onClick={() => move(index, 1)}
                                    aria-label={t('Move right')}
                                >
                                    <ChevronRight />
                                </Button>
                                {index !== 0 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => makeCover(index)}
                                    >
                                        {t('Make cover')}
                                    </Button>
                                )}
                                <div className="ml-auto">
                                    <ConfirmActionDialog
                                        trigger={
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={t('Delete photo')}
                                            >
                                                <Trash2 />
                                            </Button>
                                        }
                                        title={t('Delete this photo?')}
                                        description={t(
                                            'The photo will be removed permanently.',
                                        )}
                                        confirmLabel={t('Delete')}
                                        form={destroyForm(image.id)}
                                    />
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
