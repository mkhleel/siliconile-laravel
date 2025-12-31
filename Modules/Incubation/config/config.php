<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Module Name
    |--------------------------------------------------------------------------
    |
    | The name of the Incubation module.
    |
    */
    'name' => 'Incubation',

    /*
    |--------------------------------------------------------------------------
    | Application Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the application process.
    |
    */
    'applications' => [
        // Code prefix for application codes (e.g., INC-2025-0001)
        'code_prefix' => env('INCUBATION_APP_CODE_PREFIX', 'INC'),

        // Maximum pitch deck file size in KB
        'max_pitch_deck_size' => env('INCUBATION_MAX_PITCH_DECK_SIZE', 20480), // 20MB

        // Allowed pitch deck file types
        'allowed_pitch_deck_types' => [
            'application/pdf',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ],

        // Auto-assign reviewers on submission
        'auto_assign_reviewers' => env('INCUBATION_AUTO_ASSIGN_REVIEWERS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Evaluation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the evaluation/scoring process.
    |
    */
    'evaluation' => [
        // Scoring criteria and their weights (must sum to 100)
        'criteria' => [
            'innovation' => ['label' => 'Innovation', 'weight' => 20],
            'team' => ['label' => 'Team', 'weight' => 25],
            'market' => ['label' => 'Market Potential', 'weight' => 20],
            'traction' => ['label' => 'Traction', 'weight' => 15],
            'scalability' => ['label' => 'Scalability', 'weight' => 10],
            'fit' => ['label' => 'Program Fit', 'weight' => 10],
        ],

        // Minimum score (out of 100) to auto-advance to interview
        'auto_advance_threshold' => env('INCUBATION_AUTO_ADVANCE_SCORE', 70),

        // Number of reviewers required before averaging scores
        'required_reviewers' => env('INCUBATION_REQUIRED_REVIEWERS', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mentorship Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the mentorship program.
    |
    */
    'mentorship' => [
        // Default session duration in minutes
        'default_session_duration' => env('INCUBATION_SESSION_DURATION', 60),

        // Maximum sessions per mentor per week
        'max_sessions_per_mentor_per_week' => env('INCUBATION_MAX_SESSIONS_PER_MENTOR', 10),

        // Maximum sessions per startup per week
        'max_sessions_per_startup_per_week' => env('INCUBATION_MAX_SESSIONS_PER_STARTUP', 3),

        // Cancellation deadline (hours before session)
        'cancellation_deadline_hours' => env('INCUBATION_CANCELLATION_DEADLINE', 24),

        // Send reminder notifications
        'send_reminders' => env('INCUBATION_SEND_REMINDERS', true),

        // Reminder hours before session
        'reminder_hours' => [24, 1], // 24 hours and 1 hour before
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Email notification configuration.
    |
    */
    'notifications' => [
        // Send email on application submission
        'on_application_submitted' => true,

        // Send email on status change
        'on_status_change' => true,

        // Send email on interview scheduled
        'on_interview_scheduled' => true,

        // Send email on acceptance
        'on_accepted' => true,

        // Send email on rejection
        'on_rejected' => true,

        // BCC admin on important notifications
        'bcc_admin' => env('INCUBATION_BCC_ADMIN', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Cross-module integration configuration.
    |
    */
    'integrations' => [
        // Auto-create member record on acceptance
        'create_member_on_accept' => env('INCUBATION_CREATE_MEMBER', true),

        // Default membership plan for accepted startups
        'default_membership_plan' => env('INCUBATION_DEFAULT_PLAN', null),

        // Slack webhook for notifications
        'slack_webhook' => env('INCUBATION_SLACK_WEBHOOK', null),
    ],
];
