<?php

use App\Http\Controllers\Catalog\DestinationController;
use App\Http\Controllers\Catalog\HomeController;
use App\Http\Controllers\Catalog\HotelCompareController;
use App\Http\Controllers\Catalog\HotelPageController;
use App\Http\Controllers\Catalog\HotelSearchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('hotels', HotelSearchController::class)->name('hotels.index');
Route::get('hotels/compare', HotelCompareController::class)->name('hotels.compare');
Route::get('hotels/{hotel:slug}', HotelPageController::class)->name('hotels.show');
Route::get('destinations', DestinationController::class)
    ->middleware('throttle:60,1')
    ->name('destinations.index');

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Booking is for customers; ReservationPolicy tells owners and admins why not.
    Route::get('reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('reservations', [ReservationController::class, 'store'])->name('reservations.store');

    Route::middleware('role:customer')->group(function () {
        Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
        Route::post('reservations/{reservation}/pay', [ReservationController::class, 'pay'])->name('reservations.pay');
        Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
    });
});

Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/manage.php';
