<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Events Module Web Routes
|--------------------------------------------------------------------------
|
| Public event browsing and booking routes using Livewire Volt.
| Note: Volt components use direct names since module paths are mounted via Volt::mount()
|
*/

Route::prefix('events')->name('events.')->group(function () {
    // Event listing
    Volt::route('/', 'event-list')->name('index');

    // Authenticated routes (must be before {slug} catch-all)
    Route::middleware(['auth', 'verified'])->group(function () {
        // My tickets
        Volt::route('/my-tickets', 'my-tickets')->name('my-tickets');

        // View specific ticket
        Volt::route('/my-tickets/{reference}', 'view-ticket')->name('view-ticket');
    });

    // Event details (catch-all - must be last)
    Volt::route('/{slug}', 'event-detail')->name('show');

    // Booking flow (can be guest or authenticated)
    Volt::route('/{slug}/book', 'booking-flow')->name('book');
});
