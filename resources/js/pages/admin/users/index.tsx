import { Form, Head, Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import DeactivateUserDialog from '@/components/admin/deactivate-user-dialog';
import Heading from '@/components/heading';
import NativeSelect from '@/components/native-select';
import type { SelectOption } from '@/components/native-select';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
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
import { create, edit, index, reactivate } from '@/routes/admin/users';
import type { Paginated, UserRole } from '@/types';

type AdminUserRow = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    role_label: string;
    is_active: boolean;
    created_at: string | null;
};

type Props = {
    users: Paginated<AdminUserRow>;
    filters: { search: string; role: string; status: string };
    roles: SelectOption[];
};

const roleBadgeVariant: Record<UserRole, 'default' | 'secondary' | 'outline'> =
    {
        admin: 'default',
        owner: 'secondary',
        customer: 'outline',
    };

export default function UsersIndex({ users, filters, roles }: Props) {
    const { auth } = usePage().props;
    const { t, locale } = useTranslation();

    const dateFormatter = new Intl.DateTimeFormat(locale, {
        dateStyle: 'medium',
    });

    const statuses: SelectOption[] = [
        { value: 'active', label: t('Active') },
        { value: 'deactivated', label: t('Deactivated') },
    ];

    return (
        <>
            <Head title={t('Users')} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={t('Users')}
                        description={t(
                            'Manage user accounts, roles and access to the platform',
                        )}
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            {t('Create user')}
                        </Link>
                    </Button>
                </div>

                <Form
                    {...UserController.index.form()}
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
                                    placeholder={t('Name or email')}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="role">{t('Role')}</Label>
                                <NativeSelect
                                    id="role"
                                    name="role"
                                    defaultValue={filters.role}
                                    placeholder={t('All roles')}
                                    options={roles}
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

                <div className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-4">
                                    {t('Name')}
                                </TableHead>
                                <TableHead>{t('Email')}</TableHead>
                                <TableHead>{t('Role')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Registered')}</TableHead>
                                <TableHead className="pr-4 text-right">
                                    <span className="sr-only">
                                        {t('Actions')}
                                    </span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-10 text-center text-muted-foreground"
                                    >
                                        {t(
                                            'No users match these filters. Try clearing them.',
                                        )}
                                    </TableCell>
                                </TableRow>
                            )}
                            {users.data.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell className="pl-4 font-medium">
                                        {user.name}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {user.email}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                roleBadgeVariant[user.role]
                                            }
                                        >
                                            {user.role_label}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {user.is_active ? (
                                            <span className="text-sm text-green-700 dark:text-green-400">
                                                {t('Active')}
                                            </span>
                                        ) : (
                                            <span className="text-sm text-red-600 dark:text-red-400">
                                                {t('Deactivated')}
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {user.created_at &&
                                            dateFormatter.format(
                                                new Date(user.created_at),
                                            )}
                                    </TableCell>
                                    <TableCell className="pr-4">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={edit(user.id)}>
                                                    {t('Edit')}
                                                </Link>
                                            </Button>
                                            {user.id !== auth.user.id &&
                                                (user.is_active ? (
                                                    <DeactivateUserDialog
                                                        user={user}
                                                    />
                                                ) : (
                                                    <Button
                                                        variant="secondary"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={reactivate(
                                                                user.id,
                                                            )}
                                                            as="button"
                                                            preserveScroll
                                                        >
                                                            {t('Reactivate')}
                                                        </Link>
                                                    </Button>
                                                ))}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <Pagination paginator={users} />
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: index(),
        },
    ],
};
