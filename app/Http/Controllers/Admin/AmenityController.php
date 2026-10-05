<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AmenityIcon;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AmenityRequest;
use App\Models\Amenity;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AmenityController extends Controller
{
    /**
     * List amenities with the number of hotels using each one.
     */
    public function index(): Response
    {
        return Inertia::render('admin/amenities/index', [
            'amenities' => Amenity::query()
                ->withCount('hotels')
                ->get()
                ->map(fn (Amenity $amenity): array => [
                    'id' => $amenity->id,
                    'name' => $amenity->translation(),
                    'translations' => $amenity->translations(),
                    'icon' => $amenity->icon,
                    'usage_count' => $amenity->hotels_count,
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            'icons' => array_column(AmenityIcon::cases(), 'value'),
        ]);
    }

    public function store(AmenityRequest $request): RedirectResponse
    {
        Amenity::create([
            'name' => $request->translatedName(),
            'icon' => $request->string('icon')->toString(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Amenity created.')]);

        return to_route('admin.amenities.index');
    }

    public function update(AmenityRequest $request, Amenity $amenity): RedirectResponse
    {
        $amenity->update([
            'name' => $request->translatedName(),
            'icon' => $request->string('icon')->toString(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Amenity updated.')]);

        return to_route('admin.amenities.index');
    }

    /**
     * Delete the amenity; hotels using it simply lose it.
     */
    public function destroy(Amenity $amenity): RedirectResponse
    {
        $amenity->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Amenity deleted.')]);

        return to_route('admin.amenities.index');
    }
}
