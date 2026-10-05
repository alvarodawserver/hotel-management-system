import { ChevronLeft, ChevronRight, Images } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

/**
 * Cover photo with up to four thumbnails; any photo opens a full-screen
 * viewer with previous/next controls.
 */
export default function HotelGallery({
    images,
    hotelName,
}: {
    images: string[];
    hotelName: string;
}) {
    const { t } = useTranslation();
    const [openIndex, setOpenIndex] = useState<number | null>(null);

    if (images.length === 0) {
        return (
            <div className="flex aspect-[21/9] items-center justify-center rounded-3xl bg-secondary text-muted-foreground">
                {t('No photos yet')}
            </div>
        );
    }

    const [cover, ...rest] = images;
    const thumbnails = rest.slice(0, 4);
    const show = (index: number) => setOpenIndex(index);
    const step = (offset: number) =>
        setOpenIndex((index) =>
            index === null
                ? null
                : (index + offset + images.length) % images.length,
        );

    return (
        <>
            <div
                className={cn(
                    'grid gap-2 overflow-hidden rounded-3xl',
                    thumbnails.length > 0 && 'md:grid-cols-[2fr_1fr]',
                )}
            >
                <button
                    type="button"
                    onClick={() => show(0)}
                    className="relative aspect-[16/10] overflow-hidden bg-secondary md:aspect-auto md:min-h-[26rem]"
                    aria-label={t('Open photo :number of :total', {
                        number: 1,
                        total: images.length,
                    })}
                >
                    <img
                        src={cover}
                        alt={hotelName}
                        className="absolute inset-0 size-full object-cover"
                    />
                </button>
                {thumbnails.length > 0 && (
                    <div className="hidden grid-cols-2 gap-2 md:grid">
                        {thumbnails.map((image, index) => (
                            <button
                                key={image}
                                type="button"
                                onClick={() => show(index + 1)}
                                className="relative aspect-square overflow-hidden bg-secondary"
                                aria-label={t('Open photo :number of :total', {
                                    number: index + 2,
                                    total: images.length,
                                })}
                            >
                                <img
                                    src={image}
                                    alt=""
                                    loading="lazy"
                                    className="absolute inset-0 size-full object-cover"
                                />
                            </button>
                        ))}
                    </div>
                )}
            </div>

            {images.length > 1 && (
                <Button
                    variant="outline"
                    size="sm"
                    className="mt-3"
                    onClick={() => show(0)}
                >
                    <Images />
                    {t('See all :count photos', { count: images.length })}
                </Button>
            )}

            <Dialog
                open={openIndex !== null}
                onOpenChange={(open) => !open && setOpenIndex(null)}
            >
                <DialogContent className="max-w-5xl border-0 bg-black/95 p-2 text-white sm:max-w-5xl">
                    <DialogTitle className="sr-only">
                        {t('Photos of :name', { name: hotelName })}
                    </DialogTitle>
                    {openIndex !== null && (
                        <div className="relative">
                            <img
                                src={images[openIndex]}
                                alt=""
                                className="max-h-[80svh] w-full rounded-lg object-contain"
                            />
                            {images.length > 1 && (
                                <>
                                    <Button
                                        variant="secondary"
                                        size="icon"
                                        className="absolute top-1/2 left-2 -translate-y-1/2 rounded-full"
                                        onClick={() => step(-1)}
                                        aria-label={t('Previous photo')}
                                    >
                                        <ChevronLeft />
                                    </Button>
                                    <Button
                                        variant="secondary"
                                        size="icon"
                                        className="absolute top-1/2 right-2 -translate-y-1/2 rounded-full"
                                        onClick={() => step(1)}
                                        aria-label={t('Next photo')}
                                    >
                                        <ChevronRight />
                                    </Button>
                                </>
                            )}
                            <p className="mt-2 text-center text-sm text-white/70">
                                {openIndex + 1} / {images.length}
                            </p>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
