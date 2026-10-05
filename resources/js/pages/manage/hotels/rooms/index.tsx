import { Head, Link } from '@inertiajs/react';
import { Images, Layers, Plus, Trash2 } from 'lucide-react';
import BulkRoomController from '@/actions/App/Http/Controllers/Manage/BulkRoomController';
import RoomController from '@/actions/App/Http/Controllers/Manage/RoomController';
import ConfirmActionDialog from '@/components/confirm-action-dialog';
import FormDialog from '@/components/form-dialog';
import HotelManageHeader from '@/components/manage/hotel-manage-header';
import RoomFormFields from '@/components/manage/room-form-fields';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useHotelBreadcrumbs } from '@/hooks/use-hotel-breadcrumbs';
import { useTranslation } from '@/hooks/use-translation';
import { formatPrice } from '@/lib/utils';
import { edit, index } from '@/routes/manage/hotels/rooms';
import type { HotelSummary, ManagedRoom, RoomTypeOption } from '@/types';

type RoomRow = ManagedRoom & {
    room_type_name: string;
    images_count: number;
};

type Props = {
    hotel: HotelSummary;
    rooms: RoomRow[];
    roomTypes: RoomTypeOption[];
};

export default function HotelRooms({ hotel, rooms, roomTypes }: Props) {
    const { t, locale } = useTranslation();
    useHotelBreadcrumbs(hotel, { title: 'Rooms', href: index(hotel.id) });

    return (
        <>
            <Head title={`${t('Rooms')} · ${hotel.name}`} />

            <div className="max-w-5xl space-y-6 p-4">
                <HotelManageHeader hotel={hotel} />

                <div className="flex flex-wrap gap-2">
                    <FormDialog
                        trigger={
                            <Button>
                                <Plus />
                                {t('New room')}
                            </Button>
                        }
                        title={t('New room')}
                        submitLabel={t('Create room')}
                        form={RoomController.store.form(hotel.id)}
                    >
                        {(errors) => (
                            <RoomFormFields
                                roomTypes={roomTypes}
                                errors={errors}
                            />
                        )}
                    </FormDialog>

                    <FormDialog
                        trigger={
                            <Button variant="outline">
                                <Layers />
                                {t('Add several rooms')}
                            </Button>
                        }
                        title={t('Add several rooms')}
                        description={t(
                            'Creates consecutively numbered rooms with the same type, capacity and price. For example, 10 rooms starting at 101 creates rooms 101 to 110.',
                        )}
                        submitLabel={t('Create rooms')}
                        form={BulkRoomController.store.form(hotel.id)}
                    >
                        {(errors) => (
                            <RoomFormFields
                                roomTypes={roomTypes}
                                errors={errors}
                                bulk
                            />
                        )}
                    </FormDialog>
                </div>

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-4">
                                    {t('Room')}
                                </TableHead>
                                <TableHead>{t('Type')}</TableHead>
                                <TableHead>{t('Guests')}</TableHead>
                                <TableHead>{t('Price per night')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Photos')}</TableHead>
                                <TableHead className="pr-4 text-right">
                                    <span className="sr-only">
                                        {t('Actions')}
                                    </span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rooms.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-10 text-center text-muted-foreground"
                                    >
                                        {t(
                                            'No rooms yet. Add them one by one or several at once.',
                                        )}
                                    </TableCell>
                                </TableRow>
                            )}
                            {rooms.map((room) => (
                                <TableRow key={room.id}>
                                    <TableCell className="pl-4 font-medium">
                                        {room.name}
                                    </TableCell>
                                    <TableCell>{room.room_type_name}</TableCell>
                                    <TableCell>{room.capacity}</TableCell>
                                    <TableCell>
                                        {formatPrice(
                                            room.price_per_night,
                                            locale,
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {room.is_active ? (
                                            <Badge variant="secondary">
                                                {t('Active')}
                                            </Badge>
                                        ) : (
                                            <Badge variant="outline">
                                                {t('Inactive')}
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <span className="inline-flex items-center gap-1 text-muted-foreground">
                                            <Images className="size-4" />
                                            {room.images_count}
                                        </span>
                                    </TableCell>
                                    <TableCell className="pr-4">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={edit([
                                                        hotel.id,
                                                        room.id,
                                                    ])}
                                                >
                                                    {t('Edit')}
                                                </Link>
                                            </Button>
                                            <ConfirmActionDialog
                                                trigger={
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={t(
                                                            'Delete room :name',
                                                            { name: room.name },
                                                        )}
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                }
                                                title={t('Delete room :name?', {
                                                    name: room.name,
                                                })}
                                                description={t(
                                                    'The room will no longer be bookable. Past bookings keep their data.',
                                                )}
                                                confirmLabel={t('Delete')}
                                                form={RoomController.destroy.form(
                                                    [hotel.id, room.id],
                                                )}
                                            />
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}
