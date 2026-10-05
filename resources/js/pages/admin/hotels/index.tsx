import { Form, Head, Link } from '@inertiajs/react';
import { Ban } from 'lucide-react';
import HotelBlockController from '@/actions/App/Http/Controllers/Admin/HotelBlockController';
import HotelController from '@/actions/App/Http/Controllers/Admin/HotelController';
import FormDialog from '@/components/form-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import HotelStatusBadge from '@/components/manage/hotel-status-badge';
import NativeSelect from '@/components/native-select';
import type { SelectOption } from '@/components/native-select';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslation } from '@/hooks/use-translation';
import { index } from '@/routes/admin/hotels';
import { destroy as unblock } from '@/routes/admin/hotels/block';
import { edit } from '@/routes/manage/hotels';
import type { HotelSummary, Paginated } from '@/types';

type AdminHotelRow = HotelSummary & {
    owner_name: string;
    province: string;
    municipality: string;
    rooms_count: number;
};

type Props = {
    hotels: Paginated<AdminHotelRow>;
    filters: { search: string; province: string; status: string };
    provinces: SelectOption[];
};

export default function AdminHotels({ hotels, filters, provinces }: Props) {
    const { t } = useTranslation();

    const statuses: SelectOption[] = [
        { value: 'visible', label: t('Visible') },
        { value: 'hidden', label: t('Hidden') },
        { value: 'blocked', label: t('Blocked') },
    ];

    return (
        <>
            <Head title={t('Hotels')} />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title={t('Hotels')}
                    description={t(
                        'Every hotel on the platform. Edit any of them or block those that break the rules.',
                    )}
                />

                <Form
                    {...HotelController.index.form()}
                    transform={(data) =>
                        Object.fromEntries(
                            Object.entries(data).filter(
                                ([, value]) => value !== '',
                            ),
                        )
                    }
                    options={{ preserveScroll: true }}
                    className="grid gap-4 md:grid-cols-[1fr_12rem_12rem_auto] md:items-end"
                >
                    {({ processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="search">{t('Search')}</Label>
                                <Input
                                    id="search"
                                    name="search"
                                    type="search"
                                    defaultValue={filters.search}
                                    placeholder={t(
                                        'Hotel, municipality or owner',
                                    )}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="province">
                                    {t('Province')}
                                </Label>
                                <NativeSelect
                                    id="province"
                                    name="province"
                                    defaultValue={filters.province}
                                    placeholder={t('All provinces')}
                                    options={provinces}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="status">{t('Status')}</Label>
                                <NativeSelect
                                    id="status"
                                    name="status"
                                    defaultValue={filters.status}
                                    placeholder={t('All statuses')}
                                    options={statuses}
                                />
                            </div>
                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    {t('Filter')}
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href={index()}>{t('Clear')}</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-4">
                                    {t('Hotel')}
                                </TableHead>
                                <TableHead>{t('Owner')}</TableHead>
                                <TableHead>{t('Location')}</TableHead>
                                <TableHead>{t('Rooms')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="pr-4 text-right">
                                    <span className="sr-only">
                                        {t('Actions')}
                                    </span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {hotels.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-10 text-center text-muted-foreground"
                                    >
                                        {t(
                                            'No hotels match these filters. Try clearing them.',
                                        )}
                                    </TableCell>
                                </TableRow>
                            )}
                            {hotels.data.map((hotel) => (
                                <TableRow key={hotel.id}>
                                    <TableCell className="pl-4 font-medium">
                                        {hotel.name}
                                        {hotel.is_blocked && (
                                            <p className="text-xs font-normal whitespace-normal text-destructive">
                                                {hotel.blocked_reason}
                                            </p>
                                        )}
                                    </TableCell>
                                    <TableCell>{hotel.owner_name}</TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {hotel.municipality}, {hotel.province}
                                    </TableCell>
                                    <TableCell>{hotel.rooms_count}</TableCell>
                                    <TableCell>
                                        <HotelStatusBadge hotel={hotel} />
                                    </TableCell>
                                    <TableCell className="pr-4">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={edit(hotel.id)}>
                                                    {t('Edit')}
                                                </Link>
                                            </Button>
                                            {hotel.is_blocked ? (
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={unblock(hotel.id)}
                                                        as="button"
                                                        preserveScroll
                                                    >
                                                        {t('Unblock')}
                                                    </Link>
                                                </Button>
                                            ) : (
                                                <FormDialog
                                                    trigger={
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                        >
                                                            <Ban />
                                                            {t('Block')}
                                                        </Button>
                                                    }
                                                    title={t('Block :name?', {
                                                        name: hotel.name,
                                                    })}
                                                    description={t(
                                                        'The hotel disappears from the catalogue and its owner cannot publish it again until you unblock it. Existing bookings are kept.',
                                                    )}
                                                    submitLabel={t('Block')}
                                                    form={HotelBlockController.store.form(
                                                        hotel.id,
                                                    )}
                                                >
                                                    {(errors) => (
                                                        <div className="grid gap-2">
                                                            <Label htmlFor="reason">
                                                                {t(
                                                                    'Reason (the owner will see it)',
                                                                )}
                                                            </Label>
                                                            <Input
                                                                id="reason"
                                                                name="reason"
                                                                required
                                                                maxLength={255}
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.reason
                                                                }
                                                            />
                                                        </div>
                                                    )}
                                                </FormDialog>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <Pagination paginator={hotels} />
            </div>
        </>
    );
}

AdminHotels.layout = {
    breadcrumbs: [{ title: 'Hotels', href: index() }],
};
