import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import type { CancellationTier } from '@/types';

const MAX_TIERS = 4;

type Props = {
    initialTiers: CancellationTier[];
    errors: Partial<Record<string, string>>;
};

/**
 * Editable list of refund tiers ("up to N days before check-in → X %").
 * Inputs are named cancellation_policy[i][field] so a regular <Form>
 * submits them as an array.
 */
export default function CancellationPolicyEditor({
    initialTiers,
    errors,
}: Props) {
    const { t } = useTranslation();
    const [tiers, setTiers] = useState(() =>
        initialTiers.map((tier, index) => ({ ...tier, key: index })),
    );

    const addTier = () => {
        setTiers((current) => [
            ...current,
            {
                key: Math.max(-1, ...current.map((tier) => tier.key)) + 1,
                days_before: 0,
                refund_percent: 0,
            },
        ]);
    };

    const removeTier = (key: number) => {
        setTiers((current) => current.filter((tier) => tier.key !== key));
    };

    return (
        <fieldset className="space-y-3">
            <legend className="text-sm font-medium">
                {t('Cancellation policy')}
            </legend>
            <p className="text-sm text-muted-foreground">
                {t(
                    'How much is refunded if a guest cancels. Cancellations after the last tier are not refunded.',
                )}
            </p>

            {tiers.map((tier, index) => (
                <div
                    key={tier.key}
                    className="flex flex-wrap items-end gap-3 rounded-lg border p-3"
                >
                    <div className="grid gap-1.5">
                        <Label htmlFor={`tier-${tier.key}-days`}>
                            {t('Up to (days before check-in)')}
                        </Label>
                        <Input
                            id={`tier-${tier.key}-days`}
                            name={`cancellation_policy[${index}][days_before]`}
                            type="number"
                            min={0}
                            max={365}
                            required
                            defaultValue={tier.days_before}
                            className="w-32"
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor={`tier-${tier.key}-percent`}>
                            {t('Refund (%)')}
                        </Label>
                        <Input
                            id={`tier-${tier.key}-percent`}
                            name={`cancellation_policy[${index}][refund_percent]`}
                            type="number"
                            min={1}
                            max={100}
                            required
                            defaultValue={tier.refund_percent || ''}
                            className="w-28"
                        />
                    </div>
                    {tiers.length > 1 && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            onClick={() => removeTier(tier.key)}
                            aria-label={t('Remove tier')}
                        >
                            <Trash2 />
                        </Button>
                    )}
                    <InputError
                        className="w-full"
                        message={
                            errors[
                                `cancellation_policy.${index}.days_before`
                            ] ??
                            errors[
                                `cancellation_policy.${index}.refund_percent`
                            ]
                        }
                    />
                </div>
            ))}

            <InputError message={errors.cancellation_policy} />

            {tiers.length < MAX_TIERS && (
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={addTier}
                >
                    <Plus />
                    {t('Add tier')}
                </Button>
            )}
        </fieldset>
    );
}
