import { Form } from '@inertiajs/react';
import { Pencil, ShieldAlert, Trash2 } from 'lucide-react';
import ConfirmActionDialog from '@/components/confirm-action-dialog';
import FormDialog from '@/components/form-dialog';
import InputError from '@/components/input-error';
import RatingInput from '@/components/reviews/rating-input';
import ReviewCard from '@/components/reviews/review-card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/hooks/use-translation';
import { destroy, store, update } from '@/routes/reservations/review';
import type { Reservation, Review } from '@/types';

const MIN_COMMENT = 10;
const MAX_COMMENT = 2000;

function ReviewFields({
    review,
    errors,
}: {
    review?: Review;
    errors: Partial<Record<string, string>>;
}) {
    const { t } = useTranslation();

    return (
        <div className="space-y-4">
            <div>
                <RatingInput defaultValue={review?.rating} />
                <InputError message={errors.rating} />
            </div>
            <div className="grid gap-1.5">
                <Label htmlFor="comment">{t('Your review')}</Label>
                <Textarea
                    id="comment"
                    name="comment"
                    required
                    minLength={MIN_COMMENT}
                    maxLength={MAX_COMMENT}
                    rows={5}
                    defaultValue={review?.comment}
                    placeholder={t(
                        'What did you like? What could be better? Your review helps other guests.',
                    )}
                />
                <InputError message={errors.comment} />
            </div>
        </div>
    );
}

/**
 * The guest's review of a past stay on the reservation page: a form while
 * the stay can be reviewed, the review with edit/delete once written, or the
 * moderation notice if an admin removed it.
 */
export default function GuestReview({
    reservation,
}: {
    reservation: Reservation;
}) {
    const { t } = useTranslation();
    const { review } = reservation;

    if (review?.removed_at) {
        return (
            <Alert>
                <ShieldAlert />
                <AlertTitle>{t('Your review was removed')}</AlertTitle>
                <AlertDescription>
                    <p>
                        {t(
                            'Our team removed it because it did not follow our guidelines: reviews must describe the stay, without insults or inappropriate content.',
                        )}
                    </p>
                    {review.removal_reason && (
                        <p>
                            <span className="font-medium">{t('Reason')}:</span>{' '}
                            {review.removal_reason}
                        </p>
                    )}
                </AlertDescription>
            </Alert>
        );
    }

    if (review) {
        return (
            <section className="space-y-3">
                <h2 className="font-display text-xl font-bold">
                    {t('Your review')}
                </h2>
                <ReviewCard
                    review={review}
                    actions={
                        <>
                            <FormDialog
                                trigger={
                                    <Button variant="outline" size="sm">
                                        <Pencil />
                                        {t('Edit')}
                                    </Button>
                                }
                                title={t('Edit your review')}
                                submitLabel={t('Save changes')}
                                form={update.form(reservation.code)}
                            >
                                {(errors) => (
                                    <ReviewFields
                                        review={review}
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
                                title={t('Delete your review?')}
                                description={t(
                                    'It will disappear from the hotel page. You can write a new one afterwards.',
                                )}
                                confirmLabel={t('Delete review')}
                                form={destroy.form(reservation.code)}
                            />
                        </>
                    }
                />
            </section>
        );
    }

    if (!reservation.can_be_reviewed) {
        return null;
    }

    return (
        <section className="space-y-4 rounded-2xl border border-primary/30 bg-accent/40 p-5">
            <div className="space-y-1">
                <h2 className="font-display text-xl font-bold">
                    {t('How was your stay?')}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {t(
                        'Rate :hotel and tell other guests about your experience.',
                        { hotel: reservation.hotel.name },
                    )}
                </p>
            </div>
            <Form
                {...store.form(reservation.code)}
                options={{ preserveScroll: true }}
                className="space-y-4"
            >
                {({ processing, errors }) => (
                    <>
                        <ReviewFields errors={errors} />
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {t('Publish review')}
                        </Button>
                    </>
                )}
            </Form>
        </section>
    );
}
