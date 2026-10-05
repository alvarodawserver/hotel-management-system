<?php

namespace App\Http\Controllers\Catalog;

use App\Enums\Province;
use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DestinationController extends Controller
{
    public const LIMIT = 8;

    /**
     * Suggestions for the "where are you going?" box: provinces, then
     * municipalities, then hotel names, matched without accents or case.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $needle = $this->normalize($validated['q']);
        $matches = fn (string $text): bool => Str::contains($this->normalize($text), $needle);

        $hotels = Hotel::query()->published()->get(['name', 'slug', 'municipality', 'province']);

        $provinces = collect(Province::cases())
            ->filter(fn (Province $province): bool => $matches($province->label()))
            ->map(fn (Province $province): array => [
                'type' => 'province',
                'label' => $province->label(),
                'value' => $province->label(),
            ]);

        $municipalities = $hotels
            ->filter(fn (Hotel $hotel): bool => $matches($hotel->municipality))
            ->unique('municipality')
            ->map(fn (Hotel $hotel): array => [
                'type' => 'municipality',
                'label' => "{$hotel->municipality} ({$hotel->province->label()})",
                'value' => $hotel->municipality,
            ]);

        $hotelNames = $hotels
            ->filter(fn (Hotel $hotel): bool => $matches($hotel->name))
            ->map(fn (Hotel $hotel): array => [
                'type' => 'hotel',
                'label' => $hotel->name,
                'value' => $hotel->name,
                'slug' => $hotel->slug,
            ]);

        return response()->json([
            'suggestions' => $provinces->concat($municipalities)->concat($hotelNames)->take(self::LIMIT)->values(),
        ]);
    }

    private function normalize(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }
}
