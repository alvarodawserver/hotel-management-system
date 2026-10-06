<?php

namespace App\Actions\Reviews;

use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewRemoved;
use Illuminate\Support\Facades\DB;

class RemoveReview
{
    /**
     * Take an offensive or inappropriate review down. It is soft deleted, so
     * it leaves the hotel's page and average, while its author still sees the
     * reason and cannot review that stay again. The author is told by email.
     */
    public function handle(Review $review, User $admin, string $reason): void
    {
        DB::transaction(function () use ($review, $admin, $reason): void {
            $review->forceFill([
                'deleted_by' => $admin->id,
                'deletion_reason' => $reason,
                'reported_at' => null,
                'reported_by' => null,
                'report_reason' => null,
            ])->save();

            $review->delete();

            $review->user->notify(new ReviewRemoved($review));
        });
    }
}
