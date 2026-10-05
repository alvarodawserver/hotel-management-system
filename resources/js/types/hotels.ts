export type HotelSummary = {
    id: number;
    name: string;
    slug: string;
    is_visible: boolean;
    is_blocked: boolean;
    blocked_reason: string | null;
    owner_name?: string;
};

export type CancellationTier = {
    days_before: number;
    refund_percent: number;
};

export type AmenityOption = {
    id: number;
    name: string;
    icon: string;
};

export type CategoryOption = {
    id: number;
    name: string;
};

export type RoomTypeOption = {
    id: number;
    name: string;
    default_capacity: number;
};

export type GalleryImage = {
    id: number;
    url: string;
};

export type ManagedRoom = {
    id: number;
    name: string;
    room_type_id: number;
    capacity: number;
    price_per_night: number;
    description: string | null;
    is_active: boolean;
};

export type ManagedActivity = {
    id: number;
    name: string;
    description: string;
    price: number;
    starts_at: string | null;
    ends_at: string | null;
    capacity: number | null;
};

export type OfferStatus = 'active' | 'upcoming' | 'finished' | 'disabled';

export type ManagedOffer = {
    id: number;
    title: string;
    room_type_id: number | null;
    room_type_name: string | null;
    discount_percent: number;
    starts_on: string;
    ends_on: string;
    is_active: boolean;
    status: OfferStatus;
};

/**
 * An admin-managed catalogue entry (amenity, category or room type) with its
 * name in every locale, used by the admin edit dialogs.
 */
export type CatalogueEntry = {
    id: number;
    name: string;
    translations: Record<string, string>;
    usage_count: number;
};
