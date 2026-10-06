import { Head } from '@inertiajs/react';
import { Flag, MessageSquareReply, Pencil, Trash2 } from 'lucide-react';
import ReviewController from '@/actions/App/Http/Controllers/Manage/ReviewController';
import ConfirmActionDialog from '@/components/confirm-action-dialog';
import FormDialog from '@/components/form-dialog';
import InputError from '@/components/input-error';
import HotelManageHeader from '@/components/manage/hotel-manage-header';
import Pagination from '@/components/pagination';
import RatingSummary from '@/components/reviews/rating-summary';
import ReviewCard from '@/components/reviews/review-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useHotelBreadcrumbs } from '@/hooks/use-hotel-breadcrumbs';
import { useTranslation } from '@/hooks/use-translation';
import { index } from '@/routes/manage/hotels/reviews';
import type {
    HotelSummary,
    ModeratedReview,
    Paginated,
    RatingSummary as RatingSummaryData,
} from '@/types';

type Props = {
    hotel: HotelSummary;
    rating: RatingSummaryData;
    reviews: Paginated<ModeratedReview>;
    /** Only owners report; admins remove reviews from the admin panel. */
    canReport: boolean;
};

function ReviewActions({
    hotel,
    review,
    canReport,
}: {
    hotel: HotelSummary;
    review: ModeratedReview;
    canReport: boolean;
}) {
    const { t } = useTranslation();

    return (
        <>
            <FormDialog
                trigger={
                    <Button variant="outline" size="sm">
                        {review.reply ? <Pencil /> : <MessageSquareReply />}
                        {review.reply ? t('Edit reply') : t('Reply')}
                    </Button>
                }
                title={t('Reply to :author', { author: review.author })}
                description={t(
                    'Your reply is public and shown below the review on the hotel page.',
                )}
                submitLabel={t('Publish reply')}
                form={ReviewController.reply.form([hotel.id, review.id])}
            >
                {(errors) => (
                    <div className="grid gap-2">
                        <Label htmlFor={`reply-${review.id}`}>
                            {t('Your reply')}
                        </Label>
                        <Textarea
                            id={`reply-${review.id}`}
                            name="reply"
                            required
                            maxLength={2000}
                            rows={5}
                            defaultValue={review.reply ?? ''}
                        />
                        <InputError message={errors.reply} />
                    </div>
                )}
            </FormDialog>

            {review.reply && (
                <ConfirmActionDialog
                    trigger={
                        <Button variant="ghost" size="sm">
                            <Trash2 />
                            {t('Delete reply')}
                        </Button>
                    }
                    title={t('Delete your reply?')}
                    description={t(
                        'The review stays published without the hotel’s reply.',
                    )}
                    confirmLabel={t('Delete reply')}
                    form={ReviewController.destroyReply.form([
                        hotel.id,
                        review.id,
                    ])}
                />
            )}

            {canReport && !review.is_reported && (
                <FormDialog
                    trigger={
                        <Button variant="ghost" size="sm" className="ml-auto">
                            <Flag />
                            {t('Report')}
                        </Button>
                    }
                    title={t('Report this review')}
                    description={t(
                        'Report reviews with insults or inappropriate content. An administrator will check it and remove it if it breaks the rules; reviews are not removed only for being negative.',
                    )}
                    submitLabel={t('Send report')}
                    form={ReviewController.report.form([hotel.id, review.id])}
                >
                    {(errors) => (
                        <div className="grid gap-2">
                            <Label htmlFor={`reason-${review.id}`}>
                                {t('Reason')}
                            </Label>
                            <Input
                                id={`reason-${review.id}`}
                                name="reason"
                                required
                                maxLength={255}
                                placeholder={t('Insults to the staff')}
                            />
                            <InputError message={errors.reason} />
                        </div>
                    )}
                </FormDialog>
            )}
        </>
    );
}

export default function HotelReviews({
    hotel,
    rating,
    reviews,
    canReport,
}: Props) {
    const { t } = useTranslation();
    useHotelBreadcrumbs(hotel, { title: 'Reviews', href: index(hotel.id) });

    return (
        <>
            <Head title={`${t('Reviews')} · ${hotel.name}`} />

            <div className="max-w-5xl space-y-6 p-4">
                <HotelManageHeader hotel={hotel} />

                {reviews.total === 0 ? (
                    <p className="rounded-xl border p-8 text-center text-sm text-muted-foreground">
                        {t(
                            'No reviews yet. Guests can review the hotel from their check-out day.',
                        )}
                    </p>
                ) : (
                    <>
                        <RatingSummary rating={rating} />

                        <ul className="space-y-4">
                            {reviews.data.map((review) => (
                                <li key={review.id}>
                                    <ReviewCard
                                        review={review}
                                        meta={
                                            <p className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                <span>
                                                    {review.author_name} ·{' '}
                                                    <span className="font-mono">
                                                        {
                                                            review.reservation_code
                                                        }
                                                    </span>
                                                </span>
                                                {review.is_reported && (
                                                    <Badge variant="secondary">
                                                        <Flag />
                                                        {t(
                                                            'Reported, pending review',
                                                        )}
                                                    </Badge>
                                                )}
                                            </p>
                                        }
                                        actions={
                                            <ReviewActions
                                                hotel={hotel}
                                                review={review}
                                                canReport={canReport}
                                            />
                                        }
                                    />
                                </li>
                            ))}
                        </ul>

                        <Pagination paginator={reviews} />
                    </>
                )}
            </div>
        </>
    );
}
