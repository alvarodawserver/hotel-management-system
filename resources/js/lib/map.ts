import L from 'leaflet';

export const OSM_TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

export const OSM_ATTRIBUTION =
    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';

/** Roughly the Andalusian coastline, used when there is nothing to show yet. */
export const ANDALUSIAN_COAST_CENTER: [number, number] = [36.75, -4.6];

/**
 * A CSS pin (see .hotel-pin in app.css). Using a divIcon avoids Leaflet's
 * default marker images, whose paths break when bundled with Vite.
 */
export function hotelPinIcon(active = false): L.DivIcon {
    return L.divIcon({
        className: '',
        html: `<span class="hotel-pin${active ? ' hotel-pin--active' : ''}"></span>`,
        iconSize: [28, 28],
        iconAnchor: [4, 28],
        popupAnchor: [10, -26],
    });
}
