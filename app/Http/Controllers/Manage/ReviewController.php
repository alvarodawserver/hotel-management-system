<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\ReportReviewRequest;
use App\Http\Requests\Manage\ReviewReplyRequest;
use App\Http\Resources\HotelSummaryResource;
use App\Http\Resources\ReviewResource;
use App\Models\Hotel;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The hotel's reviews as its owner (and admins) see them: they answer them
 * publicly and the owner reports offensive ones to the admins.
 */
class ReviewController extends Controller
{
    public function index(Request $request, Hotel $hotel): Response
    {
        Gate::authorize('update', $hotel);

        $reviews = $hotel->reviews()
            ->with(['user', 'reservation.room.roomType'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Review $review): array => ReviewResource::make($review)->withModeration()->resolve($request));

        return Inertia::render('manage/hotels/reviews', [
            'hotel' => HotelSummaryResource::make($hotel)->resolve(),
            'rating' => $hotel->ratingSummary(),
            'reviews' => $reviews,
            'canReport' => $request->user()->isOwner(),
        ]);
    }

    /**
     * Write or edit the hotel's public answer to a review.
     */
    public function reply(ReviewReplyRequest $request, Hotel $hotel, Review $review): RedirectResponse
    {
        $review->forceFill([
            'reply' => $request->string('reply')->toString(),
            'replied_at' => now(),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reply published.')]);

        return back();
    }

    public function destroyReply(Hotel $hotel, Review $review): RedirectResponse
    {
        Gate::authorize('reply', $review);

        $review->forceFill(['reply' => null, 'replied_at' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reply deleted.')]);

        return back();
    }

    /**
     * Ask the admins to review an offensive or inappropriate review.
     */
    public function report(ReportReviewRequest $request, Hotel $hotel, Review $review): RedirectResponse
    {
        $review->forceFill([
            'reported_at' => now(),
            'reported_by' => $request->user()->id,
            'report_reason' => $request->string('reason')->toString(),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Review reported. An administrator will check it.')]);

        return back();
    }
}
