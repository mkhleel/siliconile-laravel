<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Network Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the Network module.
    | Router credentials are stored in the database using spatie/laravel-settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Connection Settings
    |--------------------------------------------------------------------------
    |
    | These are fallback values used when database settings are not yet configured.
    | Once configured via Filament admin panel, database values take precedence.
    |
    */
    'defaults' => [
        'port' => 8728,
        'ssl_port' => 8729,
        'connection_timeout' => 10,
        'hotspot_profile' => 'default',
        'hotspot_server' => 'hotspot1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Configure which queue to use for Network jobs.
    |
    */
    'queue' => [
        'connection' => env('NETWORK_QUEUE_CONNECTION', 'default'),
        'name' => env('NETWORK_QUEUE_NAME', 'network'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | Configure retry behavior for failed API operations.
    |
    */
    'retry' => [
        'max_attempts' => 3,
        'backoff_seconds' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure logging behavior for the Network module.
    |
    */
    'logging' => [
        'enabled' => true,
        'channel' => env('NETWORK_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching for API responses.
    |
    */
    'cache' => [
        'online_count_ttl' => 10, // seconds
        'user_list_ttl' => 60, // seconds
    ],
];
