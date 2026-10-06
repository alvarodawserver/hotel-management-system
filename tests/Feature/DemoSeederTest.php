<?php

use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Models\Hotel;
use App\Models\Image;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/*
 * Seeding takes a few seconds, so each test checks several rules of the
 * same seeded data instead of seeding once per rule.
 */

test('the demo data follows the booking rules', function () {
    Storage::fake('public');

    $this->seed(DatabaseSeeder::class);

    // No room is held twice for the same night.
    Reservation::query()->blocking()->orderBy('check_in')->get()
        ->groupBy('room_id')
        ->each(fn (Collection $stays) => $stays->values()->sliding(2)->each(
            fn (Collection $pair) => expect($pair->last()->check_in->gte($pair->first()->check_out))->toBeTrue(),
        ));

    // Every total is the sum of its nights.
    Reservation::all()->each(fn (Reservation $reservation) => expect($reservation->total_price)
        ->toBe(array_sum(array_column($reservation->price_breakdown, 'price')))
        ->toBe($reservation->subtotal - $reservation->discount)
        ->and($reservation->price_breakdown)->toHaveCount($reservation->nights()));

    // Published hotels have what publishing requires, and their photos exist.
    Hotel::published()->get()->each(fn (Hotel $hotel) => expect($hotel->canBePublished())->toBeTrue());
    Image::all()->each(fn (Image $image) => Storage::disk('public')->assertExists($image->path));

    // Reviews are about finished, confirmed stays of their author.
    Review::withTrashed()->with('reservation')->get()->each(fn (Review $review) => expect($review->reservation->status)
        ->toBe(ReservationStatus::Confirmed)
        ->and($review->reservation->check_out->toDateString() <= Reservation::today()->toDateString())->toBeTrue()
        ->and($review->user_id)->toBe($review->reservation->user_id)
        ->and($review->hotel_id)->toBe($review->reservation->hotel_id));

    expect(Reservation::count())->toBeGreaterThan(1000)
        ->and(Review::count())->toBeGreaterThan(100);
});

test('the demo shows every state and its accounts work', function () {
    Storage::fake('public');

    $this->seed(DatabaseSeeder::class);

    expect(Hotel::where('is_visible', false)->count())->toBe(1)
        ->and(Hotel::whereNotNull('blocked_at')->count())->toBe(1)
        ->and(Reservation::where('refund_status', RefundStatus::Failed)->count())->toBe(1)
        ->and(Review::whereNotNull('reported_at')->count())->toBe(2)
        ->and(Review::onlyTrashed()->count())->toBe(1)
        ->and(User::whereNotNull('deactivated_at')->count())->toBe(1);

    $latestStay = User::where('email', 'customer@example.com')->sole()
        ->reservations()
        ->where('status', ReservationStatus::Confirmed)
        ->whereDate('check_out', '<=', Reservation::today())
        ->latest('check_out')
        ->first();

    expect($latestStay->canBeReviewed())->toBeTrue();

    foreach (['admin@example.com', 'owner@example.com', 'customer@example.com'] as $email) {
        $this->post(route('login.store'), ['email' => $email, 'password' => 'password']);
        $this->assertAuthenticatedAs(User::where('email', $email)->sole());
        $this->post(route('logout'));
    }
});
