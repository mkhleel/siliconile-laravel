<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\SpaceBooking\Events\BookingCancelled;
use Modules\SpaceBooking\Events\BookingConfirmed;
use Modules\SpaceBooking\Events\BookingCreated;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the module.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        BookingCreated::class => [
            // SendBookingConfirmationEmail::class,
            // NotifyResourceManager::class,
        ],
        BookingConfirmed::class => [
            // SendBookingApprovedEmail::class,
        ],
        BookingCancelled::class => [
            // SendBookingCancelledEmail::class,
            // RefundPayment::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void
    {
        // ...
    }
}
