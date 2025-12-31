<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | SpaceBooking Module Configuration
    |--------------------------------------------------------------------------
    */

    'name' => 'SpaceBooking',

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */

    // Default buffer time (minutes) between bookings if not set on resource
    'default_buffer_minutes' => 15,

    // Minimum booking duration (minutes)
    'min_booking_minutes' => 30,

    // How far in advance bookings can be made (days)
    'max_advance_booking_days' => 90,

    // Allow same-day bookings?
    'allow_same_day_booking' => true,

    // Minimum hours before a booking can be cancelled
    'cancellation_notice_hours' => 2,

    /*
    |--------------------------------------------------------------------------
    | Operating Hours (Default)
    |--------------------------------------------------------------------------
    */

    'default_operating_hours' => [
        'start' => '08:00',
        'end' => '20:00',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    */

    'default_currency' => 'EGP',

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'booking_confirmation' => true,
        'booking_reminder' => true,
        'reminder_hours_before' => 1,
    ],
];
