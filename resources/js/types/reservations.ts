import type { StayNight } from '@/types/catalog';
import type { CancellationTier } from '@/types/hotels';
import type { Review } from '@/types/reviews';

export type ReservationStatus =
    | 'pending'
    | 'confirmed'
    | 'cancelled'
    | 'expired';

export type RefundStatus = 'pending' | 'succeeded' | 'failed';

/** A reservation as App\Http\Resources\ReservationResource sends it. Amounts in cents. */
export type Reservation = {
    code: string;
    status: ReservationStatus;
    status_label: string;
    awaits_payment: boolean;
    can_be_cancelled: boolean;
    check_in: string;
    check_out: string;
    nights: number;
    adults: number;
    children: number;
    guest_name: string;
    guest_phone: string;
    special_requests: string | null;
    price_breakdown: StayNight[];
    subtotal: number;
    discount: number;
    total_price: number;
    expires_at: string | null;
    paid_at: string | null;
    created_at: string | null;
    cancelled_at: string | null;
    cancelled_by_customer: boolean;
    cancellation_reason: string | null;
    refund_amount: number;
    refund_status: RefundStatus | null;
    refund_status_label: string | null;
    hotel: {
        name: string;
        slug: string;
        province: string;
        municipality: string;
        address: string;
        cover_url: string | null;
        cancellation_policy: CancellationTier[];
    };
    room: {
        name: string;
        room_type: string;
    };
    /** Only where the guest's review was loaded (the customer's pages). */
    can_be_reviewed?: boolean;
    review?: Review | null;
    /** Only for the hotel's owner and admins. */
    customer?: {
        name: string;
        email: string;
    };
};

/** What cancelling now would refund (CalculateRefund). */
export type RefundQuote = {
    percent: number;
    amount: number;
    days_before: number;
};
