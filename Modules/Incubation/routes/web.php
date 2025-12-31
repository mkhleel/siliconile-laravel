<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Web Routes - Incubation Module
|--------------------------------------------------------------------------
|
| Public-facing routes for the incubation program.
|
*/

// Public cohort listing
Route::get('/incubation', fn () => view('incubation::pages.index'))->name('incubation.index');

// Public application form (Volt component)
Volt::route('/apply/{cohort:slug}', 'incubation::pages.apply')
    ->name('incubation.apply');

// Application status check
Volt::route('/application/{applicationCode}/status', 'incubation::pages.application-status')
    ->name('incubation.application.status');

// Authenticated routes for accepted startups
Route::middleware(['auth'])->prefix('portal/incubation')->name('incubation.portal.')->group(function () {
    // Dashboard for accepted startups
    Volt::route('/dashboard', 'incubation::portal.dashboard')
        ->name('dashboard');

    // View milestones
    Volt::route('/milestones', 'incubation::portal.milestones')
        ->name('milestones');

    // Mentorship sessions
    Volt::route('/mentorship', 'incubation::portal.mentorship')
        ->name('mentorship');

    // Book a session
    Volt::route('/mentorship/book', 'incubation::portal.book-session')
        ->name('mentorship.book');
});
