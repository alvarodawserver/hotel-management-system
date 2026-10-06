/** A review as App\Http\Resources\ReviewResource sends it. */
export type Review = {
    id: number;
    rating: number;
    comment: string;
    /** First name and the initial of the surname, e.g. "Álvaro V.". */
    author: string;
    stayed_on: string;
    room_type: string;
    created_at: string | null;
    edited_at: string | null;
    reply: string | null;
    replied_at: string | null;
    /** Set when an admin removed it; only its author sees it then. */
    removed_at: string | null;
    removal_reason: string | null;
};

/** With report and moderation details, for the hotel and the admins. */
export type ModeratedReview = Review & {
    author_name: string;
    author_email: string;
    reservation_code: string;
    is_reported: boolean;
    reported_at: string | null;
    report_reason: string | null;
    hotel?: {
        id: number;
        name: string;
        slug: string;
    };
};

/** Average (one decimal) and number of reviews; null average without reviews. */
export type HotelRating = {
    average: number | null;
    count: number;
};

export type RatingSummary = HotelRating & {
    distribution: { rating: number; count: number }[];
};
