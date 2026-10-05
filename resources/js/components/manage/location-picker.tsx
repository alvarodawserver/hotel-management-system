import 'leaflet/dist/leaflet.css';
import { useHttp } from '@inertiajs/react';
import { MapPin, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    MapContainer,
    Marker,
    TileLayer,
    useMap,
    useMapEvents,
} from 'react-leaflet';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/hooks/use-translation';
import {
    ANDALUSIAN_COAST_CENTER,
    hotelPinIcon,
    OSM_ATTRIBUTION,
    OSM_TILE_URL,
} from '@/lib/map';
import { geocode } from '@/routes/manage';

type Position = { latitude: number; longitude: number };

type GeocodeResult = Position & { label: string };

type Props = {
    initial: Position | null;
    /** Builds the text to geocode from the address fields of the form. */
    getAddress: () => string;
    error?: string;
};

function ClickToPlace({ onPlace }: { onPlace: (position: Position) => void }) {
    useMapEvents({
        click: (event) =>
            onPlace({
                latitude: event.latlng.lat,
                longitude: event.latlng.lng,
            }),
    });

    return null;
}

function FlyTo({ position }: { position: Position | null }) {
    const map = useMap();

    useEffect(() => {
        if (position) {
            map.flyTo(
                [position.latitude, position.longitude],
                Math.max(map.getZoom(), 16),
            );
        }
        // Only fly when the position comes from a search, not while dragging.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [position?.latitude, position?.longitude]);

    return null;
}

/**
 * Lets the owner place the hotel on the map: search the written address,
 * then drag the pin (or click the map) to fine-tune it. The coordinates are
 * submitted with the surrounding form as hidden inputs.
 */
export default function LocationPicker({ initial, getAddress, error }: Props) {
    const { t } = useTranslation();
    const [position, setPosition] = useState<Position | null>(initial);
    const [searchTarget, setSearchTarget] = useState<Position | null>(null);
    const [message, setMessage] = useState<string | null>(null);
    const http = useHttp<Record<string, never>, { results: GeocodeResult[] }>(
        {},
    );

    const searchAddress = async () => {
        const address = getAddress().trim();
        setMessage(null);

        if (address.length < 3) {
            setMessage(
                t('Write the address, municipality and province first.'),
            );

            return;
        }

        try {
            const response = await http.get(
                geocode.url({ query: { q: address } }),
            );
            const [first] = response.results;

            if (!first) {
                setMessage(
                    t(
                        'We could not find that address. Place the pin on the map by clicking where the hotel is.',
                    ),
                );

                return;
            }

            const found = {
                latitude: first.latitude,
                longitude: first.longitude,
            };
            setPosition(found);
            setSearchTarget(found);
        } catch {
            setMessage(
                t(
                    'The map service is not available right now. Try again in a moment.',
                ),
            );
        }
    };

    return (
        <fieldset className="space-y-3">
            <legend className="text-sm font-medium">{t('Location')}</legend>
            <p className="text-sm text-muted-foreground">
                {t(
                    'Search the address, then drag the pin or click the map to put it exactly on the hotel. Required to publish the hotel.',
                )}
            </p>

            <div className="flex flex-wrap items-center gap-3">
                <Button
                    type="button"
                    variant="outline"
                    onClick={searchAddress}
                    disabled={http.processing}
                >
                    {http.processing ? <Spinner /> : <Search />}
                    {t('Find on the map')}
                </Button>
                {position && (
                    <span className="inline-flex items-center gap-1 text-sm text-muted-foreground">
                        <MapPin className="size-4" />
                        {position.latitude.toFixed(5)},{' '}
                        {position.longitude.toFixed(5)}
                    </span>
                )}
            </div>

            {message && (
                <p className="text-sm text-muted-foreground">{message}</p>
            )}

            <MapContainer
                center={
                    position
                        ? [position.latitude, position.longitude]
                        : ANDALUSIAN_COAST_CENTER
                }
                zoom={position ? 16 : 7}
                className="z-0 h-72 w-full rounded-xl border"
            >
                <TileLayer url={OSM_TILE_URL} attribution={OSM_ATTRIBUTION} />
                <ClickToPlace onPlace={setPosition} />
                <FlyTo position={searchTarget} />
                {position && (
                    <Marker
                        position={[position.latitude, position.longitude]}
                        icon={hotelPinIcon(true)}
                        draggable
                        eventHandlers={{
                            dragend: (event) => {
                                const { lat, lng } = event.target.getLatLng();
                                setPosition({ latitude: lat, longitude: lng });
                            },
                        }}
                    />
                )}
            </MapContainer>

            <input
                type="hidden"
                name="latitude"
                value={position?.latitude ?? ''}
            />
            <input
                type="hidden"
                name="longitude"
                value={position?.longitude ?? ''}
            />
            <InputError message={error} />
        </fieldset>
    );
}
