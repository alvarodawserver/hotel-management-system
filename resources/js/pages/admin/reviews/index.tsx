import { Form, Head, Link } from '@inertiajs/react';
import { Check, Flag, ShieldAlert, Trash2 } from 'lucide-react';
import ReviewController from '@/actions/App/Http/Controllers/Admin/ReviewController';
import FormDialog from '@/components/form-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import Pagination from '@/components/pagination';
import ReviewCard from '@/components/reviews/review-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/dates';
import { cn } from '@/lib/utils';
import { show as showHotel } from '@/routes/hotels';
import { index } from '@/routes/admin/reviews';
import type { ModeratedReview, Paginated } from '@/types';

type Tab = 'reported' | 'all' | 'removed';

type Props = {
    reviews: Paginated<ModeratedReview>;
    tab: Tab;
    reportedCount: number;
    filters: { search: string };
};

const EMPTY: Record<Tab, string> = {
    reported: 'No reported reviews. Owners report offensive reviews here.',
    all: 'No reviews yet.',
    removed: 'No reviews have been removed.',
};

function ModerationActions({ review }: { review: ModeratedReview }) {
    const { t } = useTranslation();

    return (
        <>
            <FormDialog
                trigger={
                    <Button variant="destructive" size="sm">
                        <Trash2 />
                        {t('Remove')}
                    </Button>
                }
                title={t('Remove this review?')}
                description={t(
                    'Remove reviews with insults or inappropriate content, not fair criticism. It disappears from the hotel page and its average, and its author gets an email with the reason.',
                )}
                submitLabel={t('Remove review')}
                form={ReviewController.destroy.form(review.id)}
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
                            defaultValue={review.report_reason ?? ''}
                            placeholder={t('Offensive language')}
                        />
                        <InputError message={errors.reason} />
                    </div>
                )}
            </FormDialog>

            {review.is_reported && (
                <Form
                    {...ReviewController.dismissReport.form(review.id)}
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <Button
                            type="submit"
                            variant="outline"
                            size="sm"
                            disabled={processing}
                        >
                            <Check />
                            {t('Keep it (dismiss report)')}
                        </Button>
                    )}
                </Form>
            )}
        </>
    );
}

function ModerationDetails({ review }: { review: ModeratedReview }) {
    const { t, locale } = useTranslation();

    return (
        <div className="space-y-2 text-sm">
            <p className="text-xs text-muted-foreground">
                {review.hotel && (
                    <>
                        <Link
                            href={showHotel(review.hotel.slug)}
                            className="font-medium text-foreground hover:underline"
                        >
                            {review.hotel.name}
                        </Link>
                        {' · '}
                    </>
                )}
                {review.author_name} ({review.author_email}) ·{' '}
                <span className="font-mono">{review.reservation_code}</span>
            </p>
            {review.is_reported && (
                <p className="flex flex-wrap items-center gap-2 rounded-lg bg-sun/15 p-2">
                    <Badge variant="secondary">
                        <Flag />
                        {t('Reported by the owner')}
                    </Badge>
                    {review.report_reason}
                </p>
            )}
            {review.removed_at && (
                <p className="flex flex-wrap items-center gap-2 rounded-lg bg-destructive/10 p-2">
                    <Badge variant="destructive">
                        <ShieldAlert />
                        {t('Removed on :date', {
                            date: formatDateTime(review.removed_at, locale),
                        })}
                    </Badge>
                    {review.removal_reason}
                </p>
            )}
        </div>
    );
}

export default function AdminReviews({
    reviews,
    tab,
    reportedCount,
    filters,
}: Props) {
    const { t } = useTranslation();

    const tabs: { value: Tab; label: string }[] = [
        {
            value: 'reported',
            label: `${t('Reported')}${reportedCount > 0 ? ` (${reportedCount})` : ''}`,
        },
        { value: 'all', label: t('All') },
        { value: 'removed', label: t('Removed') },
    ];

    return (
        <>
            <Head title={t('Reviews')} />

            <div className="flex max-w-5xl flex-col gap-6 p-4">
                <Heading
                    title={t('Reviews')}
                    description={t(
                        'Check the reviews owners report and remove those with insults or inappropriate content. Negative but respectful reviews stay.',
                    )}
                />

                <nav
                    aria-label={t('Review lists')}
                    className="flex gap-6 border-b"
                >
                    {tabs.map((item) => (
                        <Link
                            key={item.value}
                            href={index({
                                query: {
                                    tab: item.value,
                                    ...(filters.search
                                        ? { search: filters.search }
                                        : {}),
                                },
                            })}
                            preserveScroll
                            className={cn(
                                '-mb-px border-b-2 pb-3 text-sm font-medium',
                                item.value === tab
                                    ? 'border-primary text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>

                <Form
                    {...index.form()}
                    transform={(data) => ({
                        ...Object.fromEntries(
                            Object.entries(data).filter(
                                ([, value]) => value !== '',
                            ),
                        ),
                        tab,
                    })}
                    options={{ preserveScroll: true }}
                    className="flex flex-col gap-2 sm:flex-row sm:items-end"
                >
                    {({ processing }) => (
                        <>
                            <div className="grid flex-1 gap-2">
                                <Label htmlFor="search">{t('Search')}</Label>
                                <Input
                                    id="search"
                                    name="search"
                                    type="search"
                                    defaultValue={filters.search}
                                    placeholder={t(
                                        'Hotel, guest or words in the review',
                                    )}
                                />
                            </div>
                            <Button type="submit" disabled={processing}>
                                {t('Filter')}
                            </Button>
                        </>
                    )}
                </Form>

                {reviews.data.length === 0 ? (
                    <p className="rounded-xl border p-8 text-center text-sm text-muted-foreground">
                        {t(EMPTY[tab])}
                    </p>
                ) : (
                    <ul className="space-y-4">
                        {reviews.data.map((review) => (
                            <li key={review.id}>
                                <ReviewCard
                                    review={review}
                                    meta={<ModerationDetails review={review} />}
                                    actions={
                                        review.removed_at ? undefined : (
                                            <ModerationActions
                                                review={review}
                                            />
                                        )
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination paginator={reviews} />
            </div>
        </>
    );
}

AdminReviews.layout = {
    breadcrumbs: [{ title: 'Reviews', href: index() }],
};
