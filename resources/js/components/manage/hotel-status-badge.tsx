import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/hooks/use-translation';
import type { HotelSummary } from '@/types';

export default function HotelStatusBadge({
    hotel,
}: {
    hotel: Pick<HotelSummary, 'is_visible' | 'is_blocked'>;
}) {
    const { t } = useTranslation();

    if (hotel.is_blocked) {
        return <Badge variant="destructive">{t('Blocked')}</Badge>;
    }

    return hotel.is_visible ? (
        <Badge className="bg-green-600 text-white">{t('Visible')}</Badge>
    ) : (
        <Badge variant="secondary">{t('Hidden')}</Badge>
    );
}
