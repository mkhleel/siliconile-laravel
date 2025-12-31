<?php

declare(strict_types=1);

namespace Modules\Incubation\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Incubation\Events\ApplicationAccepted;
use Modules\Incubation\Events\ApplicationRejected;
use Modules\Incubation\Events\ApplicationStatusChanged;
use Modules\Incubation\Events\ApplicationSubmitted;
use Modules\Incubation\Events\CohortOpened;
use Modules\Incubation\Events\CohortStatusChanged;
use Modules\Incubation\Events\MentorshipSessionBooked;
use Modules\Incubation\Events\MentorshipSessionCancelled;
use Modules\Incubation\Events\MentorshipSessionCompleted;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Application events
        ApplicationSubmitted::class => [
            // Add listeners here, e.g., SendApplicationConfirmationEmail::class
        ],
        ApplicationStatusChanged::class => [
            // Notify applicant of status change
        ],
        ApplicationAccepted::class => [
            // Create member record in Membership module
            // Send acceptance notification
        ],
        ApplicationRejected::class => [
            // Send rejection notification
        ],

        // Cohort events
        CohortStatusChanged::class => [],
        CohortOpened::class => [
            // Send notification to mailing list
        ],

        // Mentorship events
        MentorshipSessionBooked::class => [
            // Send calendar invite
            // Notify mentor and mentee
        ],
        MentorshipSessionCompleted::class => [
            // Send feedback request
            // Update mentor statistics
        ],
        MentorshipSessionCancelled::class => [
            // Notify parties
        ],
    ];
}
