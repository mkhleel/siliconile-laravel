<?php

declare(strict_types=1);

namespace Modules\Membership\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Membership\Events\MemberCreated;
use Modules\Membership\Events\SubscriptionActivated;
use Modules\Membership\Events\SubscriptionExpired;
use Modules\Membership\Listeners\LogSubscriptionActivated;
use Modules\Membership\Listeners\LogSubscriptionExpired;
use Modules\Membership\Listeners\SendMemberCreatedNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        MemberCreated::class => [
            SendMemberCreatedNotification::class,
        ],
        SubscriptionActivated::class => [
            LogSubscriptionActivated::class,
        ],
        SubscriptionExpired::class => [
            LogSubscriptionExpired::class,
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
    protected function configureEmailVerification(): void {}
}
