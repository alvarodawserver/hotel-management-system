<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * The guest's review of a past stay, written from the reservation page.
 */
class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Reservation $reservation): RedirectResponse
    {
        $review = $reservation->review()->make($request->validated());
        $review->user_id = $reservation->user_id;
        $review->hotel_id = $reservation->hotel_id;
        $review->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Thank you for your review!')]);

        return to_route('reservations.show', $reservation);
    }

    public function update(ReviewRequest $request, Reservation $reservation): RedirectResponse
    {
        $review = $reservation->review()->firstOrFail();
        $review->fill($request->validated());

        if ($review->isDirty()) {
            $review->edited_at = now();
            $review->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Review updated.')]);

        return to_route('reservations.show', $reservation);
    }

    /**
     * Deleting their own review lets the guest write it again.
     */
    public function destroy(Reservation $reservation): RedirectResponse
    {
        // A review removed by an admin is soft deleted, so it is not found.
        $review = $reservation->review()->firstOrFail();

        Gate::authorize('delete', $review);

        $review->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Review deleted.')]);

        return to_route('reservations.show', $reservation);
    }
}
