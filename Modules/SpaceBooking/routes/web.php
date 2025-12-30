<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Web Routes - SpaceBooking Module
|--------------------------------------------------------------------------
|
| Routes for the SpaceBooking module frontend (member portal).
|
*/

Route::middleware(['auth', 'verified'])->prefix('booking')->name('booking.')->group(function () {
    // Booking wizard
    Volt::route('/', 'spacebooking::booking-wizard')->name('wizard');

    // Member's booking history
    Route::get('/my-bookings', function () {
        // This would be a Volt component for viewing user's bookings
        return view('spacebooking::pages.my-bookings');
    })->name('my-bookings');
});
