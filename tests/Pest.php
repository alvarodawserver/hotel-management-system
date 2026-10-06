<?php

use App\Contracts\PaymentGateway;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * A guest's review of a past stay in one of the hotel's rooms.
 */
function reviewHotel(Hotel $hotel, int $rating = 4): Review
{
    $room = $hotel->rooms()->first() ?? Room::factory()->for($hotel)->create();
    $reservation = Reservation::factory()->forRoom($room)->stay(-10)->create();

    return Review::factory()->forReservation($reservation)->create(['rating' => $rating]);
}

/**
 * The fake that stands in for Stripe in every test (see TestCase::setUp).
 */
function paymentGateway(): FakePaymentGateway
{
    return app(PaymentGateway::class);
}
