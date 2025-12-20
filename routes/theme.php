<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Theme Routes Example
|--------------------------------------------------------------------------
|
| These routes demonstrate how to use the converted Blade theme.
| You can integrate these into your main web.php routes file.
|
*/

// Home page
Volt::route('/', 'home')->name('home');
Volt::route('/about', 'about')->name('about');
Volt::route('/programs', 'programs')->name('programs');
Volt::route('/startups', 'startups')->name('startups');
Volt::route('/events', 'events')->name('events');
Volt::route('/co-working', 'coworking')->name('coworking');
Volt::route('/contact', 'contact')->name('contact');


// Newsletter subscription
Route::post('/newsletter/subscribe', function () {
    // Handle newsletter subscription logic here
    return back()->with('success', 'Thank you for subscribing!');
})->name('newsletter.subscribe');
