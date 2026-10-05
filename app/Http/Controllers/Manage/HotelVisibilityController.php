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

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $visible ? __('The hotel is now visible.') : __('The hotel is now hidden.'),
        ]);

        return back();
    }
}
