<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Reviews\RemoveReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RemoveReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Review moderation: reports from owners first, every review, and the ones
 * already removed.
 */
class ReviewController extends Controller
{
    public const TABS = ['reported', 'all', 'removed'];

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(self::TABS)],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $tab = $filters['tab'] ?? 'reported';

        $reviews = Review::query()
            ->with(['user', 'hotel', 'reservation.room.roomType'])
            ->when($tab === 'reported', fn (Builder $query) => $query->whereNotNull('reported_at')->orderBy('reported_at'))
            ->when($tab === 'removed', fn (Builder $query) => $query->onlyTrashed()->orderByDesc('deleted_at'))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query
                    ->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('hotel', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")));
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Review $review): array => ReviewResource::make($review)->withModeration()->resolve($request));

        return Inertia::render('admin/reviews/index', [
            'reviews' => $reviews,
            'tab' => $tab,
            'reportedCount' => Review::query()->whereNotNull('reported_at')->count(),
            'filters' => ['search' => $filters['search'] ?? ''],
        ]);
    }

    /**
     * Remove an offensive or inappropriate review, telling its author why.
     */
    public function destroy(RemoveReviewRequest $request, Review $review, RemoveReview $removeReview): RedirectResponse
    {
        $removeReview->handle($review, $request->user(), $request->string('reason')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Review removed. Its author has been told why.')]);

        return back();
    }

    /**
     * Keep a reported review that does not break the rules.
     */
    public function dismissReport(Review $review): RedirectResponse
    {
        Gate::authorize('moderate', $review);

        $review->forceFill(['reported_at' => null, 'reported_by' => null, 'report_reason' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Report dismissed. The review stays published.')]);

        return back();
    }
}
