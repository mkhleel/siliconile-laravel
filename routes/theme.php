<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Theme Routes
|--------------------------------------------------------------------------
|
| Public frontend routes for the Siliconile website.
| Includes marketing pages, membership, spaces, and events.
|
*/

// ============================================
// PUBLIC PAGES
// ============================================

// Home & About
Volt::route('/', 'home')->name('home');
Volt::route('/about', 'about')->name('about');

// Programs & Startups
Volt::route('/programs', 'programs')->name('programs');
Volt::route('/startups', 'startups')->name('startups');
Volt::route('/programs/apply', 'programs.apply')->name('programs.apply');
Volt::route('/programs/{cohort:slug}', 'cohorts.show')->name('cohorts.show');

// Mentors
Volt::route('/mentors/apply', 'become-mentor')->name('mentors.apply');

// Events - Handled by Events module (Modules/Events/routes/web.php)
// The Events module registers its own routes at /events
// - /events → events::event-list (events.index)
// - /events/{slug} → events::event-detail (events.show)
// - /events/{slug}/book → events::booking-flow (events.book)

// Coworking & Spaces
Volt::route('/co-working', 'coworking')->name('coworking');
Volt::route('/spaces', 'spaces')->name('spaces');
Volt::route('/spaces/{slug}', 'spaces.show')->name('spaces.show');

// Membership & Pricing
Volt::route('/pricing', 'pricing')->name('pricing');

// Application (Membership)
Volt::route('/apply', 'apply')->name('apply');
Volt::route('/apply/success', 'application-success')->name('application.success');

// Incubation Status Check (public)
Volt::route('/incubation/status', 'incubation-status')->name('incubation.status');

// Contact
Volt::route('/contact', 'contact')->name('contact');

// ============================================
// MEMBER PORTAL (Authenticated)
// ============================================

Route::middleware(['auth', 'verified'])->prefix('member')->name('member.')->group(function () {
    // Dashboard
    Volt::route('/dashboard', 'member.dashboard')->name('portal');

    // Bookings
    Volt::route('/bookings', 'member.bookings.index')->name('bookings');
    Volt::route('/bookings/{booking}', 'member.bookings.show')->name('bookings.show');

    // Orders & Billing
    Volt::route('/orders', 'member.orders.index')->name('orders');
    Volt::route('/orders/{order}', 'member.orders.show')->name('orders.show');

    // Subscription
    Volt::route('/subscription', 'member.subscription')->name('subscription');
});

// ============================================
// UTILITY ROUTES
// ============================================

// Newsletter subscription
Route::post('/newsletter/subscribe', function () {
    // TODO: Implement newsletter subscription logic
    // - Validate email
    // - Store in newsletter_subscribers table or send to mailing service
    return back()->with('success', 'Thank you for subscribing!');
})->name('newsletter.subscribe');

// Contact form submission
Route::post('/contact/submit', function () {
    // TODO: Implement contact form handling
    // - Validate form data
    // - Send notification email
    // - Store in database
    return back()->with('success', 'Your message has been sent. We\'ll get back to you soon!');
})->name('contact.submit');
