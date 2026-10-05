import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import DeactivateUserDialog from '@/components/admin/deactivate-user-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import type { SelectOption } from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import { edit, index, reactivate } from '@/routes/admin/users';
import type { UserRole } from '@/types';

type Props = {
    user: {
        id: number;
        name: string;
        email: string;
        role: UserRole;
        is_active: boolean;
    };
    roles: SelectOption[];
    canChangeRole: boolean;
    canDeactivate: boolean;
};

export default function EditUser({
    user,
    roles,
    canChangeRole,
    canDeactivate,
}: Props) {
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            { title: 'Users', href: index() },
            { title: user.name, href: edit(user.id) },
        ],
    });

    return (
        <>
            <Head title={t('Edit :name', { name: user.name })} />

            <div className="max-w-xl space-y-12 p-4">
                <div className="space-y-6">
                    <Heading
                        title={t('Edit :name', { name: user.name })}
                        description={t('Update the name, email and role')}
                    />

                    <Form
                        {...UserController.update.form(user.id)}
                        options={{ preserveScroll: true }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">{t('Name')}</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        defaultValue={user.name}
                                        autoComplete="off"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">
                                        {t('Email address')}
                                    </Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        required
                                        defaultValue={user.email}
                                        autoComplete="off"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="role">{t('Role')}</Label>
                                    {canChangeRole ? (
                                        <NativeSelect
                                            id="role"
                                            name="role"
                                            defaultValue={user.role}
                                            options={roles}
                                        />
                                    ) : (
                                        <>
                                            <input
                                                type="hidden"
                                                name="role"
                                                value={user.role}
                                            />
                                            <NativeSelect
                                                id="role"
                                                disabled
                                                aria-describedby="role-locked"
                                                defaultValue={user.role}
                                                options={roles}
                                            />
                                            <p
                                                id="role-locked"
                                                className="text-sm text-muted-foreground"
                                            >
                                                {t(
                                                    'You cannot change your own role.',
                                                )}
                                            </p>
                                        </>
                                    )}
                                    <InputError message={errors.role} />
                                </div>

                                <div className="flex items-center gap-2">
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        {t('Save')}
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <Link href={index()}>
                                            {t('Cancel')}
                                        </Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                {canDeactivate && (
                    <div className="space-y-4">
                        <Heading
                            variant="small"
                            title={t('Account status')}
                            description={
                                user.is_active
                                    ? t(
                                          'This account is active and can log in.',
                                      )
                                    : t(
                                          'This account is deactivated and cannot log in.',
                                      )
                            }
                        />
                        {user.is_active ? (
                            <DeactivateUserDialog
                                user={user}
                                triggerSize="default"
                            />
                        ) : (
                            <Button variant="secondary" asChild>
                                <Link
                                    href={reactivate(user.id)}
                                    as="button"
                                    preserveScroll
                                >
                                    {t('Reactivate')}
                                </Link>
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}
