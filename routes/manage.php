<?php

use App\Http\Controllers\Manage\ActivityController;
use App\Http\Controllers\Manage\BulkRoomController;
use App\Http\Controllers\Manage\GeocodeController;
use App\Http\Controllers\Manage\HotelController;
use App\Http\Controllers\Manage\HotelImageController;
use App\Http\Controllers\Manage\HotelVisibilityController;
use App\Http\Controllers\Manage\OfferController;
use App\Http\Controllers\Manage\RoomController;
use App\Http\Controllers\Manage\RoomImageController;
use Illuminate\Support\Facades\Route;

/*
| Hotel management, shared by owners (their own hotels) and admins (any
| hotel). HotelPolicy decides which hotels each user may touch; nested
| bindings are scoped so a room, photo or activity must belong to the hotel.
*/
Route::middleware(['auth', 'verified', 'role:owner,admin'])
    ->prefix('manage')
    ->name('manage.')
    ->group(function () {
        Route::resource('hotels', HotelController::class)->except(['show']);

        Route::put('hotels/{hotel}/visibility', [HotelVisibilityController::class, 'update'])
            ->name('hotels.visibility.update');

        Route::scopeBindings()->group(function () {
            Route::resource('hotels.rooms', RoomController::class)->except(['show', 'create']);
            Route::post('hotels/{hotel}/rooms/bulk', [BulkRoomController::class, 'store'])
                ->name('hotels.rooms.bulk.store');

            Route::get('hotels/{hotel}/photos', [HotelImageController::class, 'index'])->name('hotels.images.index');
            Route::post('hotels/{hotel}/photos', [HotelImageController::class, 'store'])->name('hotels.images.store');
            Route::put('hotels/{hotel}/photos/order', [HotelImageController::class, 'reorder'])->name('hotels.images.reorder');
            Route::delete('hotels/{hotel}/photos/{image}', [HotelImageController::class, 'destroy'])->name('hotels.images.destroy');

            Route::post('hotels/{hotel}/rooms/{room}/photos', [RoomImageController::class, 'store'])->name('hotels.rooms.images.store');
            Route::put('hotels/{hotel}/rooms/{room}/photos/order', [RoomImageController::class, 'reorder'])->name('hotels.rooms.images.reorder');
            Route::delete('hotels/{hotel}/rooms/{room}/photos/{image}', [RoomImageController::class, 'destroy'])->name('hotels.rooms.images.destroy');

            Route::resource('hotels.activities', ActivityController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('hotels.offers', OfferController::class)->only(['index', 'store', 'update', 'destroy']);
        });

        Route::get('geocode', GeocodeController::class)
            ->middleware('throttle:30,1')
            ->name('geocode');
    });
