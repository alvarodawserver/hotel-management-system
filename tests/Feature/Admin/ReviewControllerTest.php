<?php

use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewRemoved;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

it('lists reported reviews first, then all and removed ones', function () {
    $admin = User::factory()->admin()->create();
    Review::factory()->reported()->create();
    Review::factory()->create();
    Review::factory()->removed()->create();

    $this->actingAs($admin)
        ->get(route('admin.reviews.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/reviews/index')
            ->where('tab', 'reported')
            ->where('reportedCount', 1)
            ->has('reviews.data', 1)
            ->where('reviews.data.0.is_reported', true));

    $this->actingAs($admin)
        ->get(route('admin.reviews.index', ['tab' => 'all']))
        ->assertInertia(fn (Assert $page) => $page->has('reviews.data', 2));

    $this->actingAs($admin)
        ->get(route('admin.reviews.index', ['tab' => 'removed']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('reviews.data', 1)
            ->where('reviews.data.0.removal_reason', 'Offensive language'));
});

it('removes a review with a reason and tells its author', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $review = Review::factory()->reported()->create();

    $this->actingAs($admin)
        ->delete(route('admin.reviews.destroy', $review), ['reason' => 'Insults'])
        ->assertSessionHasNoErrors();

    $review = Review::withTrashed()->find($review->id);
    expect($review)
        ->trashed()->toBeTrue()
        ->deleted_by->toBe($admin->id)
        ->deletion_reason->toBe('Insults')
        ->isReported()->toBeFalse();

    Notification::assertSentTo($review->user, ReviewRemoved::class);
});

it('requires a reason to remove a review', function () {
    $review = Review::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.reviews.destroy', $review), ['reason' => ''])
        ->assertSessionHasErrors('reason');

    expect($review->fresh()->trashed())->toBeFalse();
});

it('dismisses a report and keeps the review', function () {
    $review = Review::factory()->reported()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.reviews.report.destroy', $review));

    expect($review->fresh())
        ->isReported()->toBeFalse()
        ->trashed()->toBeFalse();
});

it('keeps owners and customers out of moderation', function (string $role) {
    $review = Review::factory()->create();
    $user = $role === 'owner' ? User::factory()->owner()->create() : User::factory()->create();

    $this->actingAs($user)->get(route('admin.reviews.index'))->assertRedirect();
    $this->actingAs($user)->delete(route('admin.reviews.destroy', $review), ['reason' => 'x']);

    expect($review->fresh()->trashed())->toBeFalse();
})->with(['owner', 'customer']);

it('emails the removal reason in the author’s language', function () {
    $review = Review::factory()->removed()->create();

    App::setLocale('es');
    $mail = (new ReviewRemoved($review))->toMail($review->user);

    expect($mail->subject)->toBe("Hemos retirado tu opinión sobre {$review->hotel->name}")
        ->and((string) $mail->render())->toContain('Offensive language', 'Como la opinión fue retirada');
});
