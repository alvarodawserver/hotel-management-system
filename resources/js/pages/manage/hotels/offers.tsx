import { Head } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import OfferController from '@/actions/App/Http/Controllers/Manage/OfferController';
import ConfirmActionDialog from '@/components/confirm-action-dialog';
import FormDialog from '@/components/form-dialog';
import InputError from '@/components/input-error';
import HotelManageHeader from '@/components/manage/hotel-manage-header';
import NativeSelect from '@/components/native-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useHotelBreadcrumbs } from '@/hooks/use-hotel-breadcrumbs';
import { useTranslation } from '@/hooks/use-translation';
import { index } from '@/routes/manage/hotels/offers';
import type { HotelSummary, ManagedOffer, OfferStatus } from '@/types';

type Props = {
    hotel: HotelSummary;
    offers: ManagedOffer[];
    roomTypes: { id: number; name: string }[];
};

function OfferFields({
    offer,
    roomTypes,
    errors,
}: {
    offer?: ManagedOffer;
    roomTypes: Props['roomTypes'];
    errors: Partial<Record<string, string>>;
}) {
    const { t } = useTranslation();

    return (
        <div className="space-y-4">
            <div className="grid gap-2">
                <Label htmlFor="title">{t('Title')}</Label>
                <Input
                    id="title"
                    name="title"
                    required
                    defaultValue={offer?.title}
                    placeholder={t('Spring getaway')}
                />
                <InputError message={errors.title} />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="discount_percent">
                        {t('Discount (%)')}
                    </Label>
                    <Input
                        id="discount_percent"
                        name="discount_percent"
                        type="number"
                        min={1}
                        max={90}
                        required
                        defaultValue={offer?.discount_percent ?? 15}
                    />
                    <InputError message={errors.discount_percent} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="room_type_id">{t('Applies to')}</Label>
                    <NativeSelect
                        id="room_type_id"
                        name="room_type_id"
                        defaultValue={
                            offer?.room_type_id
                                ? String(offer.room_type_id)
                                : ''
                        }
                        placeholder={t('The whole hotel')}
                        options={roomTypes.map((type) => ({
                            value: String(type.id),
                            label: type.name,
                        }))}
                    />
                    <InputError message={errors.room_type_id} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="starts_on">{t('First night')}</Label>
                    <Input
                        id="starts_on"
                        name="starts_on"
                        type="date"
                        required
                        defaultValue={offer?.starts_on}
                    />
                    <InputError message={errors.starts_on} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="ends_on">{t('Last night')}</Label>
                    <Input
                        id="ends_on"
                        name="ends_on"
                        type="date"
                        required
                        defaultValue={offer?.ends_on}
                    />
                    <InputError message={errors.ends_on} />
                </div>
            </div>
            <Label className="flex items-center gap-2 font-normal">
                <Checkbox
                    name="is_active"
                    value="1"
                    defaultChecked={offer?.is_active ?? true}
                />
                {t('Active')}
            </Label>
        </div>
    );
}

export default function HotelOffers({ hotel, offers, roomTypes }: Props) {
    const { t, locale } = useTranslation();
    useHotelBreadcrumbs(hotel, { title: 'Offers', href: index(hotel.id) });

    const dateFormatter = new Intl.DateTimeFormat(locale, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
    const formatDate = (date: string) =>
        dateFormatter.format(new Date(`${date}T00:00:00`));

    const statusLabels: Record<OfferStatus, string> = {
        active: t('Running'),
        upcoming: t('Upcoming'),
        finished: t('Finished'),
        disabled: t('Disabled'),
    };

    return (
        <>
            <Head title={`${t('Offers')} · ${hotel.name}`} />

            <div className="max-w-5xl space-y-6 p-4">
                <HotelManageHeader hotel={hotel} />

                <p className="text-sm text-muted-foreground">
                    {t(
                        'A discount on the nights between two dates, for the whole hotel or one room type. If several offers cover the same night, guests get the biggest one; discounts are never added together.',
                    )}
                </p>

                {roomTypes.length === 0 ? (
                    <p className="rounded-xl border p-6 text-sm text-muted-foreground">
                        {t('Add rooms to the hotel before creating offers.')}
                    </p>
                ) : (
                    <FormDialog
                        trigger={
                            <Button>
                                <Plus />
                                {t('New offer')}
                            </Button>
                        }
                        title={t('New offer')}
                        submitLabel={t('Create offer')}
                        form={OfferController.store.form(hotel.id)}
                    >
                        {(errors) => (
                            <OfferFields
                                roomTypes={roomTypes}
                                errors={errors}
                            />
                        )}
                    </FormDialog>
                )}

                {offers.length === 0 ? (
                    <p className="rounded-xl border p-8 text-center text-sm text-muted-foreground">
                        {t(
                            'No offers yet. Offers are highlighted in the catalogue with their discount.',
                        )}
                    </p>
                ) : (
                    <ul className="space-y-3">
                        {offers.map((offer) => (
                            <li
                                key={offer.id}
                                className="flex flex-wrap items-center gap-4 rounded-xl border p-4"
                            >
                                <span className="rounded-full bg-sun px-3 py-1 text-sm font-semibold text-sun-foreground">
                                    −{offer.discount_percent} %
                                </span>
                                <div className="min-w-48 flex-1">
                                    <p className="font-medium">{offer.title}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {t(':from to :to', {
                                            from: formatDate(offer.starts_on),
                                            to: formatDate(offer.ends_on),
                                        })}
                                        {', '}
                                        {offer.room_type_name ??
                                            t('the whole hotel')}
                                    </p>
                                </div>
                                <Badge
                                    variant={
                                        offer.status === 'active'
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {statusLabels[offer.status]}
                                </Badge>
                                <div className="flex gap-2">
                                    <FormDialog
                                        trigger={
                                            <Button variant="outline" size="sm">
                                                <Pencil />
                                                {t('Edit')}
                                            </Button>
                                        }
                                        title={t('Edit offer')}
                                        submitLabel={t('Save changes')}
                                        form={OfferController.update.form([
                                            hotel.id,
                                            offer.id,
                                        ])}
                                    >
                                        {(errors) => (
                                            <OfferFields
                                                offer={offer}
                                                roomTypes={roomTypes}
                                                errors={errors}
                                            />
                                        )}
                                    </FormDialog>
                                    <ConfirmActionDialog
                                        trigger={
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={t('Delete :name', {
                                                    name: offer.title,
                                                })}
                                            >
                                                <Trash2 />
                                            </Button>
                                        }
                                        title={t('Delete :name?', {
                                            name: offer.title,
                                        })}
                                        description={t(
                                            'Bookings already made keep the price they were made with.',
                                        )}
                                        confirmLabel={t('Delete')}
                                        form={OfferController.destroy.form([
                                            hotel.id,
                                            offer.id,
                                        ])}
                                    />
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
