import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { Reservation } from '@/types';

const statusClasses: Record<Reservation['status'], string> = {
    pending: 'bg-sun text-sun-foreground',
    confirmed: 'bg-primary text-primary-foreground',
    cancelled: 'bg-destructive/10 text-destructive',
    expired: 'bg-muted text-muted-foreground',
};

export default function ReservationStatusBadge({
    reservation,
    className,
}: {
    reservation: Pick<Reservation, 'status' | 'status_label'>;
    className?: string;
}) {
    return (
        <Badge className={cn(statusClasses[reservation.status], className)}>
            {reservation.status_label}
        </Badge>
    );
}
