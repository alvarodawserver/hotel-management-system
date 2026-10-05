import { useTranslation } from '@/hooks/use-translation';
import type { CancellationTier } from '@/types';

/**
 * The hotel's refund tiers as plain sentences, from the most generous to
 * the last one, ending with what happens afterwards.
 */
export default function CancellationPolicyText({
    tiers,
}: {
    tiers: CancellationTier[];
}) {
    const { t } = useTranslation();
    const sorted = [...tiers].sort((a, b) => b.days_before - a.days_before);
    const last = sorted.at(-1);

    return (
        <ul className="space-y-2 text-sm">
            {sorted.map((tier) => (
                <li key={tier.days_before} className="flex gap-2">
                    <span
                        className="mt-1.5 size-2 shrink-0 rounded-full bg-primary"
                        aria-hidden
                    />
                    {tier.refund_percent === 100
                        ? t(
                              'Free cancellation up to :days days before check-in.',
                              { days: tier.days_before },
                          )
                        : t(
                              ':percent % refund if you cancel up to :days days before check-in.',
                              {
                                  percent: tier.refund_percent,
                                  days: tier.days_before,
                              },
                          )}
                </li>
            ))}
            {last && (
                <li className="flex gap-2 text-muted-foreground">
                    <span
                        className="mt-1.5 size-2 shrink-0 rounded-full bg-muted-foreground"
                        aria-hidden
                    />
                    {t(
                        'Cancelling less than :days days before check-in is not refunded.',
                        { days: last.days_before },
                    )}
                </li>
            )}
        </ul>
    );
}
