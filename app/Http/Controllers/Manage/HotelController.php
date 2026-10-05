<?php

namespace App\Http\Controllers\Manage;

use App\Enums\Province;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreHotelRequest;
use App\Http\Requests\Manage\UpdateHotelRequest;
use App\Http\Resources\HotelSummaryResource;
use App\Models\Amenity;
use App\Models\Category;
use App\Models\Hotel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class HotelController extends Controller
{
    /**
     * List the signed-in owner's hotels. Admins manage all hotels from the
     * admin hotel list instead.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if ($request->user()->isAdmin()) {
            return to_route('admin.hotels.index');
        }

        $hotels = $request->user()->hotels()
            ->with('coverImage')
            ->withCount([
                'rooms',
                'rooms as active_rooms_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Hotel $hotel): array => [
                ...HotelSummaryResource::make($hotel)->resolve(),
                'province' => $hotel->province->label(),
                'municipality' => $hotel->municipality,
                'cover_url' => $hotel->coverImage?->url,
                'rooms_count' => $hotel->rooms_count,
                'active_rooms_count' => $hotel->active_rooms_count,
            ]);

        return Inertia::render('manage/hotels/index', [
            'hotels' => $hotels,
            'canCreate' => $request->user()->can('create', Hotel::class),
        ]);
    }

    /**
     * Show the form for creating a hotel.
     */
    public function create(): Response
    {
        Gate::authorize('create', Hotel::class);

        return Inertia::render('manage/hotels/create', [
            ...$this->formOptions(),
            'defaultCancellationPolicy' => Hotel::DEFAULT_CANCELLATION_POLICY,
        ]);
    }

    /**
     * Store a new hotel. It starts hidden until the owner publishes it.
     */
    public function store(StoreHotelRequest $request): RedirectResponse
    {
        $hotel = DB::transaction(function () use ($request): Hotel {
            $hotel = new Hotel($request->hotelAttributes());
            $hotel->owner()->associate($request->user());
            $hotel->save();

            $hotel->amenities()->sync($request->input('amenity_ids', []));
            $hotel->categories()->sync($request->input('category_ids', []));

            return $hotel;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hotel created. Now add its rooms.')]);

        return to_route('manage.hotels.rooms.index', $hotel);
    }

    /**
     * Show the form for editing the hotel's details.
     */
    public function edit(Request $request, Hotel $hotel): Response
    {
        Gate::authorize('update', $hotel);

        $hotel->load('owner', 'amenities:id', 'categories:id');

        return Inertia::render('manage/hotels/edit', [
            ...$this->formOptions(),
            'hotel' => [
                ...HotelSummaryResource::make($hotel)->resolve(),
                'description' => $hotel->description,
                'province' => $hotel->province->value,
                'municipality' => $hotel->municipality,
                'address' => $hotel->address,
                'latitude' => $hotel->latitude,
                'longitude' => $hotel->longitude,
                'stars' => $hotel->stars,
                'cancellation_policy' => $hotel->cancellation_policy,
                'amenity_ids' => $hotel->amenities->modelKeys(),
                'category_ids' => $hotel->categories->modelKeys(),
            ],
            'publishing' => [
                'has_active_rooms' => $hotel->hasActiveRooms(),
                'has_images' => $hotel->images()->exists(),
                'has_location' => $hotel->hasLocation(),
            ],
            'canDelete' => $request->user()->can('delete', $hotel),
        ]);
    }

    /**
     * Update the hotel's details.
     */
    public function update(UpdateHotelRequest $request, Hotel $hotel): RedirectResponse
    {
        DB::transaction(function () use ($request, $hotel): void {
            $hotel->update($request->hotelAttributes());
            $hotel->amenities()->sync($request->input('amenity_ids', []));
            $hotel->categories()->sync($request->input('category_ids', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hotel updated.')]);

        return to_route('manage.hotels.edit', $hotel);
    }

    /**
     * Delete the hotel (soft delete, so past reservations keep it).
     */
    public function destroy(Request $request, Hotel $hotel): RedirectResponse
    {
        Gate::authorize('delete', $hotel);

        if ($hotel->reservations()->active()->exists()) {
            throw ValidationException::withMessages([
                'hotel' => __('This hotel has active reservations and cannot be deleted. You can hide it instead.'),
            ]);
        }

        $hotel->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hotel deleted.')]);

        return $request->user()->isAdmin()
            ? to_route('admin.hotels.index')
            : to_route('manage.hotels.index');
    }

    /**
     * Options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'provinces' => Province::options(),
            'amenities' => Amenity::query()->get()
                ->map(fn (Amenity $amenity): array => [
                    'id' => $amenity->id,
                    'name' => $amenity->translation(),
                    'icon' => $amenity->icon,
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            'categories' => Category::query()->get()
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->translation(),
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
        ];
    }
}
