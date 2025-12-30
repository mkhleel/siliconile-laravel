<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - SpaceBooking Module
|--------------------------------------------------------------------------
|
| API endpoints for the SpaceBooking module.
|
*/

Route::prefix('spacebooking')->name('api.spacebooking.')->group(function () {
    // Public availability check
    Route::get('/availability', function () {
        // Return available slots for a given date/resource
    })->name('availability');

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Get user's bookings
        Route::get('/bookings', function () {
            // Return authenticated user's bookings
        })->name('bookings.index');

        // Cancel a booking
        Route::post('/bookings/{booking}/cancel', function ($booking) {
            // Cancel a booking
        })->name('bookings.cancel');
    });
});
