import { Form, Head, Link, usePage } from '@inertiajs/react';
import ReservationController from '@/actions/App/Http/Controllers/Manage/ReservationController';
import Heading from '@/components/heading';
import NativeSelect from '@/components/native-select';
import type { SelectOption } from '@/components/native-select';
import Pagination from '@/components/pagination';
import ReservationStatusBadge from '@/components/reservations/reservation-status-badge';
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
import { formatDay } from '@/lib/dates';
import { formatPrice } from '@/lib/utils';
import { index, show } from '@/routes/manage/reservations';
import type { Paginated, Reservation } from '@/types';

type Props = {
    reservations: Paginated<Reservation>;
    hotels: SelectOption[];
    statuses: SelectOption[];
    filters: {
        search: string;
        hotel: string;
        status: string;
        from: string;
        to: string;
        refund_failed: boolean;
    };
};

const shortDate: Intl.DateTimeFormatOptions = {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
};

export default function ManageReservationsIndex({
    reservations,
    hotels,
    statuses,
    filters,
}: Props) {
    const { auth } = usePage().props;
    const { t, locale } = useTranslation();
    const isAdmin = auth.user.role === 'admin';

    return (
        <>
            <Head title={t('Reservations')} />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title={t('Reservations')}
                    description={
                        isAdmin
                            ? t('Bookings of every hotel on the platform')
                            : t('Bookings of your hotels')
                    }
                />

                <Form
                    {...ReservationController.index.form()}
                    transform={(data) =>
                        Object.fromEntries(
                            Object.entries(data).filter(
                                ([, value]) => value !== '',
                            ),
                        )
                    }
                    options={{ preserveScroll: true }}
                    className="grid gap-4 md:grid-cols-2 xl:grid-cols-[1fr_12rem_11rem_10rem_10rem_auto] xl:items-end"
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
                                    placeholder={t('Code, guest or email')}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="hotel">{t('Hotel')}</Label>
                                <NativeSelect
                                    id="hotel"
                                    name="hotel"
                                    defaultValue={filters.hotel}
                                    placeholder={t('All hotels')}
                                    options={hotels}
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
                            <div className="grid gap-2">
                                <Label htmlFor="from">
                                    {t('Staying from')}
                                </Label>
                                <Input
                                    id="from"
                                    name="from"
                                    type="date"
                                    defaultValue={filters.from}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="to">{t('Staying until')}</Label>
                                <Input
                                    id="to"
                                    name="to"
                                    type="date"
                                    defaultValue={filters.to}
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
                            {isAdmin && (
                                <label className="flex items-center gap-2 text-sm md:col-span-2 xl:col-span-6">
                                    <input
                                        type="checkbox"
                                        name="refund_failed"
                                        value="1"
                                        defaultChecked={filters.refund_failed}
                                        className="size-4 accent-primary"
                                    />
                                    {t('Only failed refunds')}
                                </label>
                            )}
                        </>
                    )}
                </Form>

                <div className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-4">
                                    {t('Code')}
                                </TableHead>
                                <TableHead>{t('Guest')}</TableHead>
                                <TableHead>{t('Hotel')}</TableHead>
                                <TableHead>{t('Stay')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="pr-4 text-right">
                                    {t('Total')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {reservations.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-10 text-center text-muted-foreground"
                                    >
                                        {t(
                                            'No reservations match these filters.',
                                        )}
                                    </TableCell>
                                </TableRow>
                            )}
                            {reservations.data.map((reservation) => (
                                <TableRow key={reservation.code}>
                                    <TableCell className="pl-4">
                                        <Link
                                            href={show(reservation.code)}
                                            className="font-mono font-medium text-primary hover:underline"
                                        >
                                            {reservation.code}
                                        </Link>
                                    </TableCell>
                                    <TableCell>
                                        <span className="block font-medium">
                                            {reservation.guest_name}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {reservation.customer?.email}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <span className="block">
                                            {reservation.hotel.name}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {reservation.room.room_type} ·{' '}
                                            {t('No. :name', {
                                                name: reservation.room.name,
                                            })}
                                        </span>
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        {formatDay(
                                            reservation.check_in,
                                            locale,
                                            shortDate,
                                        )}{' '}
                                        →{' '}
                                        {formatDay(
                                            reservation.check_out,
                                            locale,
                                            shortDate,
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <ReservationStatusBadge
                                            reservation={reservation}
                                        />
                                        {reservation.refund_status ===
                                            'failed' && (
                                            <span className="mt-1 block text-xs font-medium text-destructive">
                                                {
                                                    reservation.refund_status_label
                                                }
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="pr-4 text-right font-medium">
                                        {formatPrice(
                                            reservation.total_price,
                                            locale,
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <Pagination paginator={reservations} />
            </div>
        </>
    );
}

ManageReservationsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Reservations',
            href: index(),
        },
    ],
};
