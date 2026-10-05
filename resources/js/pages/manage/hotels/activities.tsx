import { Head } from '@inertiajs/react';
import { Clock, Pencil, Plus, Trash2, Users } from 'lucide-react';
import ActivityController from '@/actions/App/Http/Controllers/Manage/ActivityController';
import ConfirmActionDialog from '@/components/confirm-action-dialog';
import FormDialog from '@/components/form-dialog';
import InputError from '@/components/input-error';
import HotelManageHeader from '@/components/manage/hotel-manage-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useHotelBreadcrumbs } from '@/hooks/use-hotel-breadcrumbs';
import { useTranslation } from '@/hooks/use-translation';
import { centsToInput, formatPrice } from '@/lib/utils';
import { index } from '@/routes/manage/hotels/activities';
import type { HotelSummary, ManagedActivity } from '@/types';

type Props = {
    hotel: HotelSummary;
    activities: ManagedActivity[];
};

function ActivityFields({
    activity,
    errors,
}: {
    activity?: ManagedActivity;
    errors: Partial<Record<string, string>>;
}) {
    const { t } = useTranslation();

    return (
        <div className="space-y-4">
            <div className="grid gap-2">
                <Label htmlFor="name">{t('Name')}</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    defaultValue={activity?.name}
                    placeholder={t('Beach yoga')}
                />
                <InputError message={errors.name} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="description">{t('Description')}</Label>
                <textarea
                    id="description"
                    name="description"
                    required
                    maxLength={2000}
                    defaultValue={activity?.description}
                    className="flex min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                />
                <InputError message={errors.description} />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="price">{t('Price (€, 0 if free)')}</Label>
                    <Input
                        id="price"
                        name="price"
                        type="number"
                        min={0}
                        step="0.01"
                        required
                        defaultValue={
                            activity ? centsToInput(activity.price) : '0'
                        }
                    />
                    <InputError message={errors.price} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="capacity">{t('Capacity (optional)')}</Label>
                    <Input
                        id="capacity"
                        name="capacity"
                        type="number"
                        min={1}
                        defaultValue={activity?.capacity ?? ''}
                    />
                    <InputError message={errors.capacity} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="starts_at">{t('Starts at')}</Label>
                    <Input
                        id="starts_at"
                        name="starts_at"
                        type="time"
                        defaultValue={activity?.starts_at ?? ''}
                    />
                    <InputError message={errors.starts_at} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="ends_at">{t('Ends at')}</Label>
                    <Input
                        id="ends_at"
                        name="ends_at"
                        type="time"
                        defaultValue={activity?.ends_at ?? ''}
                    />
                    <InputError message={errors.ends_at} />
                </div>
            </div>
        </div>
    );
}

export default function HotelActivities({ hotel, activities }: Props) {
    const { t, locale } = useTranslation();
    useHotelBreadcrumbs(hotel, { title: 'Activities', href: index(hotel.id) });

    return (
        <>
            <Head title={`${t('Activities')} · ${hotel.name}`} />

            <div className="max-w-5xl space-y-6 p-4">
                <HotelManageHeader hotel={hotel} />

                <p className="text-sm text-muted-foreground">
                    {t(
                        'Activities are shown on the hotel page for information only; guests do not book them through the platform.',
                    )}
                </p>

                <FormDialog
                    trigger={
                        <Button>
                            <Plus />
                            {t('New activity')}
                        </Button>
                    }
                    title={t('New activity')}
                    submitLabel={t('Create activity')}
                    form={ActivityController.store.form(hotel.id)}
                >
                    {(errors) => <ActivityFields errors={errors} />}
                </FormDialog>

                {activities.length === 0 ? (
                    <p className="rounded-xl border p-8 text-center text-sm text-muted-foreground">
                        {t(
                            'No activities yet. Add yoga classes, excursions, tastings… anything that makes the stay special.',
                        )}
                    </p>
                ) : (
                    <ul className="grid gap-4 sm:grid-cols-2">
                        {activities.map((activity) => (
                            <li
                                key={activity.id}
                                className="space-y-3 rounded-xl border p-4"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <h2 className="font-medium">
                                        {activity.name}
                                    </h2>
                                    <span className="text-sm font-medium">
                                        {activity.price === 0
                                            ? t('Free')
                                            : formatPrice(
                                                  activity.price,
                                                  locale,
                                              )}
                                    </span>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {activity.description}
                                </p>
                                <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                    {activity.starts_at && (
                                        <span className="inline-flex items-center gap-1">
                                            <Clock className="size-4" />
                                            {activity.starts_at}
                                            {activity.ends_at &&
                                                ` – ${activity.ends_at}`}
                                        </span>
                                    )}
                                    {activity.capacity && (
                                        <span className="inline-flex items-center gap-1">
                                            <Users className="size-4" />
                                            {t('Up to :count people', {
                                                count: activity.capacity,
                                            })}
                                        </span>
                                    )}
                                </div>
                                <div className="flex gap-2">
                                    <FormDialog
                                        trigger={
                                            <Button variant="outline" size="sm">
                                                <Pencil />
                                                {t('Edit')}
                                            </Button>
                                        }
                                        title={t('Edit activity')}
                                        submitLabel={t('Save changes')}
                                        form={ActivityController.update.form([
                                            hotel.id,
                                            activity.id,
                                        ])}
                                    >
                                        {(errors) => (
                                            <ActivityFields
                                                activity={activity}
                                                errors={errors}
                                            />
                                        )}
                                    </FormDialog>
                                    <ConfirmActionDialog
                                        trigger={
                                            <Button variant="ghost" size="sm">
                                                <Trash2 />
                                                {t('Delete')}
                                            </Button>
                                        }
                                        title={t('Delete :name?', {
                                            name: activity.name,
                                        })}
                                        description={t(
                                            'The activity will be removed from the hotel page.',
                                        )}
                                        confirmLabel={t('Delete')}
                                        form={ActivityController.destroy.form([
                                            hotel.id,
                                            activity.id,
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
