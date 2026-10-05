<?php

namespace App\Http\Controllers\Manage;

use App\Actions\Hotels\ChangeHotelVisibility;
use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class HotelVisibilityController extends Controller
{
    /**
     * Show or hide the hotel in the public catalogue.
     */
    public function update(Request $request, Hotel $hotel, ChangeHotelVisibility $changeVisibility): RedirectResponse
    {
        Gate::authorize('update', $hotel);

        $validated = $request->validate([
            'is_visible' => ['required', 'boolean'],
        ]);

        $visible = (bool) $validated['is_visible'];
        $changeVisibility->handle($hotel, $visible);

        // Hiding only stops new bookings; existing ones are kept.
        $upcomingCount = $visible ? 0 : $hotel->reservations()->active()->count();

        Inertia::flash('toast', match (true) {
            $visible => ['type' => 'success', 'message' => __('The hotel is now visible.')],
            $upcomingCount > 0 => ['type' => 'warning', 'message' => trans_choice(
                'The hotel is now hidden. It has :count upcoming reservation, which is not cancelled.|The hotel is now hidden. It has :count upcoming reservations, which are not cancelled.',
                $upcomingCount,
            )],
            default => ['type' => 'success', 'message' => __('The hotel is now hidden.')],
        });

        return back();
    }
}
