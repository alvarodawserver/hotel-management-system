import 'leaflet/dist/leaflet.css';
import { Link } from '@inertiajs/react';
import L from 'leaflet';
import { useEffect } from 'react';
import { MapContainer, Marker, Popup, TileLayer, useMap } from 'react-leaflet';
import {
    ANDALUSIAN_COAST_CENTER,
    hotelPinIcon,
    OSM_ATTRIBUTION,
    OSM_TILE_URL,
} from '@/lib/map';
import { cn } from '@/lib/utils';
import type { MapPin } from '@/types';

type Props = {
    pins: MapPin[];
    highlightedId?: number | null;
    className?: string;
    zoom?: number;
};

/** Keeps every pin in view whenever the list of pins changes. */
function FitToPins({ pins, zoom }: { pins: MapPin[]; zoom: number }) {
    const map = useMap();
    const signature = pins.map((pin) => pin.id).join(',');

    useEffect(() => {
        if (pins.length === 1) {
            map.setView([pins[0].latitude, pins[0].longitude], zoom);
        } else if (pins.length > 1) {
            map.fitBounds(
                L.latLngBounds(
                    pins.map((pin) => [pin.latitude, pin.longitude]),
                ),
                { padding: [32, 32] },
            );
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [signature]);

    return null;
}

/**
 * Read-only OpenStreetMap map with one pin per hotel. The highlighted pin
 * (e.g. the card under the cursor) is drawn in the accent colour.
 */
export default function HotelMap({
    pins,
    highlightedId = null,
    className,
    zoom = 14,
}: Props) {
    return (
        <MapContainer
            center={ANDALUSIAN_COAST_CENTER}
            zoom={7}
            scrollWheelZoom={false}
            className={cn('z-0 h-80 w-full rounded-2xl', className)}
        >
            <TileLayer url={OSM_TILE_URL} attribution={OSM_ATTRIBUTION} />
            <FitToPins pins={pins} zoom={zoom} />
            {pins.map((pin) => (
                <Marker
                    key={pin.id}
                    position={[pin.latitude, pin.longitude]}
                    icon={hotelPinIcon(pin.id === highlightedId)}
                    zIndexOffset={pin.id === highlightedId ? 1000 : 0}
                >
                    {pin.href && (
                        <Popup>
                            <Link href={pin.href} className="font-medium">
                                {pin.name}
                            </Link>
                            {pin.label && (
                                <span className="block text-xs">
                                    {pin.label}
                                </span>
                            )}
                        </Popup>
                    )}
                </Marker>
            ))}
        </MapContainer>
    );
}
