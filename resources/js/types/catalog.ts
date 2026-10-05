import type { CancellationTier } from '@/types/hotels';

export type SearchCriteria = {
    q: string | null;
    check_in: string | null;
    check_out: string | null;
    adults: number;
    children: number;
    price_min: number | null;
    price_max: number | null;
    stars: number | null;
    amenities: number[];
    categories: number[];
    sort: 'recommended' | 'price_asc' | 'price_desc';
};

/** Cheapest stay for the searched dates (or tonight). Amounts in cents. */
export type CardPrice = {
    total: number;
    per_night: number;
    nights: number;
    has_dates: boolean;
    discount_percent: number;
};

export type HotelCardData = {
    id: number;
    name: string;
    slug: string;
    province: string;
    municipality: string;
    stars: number | null;
    cover_url: string | null;
    latitude: number | null;
    longitude: number | null;
    amenities: { name: string; icon: string }[];
    price: CardPrice;
};

export type StayNight = {
    date: string;
    base: number;
    discount_percent: number;
    price: number;
};

export type StayBreakdown = {
    nights: StayNight[];
    night_count: number;
    subtotal: number;
    discount: number;
    total: number;
    best_discount_percent: number;
};

export type RoomGroup = {
    key: string;
    room_type_id: number;
    room_type: string;
    capacity: number;
    price_per_night: number;
    rooms_count: number;
    /** Free rooms for the searched dates; null without dates. */
    available_count: number | null;
    description: string | null;
    image_url: string | null;
    fits_guests: boolean;
    stay: StayBreakdown | null;
};

export type PublicActivity = {
    id: number;
    name: string;
    description: string;
    price: number;
    starts_at: string | null;
    ends_at: string | null;
    capacity: number | null;
};

export type PublicOffer = {
    title: string;
    discount_percent: number;
    starts_on: string;
    ends_on: string;
    room_type_name: string | null;
};

export type PublicHotel = {
    id: number;
    name: string;
    slug: string;
    description: string;
    province: string;
    municipality: string;
    address: string;
    latitude: number | null;
    longitude: number | null;
    stars: number | null;
    cancellation_policy: CancellationTier[];
    images: string[];
    amenities: { name: string; icon: string }[];
    categories: string[];
    activities: PublicActivity[];
    offers: PublicOffer[];
};

export type MapPin = {
    id: number;
    name: string;
    latitude: number;
    longitude: number;
    href?: string;
    label?: string;
};
