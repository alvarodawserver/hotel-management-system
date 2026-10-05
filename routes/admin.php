<?php

use App\Http\Controllers\Admin\AmenityController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\HotelBlockController;
use App\Http\Controllers\Admin\HotelController;
use App\Http\Controllers\Admin\RoomTypeController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);

        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');

        Route::get('hotels', [HotelController::class, 'index'])->name('hotels.index');
        Route::post('hotels/{hotel}/block', [HotelBlockController::class, 'store'])->name('hotels.block.store');
        Route::delete('hotels/{hotel}/block', [HotelBlockController::class, 'destroy'])->name('hotels.block.destroy');

        Route::resource('amenities', AmenityController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('room-types', RoomTypeController::class)->only(['index', 'store', 'update', 'destroy']);
    });
