import { useTranslation } from '@/hooks/use-translation';
import { formatPrice } from '@/lib/utils';
import type { StayNight } from '@/types';

type Props = {
    nights: StayNight[];
    subtotal: number;
    discount: number;
    total: number;
    totalLabel?: string;
};

/**
 * The price of a stay night by night, with any offer and the total. The
 * same numbers are charged by Stripe.
 */
export default function PriceBreakdown({
    nights,
    subtotal,
    discount,
    total,
    totalLabel,
}: Props) {
    const { t, locale } = useTranslation();
    const dateFormatter = new Intl.DateTimeFormat(locale, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });

    return (
        <div className="space-y-3 text-sm">
            <ul className="space-y-1.5">
                {nights.map((night) => (
                    <li key={night.date} className="flex justify-between gap-4">
                        <span className="text-muted-foreground">
                            {dateFormatter.format(
                                new Date(`${night.date}T00:00:00`),
                            )}
                        </span>
                        <span>
                            {night.discount_percent > 0 && (
                                <span className="mr-2 text-xs text-muted-foreground line-through">
                                    {formatPrice(night.base, locale)}
                                </span>
                            )}
                            {formatPrice(night.price, locale)}
                        </span>
                    </li>
                ))}
            </ul>
            <div className="space-y-1.5 border-t pt-3">
                {discount > 0 && (
                    <>
                        <div className="flex justify-between gap-4 text-muted-foreground">
                            <span>{t('Subtotal')}</span>
                            <span>{formatPrice(subtotal, locale)}</span>
                        </div>
                        <div className="flex justify-between gap-4 text-sun">
                            <span>{t('Offer discount')}</span>
                            <span>−{formatPrice(discount, locale)}</span>
                        </div>
                    </>
                )}
                <div className="flex justify-between gap-4 text-base font-semibold">
                    <span>{totalLabel ?? t('Total')}</span>
                    <span>{formatPrice(total, locale)}</span>
                </div>
            </div>
        </div>
    );
}
