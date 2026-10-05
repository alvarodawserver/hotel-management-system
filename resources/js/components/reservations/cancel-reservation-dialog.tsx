import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    form: RouteFormDefinition<'post'>;
    /** What the cancellation means for the money, shown before confirming. */
    refundNotice: ReactNode;
    /** The hotel must tell the guest why it cancels. */
    askForReason?: boolean;
};

/**
 * Confirms a cancellation, showing first how much will be refunded.
 */
export default function CancelReservationDialog({
    form,
    refundNotice,
    askForReason = false,
}: Props) {
    const [open, setOpen] = useState(false);
    const { t } = useTranslation();

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline">{t('Cancel reservation')}</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{t('Cancel this reservation?')}</DialogTitle>
                <DialogDescription>
                    {t('The room is released at once. This cannot be undone.')}
                </DialogDescription>

                <div className="rounded-xl bg-secondary p-4 text-sm">
                    {refundNotice}
                </div>

                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            {askForReason && (
                                <div className="grid gap-2">
                                    <Label htmlFor="reason">
                                        {t('Reason for the guest')}
                                    </Label>
                                    <Input
                                        id="reason"
                                        name="reason"
                                        required
                                        maxLength={255}
                                        placeholder={t(
                                            'E.g. the room is closed for repairs',
                                        )}
                                    />
                                </div>
                            )}

                            {Object.values(errors).map((message) => (
                                <InputError key={message} message={message} />
                            ))}

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary" type="button">
                                        {t('Keep reservation')}
                                    </Button>
                                </DialogClose>
                                <Button
                                    variant="destructive"
                                    type="submit"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    {t('Cancel reservation')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
