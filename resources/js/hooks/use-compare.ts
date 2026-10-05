import { useEffect, useState } from 'react';

export const MAX_COMPARED_HOTELS = 3;

const STORAGE_KEY = 'compare-hotels';
const CHANGE_EVENT = 'compare-hotels-change';

export type ComparedHotel = { id: number; name: string };

function read(): ComparedHotel[] {
    try {
        const stored = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]');

        return Array.isArray(stored)
            ? stored.slice(0, MAX_COMPARED_HOTELS)
            : [];
    } catch {
        return [];
    }
}

function write(hotels: ComparedHotel[]): void {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(hotels));
    } catch {
        // Storage can be unavailable (private mode); the selection then
        // only lives until the page changes.
    }

    window.dispatchEvent(new Event(CHANGE_EVENT));
}

export type UseCompareReturn = {
    hotels: ComparedHotel[];
    isSelected: (hotelId: number) => boolean;
    isFull: boolean;
    toggle: (hotel: ComparedHotel) => void;
    clear: () => void;
};

/**
 * The hotels picked for comparison (up to 3), kept in the browser so the
 * selection survives moving between result pages.
 */
export function useCompare(): UseCompareReturn {
    const [hotels, setHotels] = useState<ComparedHotel[]>([]);

    useEffect(() => {
        const sync = () => setHotels(read());
        sync();
        window.addEventListener(CHANGE_EVENT, sync);
        window.addEventListener('storage', sync);

        return () => {
            window.removeEventListener(CHANGE_EVENT, sync);
            window.removeEventListener('storage', sync);
        };
    }, []);

    const isSelected = (hotelId: number) =>
        hotels.some((hotel) => hotel.id === hotelId);

    const toggle = (hotel: ComparedHotel) => {
        const current = read();

        if (current.some((selected) => selected.id === hotel.id)) {
            write(current.filter((selected) => selected.id !== hotel.id));
        } else if (current.length < MAX_COMPARED_HOTELS) {
            write([...current, hotel]);
        }
    };

    return {
        hotels,
        isSelected,
        isFull: hotels.length >= MAX_COMPARED_HOTELS,
        toggle,
        clear: () => write([]),
    };
}
