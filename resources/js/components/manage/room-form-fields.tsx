import { useState } from 'react';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { centsToInput } from '@/lib/utils';
import type { ManagedRoom, RoomTypeOption } from '@/types';

type Props = {
    room?: ManagedRoom;
    roomTypes: RoomTypeOption[];
    errors: Partial<Record<string, string>>;
    /** Bulk creation has no name/description/status but a quantity and first number. */
    bulk?: boolean;
};

/**
 * Room fields shared by the create, bulk-create and edit forms. Choosing a
 * room type pre-fills its default capacity, which can then be changed.
 */
export default function RoomFormFields({
    room,
    roomTypes,
    errors,
    bulk = false,
}: Props) {
    const { t } = useTranslation();
    const [capacity, setCapacity] = useState<string>(
        room ? String(room.capacity) : '',
    );

    const onRoomTypeChange = (roomTypeId: string) => {
        const roomType = roomTypes.find(
            (type) => String(type.id) === roomTypeId,
        );

        if (roomType) {
            setCapacity(String(roomType.default_capacity));
        }
    };

    return (
        <div className="space-y-4">
            {bulk ? (
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="quantity">{t('Number of rooms')}</Label>
                        <Input
                            id="quantity"
                            name="quantity"
                            type="number"
                            min={1}
                            max={50}
                            required
                        />
                        <InputError message={errors.quantity} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="first_number">
                            {t('First room number')}
                        </Label>
                        <Input
                            id="first_number"
                            name="first_number"
                            type="number"
                            min={1}
                            placeholder="101"
                            required
                        />
                        <InputError message={errors.first_number} />
                    </div>
                </div>
            ) : (
                <div className="grid gap-2">
                    <Label htmlFor="name">{t('Room number or name')}</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        maxLength={50}
                        defaultValue={room?.name}
                        placeholder="101"
                    />
                    <InputError message={errors.name} />
                </div>
            )}

            <div className="grid gap-2">
                <Label htmlFor="room_type_id">{t('Room type')}</Label>
                <NativeSelect
                    id="room_type_id"
                    name="room_type_id"
                    required
                    defaultValue={room ? String(room.room_type_id) : ''}
                    placeholder={t('Choose a room type')}
                    options={roomTypes.map((type) => ({
                        value: String(type.id),
                        label: type.name,
                    }))}
                    onChange={(event) => onRoomTypeChange(event.target.value)}
                />
                <InputError message={errors.room_type_id} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="capacity">{t('Capacity (guests)')}</Label>
                    <Input
                        id="capacity"
                        name="capacity"
                        type="number"
                        min={1}
                        max={20}
                        required
                        value={capacity}
                        onChange={(event) => setCapacity(event.target.value)}
                    />
                    <InputError message={errors.capacity} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="price">{t('Price per night (€)')}</Label>
                    <Input
                        id="price"
                        name="price"
                        type="number"
                        min={1}
                        step="0.01"
                        required
                        defaultValue={
                            room ? centsToInput(room.price_per_night) : ''
                        }
                    />
                    <InputError message={errors.price} />
                </div>
            </div>

            {!bulk && (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="description">
                            {t('Description (optional)')}
                        </Label>
                        <textarea
                            id="description"
                            name="description"
                            maxLength={2000}
                            defaultValue={room?.description ?? ''}
                            className="flex min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                        />
                        <InputError message={errors.description} />
                    </div>

                    <Label className="flex items-center gap-2 font-normal">
                        <Checkbox
                            name="is_active"
                            value="1"
                            defaultChecked={room?.is_active ?? true}
                        />
                        {t('Active (can be booked)')}
                    </Label>
                </>
            )}
        </div>
    );
}
