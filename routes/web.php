<?php

use App\Http\Controllers\Catalog\DestinationController;
use App\Http\Controllers\Catalog\HomeController;
use App\Http\Controllers\Catalog\HotelCompareController;
use App\Http\Controllers\Catalog\HotelPageController;
use App\Http\Controllers\Catalog\HotelSearchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
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
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/manage.php';
