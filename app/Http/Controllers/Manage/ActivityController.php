<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\ActivityRequest;
use App\Http\Resources\HotelSummaryResource;
use App\Models\Activity;
use App\Models\Hotel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    /**
     * List the hotel's activities.
     */
    public function index(Hotel $hotel): Response
    {
        Gate::authorize('update', $hotel);

        return Inertia::render('manage/hotels/activities', [
            'hotel' => HotelSummaryResource::make($hotel)->resolve(),
            'activities' => $hotel->activities()
                ->orderBy('name')
                ->get()
                ->map(fn (Activity $activity): array => [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'description' => $activity->description,
                    'price' => $activity->price,
                    'starts_at' => $activity->starts_at ? substr($activity->starts_at, 0, 5) : null,
                    'ends_at' => $activity->ends_at ? substr($activity->ends_at, 0, 5) : null,
                    'capacity' => $activity->capacity,
                ]),
        ]);
    }

    /**
     * Store an activity.
     */
    public function store(ActivityRequest $request, Hotel $hotel): RedirectResponse
    {
        $hotel->activities()->create($request->activityAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Activity created.')]);

        return to_route('manage.hotels.activities.index', $hotel);
    }

    /**
     * Update an activity.
     */
    public function update(ActivityRequest $request, Hotel $hotel, Activity $activity): RedirectResponse
    {
        $activity->update($request->activityAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Activity updated.')]);

        return to_route('manage.hotels.activities.index', $hotel);
    }

    /**
     * Delete an activity.
     */
    public function destroy(Hotel $hotel, Activity $activity): RedirectResponse
    {
        Gate::authorize('update', $hotel);

        $activity->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Activity deleted.')]);

        return to_route('manage.hotels.activities.index', $hotel);
    }
}
