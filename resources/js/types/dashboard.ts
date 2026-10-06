/** A figure this month and the previous one. */
export type MonthComparison = {
    current: number;
    previous: number;
};

export type OwnerKpis = {
    revenue: MonthComparison;
    occupancy: {
        percent: number;
        booked_nights: number;
        available_nights: number;
    };
    arrivals: { today: number; week: number };
    rating: { average: number | null; count: number };
};

export type AdminKpis = {
    revenue: MonthComparison;
    bookings: MonthComparison;
    hotels: { published: number; total: number };
    new_users: MonthComparison;
};

/** Revenue (cents) and confirmed stays of a check-in month ("Y-m"). */
export type MonthRevenue = {
    month: string;
    revenue: number;
    stays: number;
};

export type AttentionItem =
    | {
          type: 'unanswered_reviews';
          hotel: string;
          count: number;
          href: string;
      }
    | { type: 'blocked_hotel'; hotel: string; reason: string; href: string }
    | { type: 'hidden_hotel'; hotel: string; href: string }
    | {
          type: 'incomplete_hotel';
          hotel: string;
          missing: ('active_room' | 'photo' | 'location')[];
          href: string;
      }
    | {
          type: 'reported_reviews' | 'failed_refunds' | 'blocked_hotels';
          count: number;
          href: string;
      };

export type UpcomingArrival = {
    code: string;
    guest_name: string;
    guests: number;
    hotel: string;
    room: string;
    check_in: string;
    nights: number;
};

export type TopHotel = {
    hotel: string;
    revenue: number;
    stays: number;
};

export type ProvinceStays = {
    province: string;
    stays: number;
};
