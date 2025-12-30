<?php

return [
    'name' => 'Core',

    'localization' => [
        'base_currency' => env('APP_BASE_CURRENCY', 'USD'),
        'preferred_currency' => env('APP_PREFERRED_CURRENCY', 'SAR'),
        'languages' => explode(',', env('APP_LOCALIZATION_LANGUAGES', 'en')),
        'driver' => env('APP_LOCALIZATION_DRIVER', 'json'),
    ],

    'settings_mapping' => [
        'smtp_host' => 'mail.mailers.smtp.host',
        'smtp_port' => 'mail.mailers.smtp.port',
        'smtp_username' => 'mail.mailers.smtp.username',
        'smtp_password' => 'mail.mailers.smtp.password',
        'smtp_encryption' => 'mail.mailers.smtp.encryption',
        'from_address' => 'mail.from.address',
        'from_name' => 'mail.from.name',
        'site_name' => 'app.name',
        'site_theme' => 'app.site_theme',
        'timezone' => 'app.timezone',
        'site_languages' => 'core.localization.languages',
        'ai_provider' => 'core.ai.default_provider',
        'ai_model' => 'core.ai.default_model',
        'ai_api_key' => 'prism.providers.'.config('core.ai.default_provider').'.api_key',
    ],
    'ai' => [
        'default_provider' => 'groq',
        'prompts' => [
            'system' => 'You are a helpful AI assistant. You are a specialist in tour guiding and travel planning. specifically in Egypt. You are able to answer questions about travel, culture, history, and local attractions. You can also assist with itinerary planning and provide recommendations for travelers. You provide accurate, concise, and helpful responses to user queries. Always ask for clarification if the user\'s request is ambiguous.',
        ],
        'default_model' => 'gpt-3.5-turbo',
        'default_temperature' => 0.7,
        'default_max_tokens' => 1000,

    ],

    'enable_wire_navigate' => env('ENABLE_WIRE_NAVIGATE', true),
];
