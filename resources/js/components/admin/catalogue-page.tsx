import { Pencil, Plus, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import ConfirmActionDialog from '@/components/confirm-action-dialog';
import FormDialog from '@/components/form-dialog';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslation } from '@/hooks/use-translation';
import type { CatalogueEntry } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type Props<T extends CatalogueEntry> = {
    title: string;
    description: string;
    entries: T[];
    newLabel: string;
    usageHeader: string;
    deleteDescription: (entry: T) => string;
    /** Optional extra column (e.g. icon, default capacity). */
    extraColumn?: { header: string; cell: (entry: T) => ReactNode };
    /** Form fields for the create (entry undefined) and edit dialogs. */
    fields: (
        entry: T | undefined,
        errors: Partial<Record<string, string>>,
    ) => ReactNode;
    storeForm: RouteFormDefinition<'post'>;
    updateForm: (entry: T) => RouteFormDefinition<'post'>;
    destroyForm: (entry: T) => RouteFormDefinition<'post'>;
};

/**
 * List, create, edit and delete page shared by the admin catalogues
 * (amenities, categories and room types).
 */
export default function CataloguePage<T extends CatalogueEntry>({
    title,
    description,
    entries,
    newLabel,
    usageHeader,
    deleteDescription,
    extraColumn,
    fields,
    storeForm,
    updateForm,
    destroyForm,
}: Props<T>) {
    const { t } = useTranslation();

    return (
        <div className="flex max-w-4xl flex-col gap-6 p-4">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading title={title} description={description} />
                <FormDialog
                    trigger={
                        <Button>
                            <Plus />
                            {newLabel}
                        </Button>
                    }
                    title={newLabel}
                    submitLabel={t('Create')}
                    form={storeForm}
                >
                    {(errors) => fields(undefined, errors)}
                </FormDialog>
            </div>

            <div className="rounded-xl border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {extraColumn && (
                                <TableHead className="pl-4">
                                    {extraColumn.header}
                                </TableHead>
                            )}
                            <TableHead className={extraColumn ? '' : 'pl-4'}>
                                {t('Name')}
                            </TableHead>
                            <TableHead>{usageHeader}</TableHead>
                            <TableHead className="pr-4 text-right">
                                <span className="sr-only">{t('Actions')}</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {entries.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={extraColumn ? 4 : 3}
                                    className="py-10 text-center text-muted-foreground"
                                >
                                    {t('Nothing here yet.')}
                                </TableCell>
                            </TableRow>
                        )}
                        {entries.map((entry) => (
                            <TableRow key={entry.id}>
                                {extraColumn && (
                                    <TableCell className="pl-4">
                                        {extraColumn.cell(entry)}
                                    </TableCell>
                                )}
                                <TableCell
                                    className={
                                        extraColumn
                                            ? 'font-medium'
                                            : 'pl-4 font-medium'
                                    }
                                >
                                    {entry.name}
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {entry.usage_count}
                                </TableCell>
                                <TableCell className="pr-4">
                                    <div className="flex justify-end gap-2">
                                        <FormDialog
                                            trigger={
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <Pencil />
                                                    {t('Edit')}
                                                </Button>
                                            }
                                            title={t('Edit :name', {
                                                name: entry.name,
                                            })}
                                            submitLabel={t('Save changes')}
                                            form={updateForm(entry)}
                                        >
                                            {(errors) => fields(entry, errors)}
                                        </FormDialog>
                                        <ConfirmActionDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={t(
                                                        'Delete :name',
                                                        { name: entry.name },
                                                    )}
                                                >
                                                    <Trash2 />
                                                </Button>
                                            }
                                            title={t('Delete :name?', {
                                                name: entry.name,
                                            })}
                                            description={deleteDescription(
                                                entry,
                                            )}
                                            confirmLabel={t('Delete')}
                                            form={destroyForm(entry)}
                                        />
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
