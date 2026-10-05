<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GeocodeController extends Controller
{
    /**
     * Turn an address into map coordinates using OpenStreetMap's Nominatim,
     * limited to Spain. Results are cached for a day to respect its usage
     * policy (and because addresses rarely move).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $query = Str::squish($validated['q']);

        try {
            $results = Cache::remember(
                'geocode:'.md5(Str::lower($query)),
                now()->addDay(),
                fn (): array => $this->search($query),
            );
        } catch (ConnectionException|RequestException) {
            return response()->json([
                'message' => __('The map service is not available right now. Try again in a moment.'),
            ], 503);
        }

        return response()->json(['results' => $results]);
    }

    /**
     * @return list<array{label: string, latitude: float, longitude: float}>
     *
     * @throws ConnectionException|RequestException
     */
    private function search(string $query): array
    {
        $response = Http::baseUrl(config('services.nominatim.url'))
            ->withUserAgent(config('services.nominatim.user_agent'))
            ->acceptJson()
            ->timeout(5)
            ->get('/search', [
                'q' => $query,
                'format' => 'jsonv2',
                'countrycodes' => 'es',
                'limit' => 5,
                'accept-language' => app()->getLocale(),
            ])
            ->throw();

        /** @var list<array{display_name: string, lat: string, lon: string}> $places */
        $places = $response->json() ?? [];

        return array_map(fn (array $place): array => [
            'label' => $place['display_name'],
            'latitude' => (float) $place['lat'],
            'longitude' => (float) $place['lon'],
        ], $places);
    }
}
