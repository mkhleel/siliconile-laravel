<?php

namespace Modules\Core\Settings;

use DateInterval;
use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name = 'Tenchology';

    public string $site_description = 'A modern and powerful Travel and Tour Management System built with love from Egypt, Luxor.';

    public array $site_languages = ['en'];

    public string $site_locale = 'en';

    public string $site_color;

    public ?string $site_logo;

    public ?string $site_dark_logo;

    public ?string $site_favicon;

    public string $site_theme = 'classic';

    public ?string $placeholder_image;

    public ?array $slider;

    public ?string $currency = 'USD';

    public ?string $preferred_currency = 'USD';

    public ?string $admin_prefix = 'admin';

    public bool $site_active = true;

    public ?array $disabled_modules;

    public ?array $brands;

    public ?array $faqs;

    public ?array $testimonials;

    public ?string $ai_provider = 'groq';

    public ?string $ai_model = 'llama-3.3-70b-versatile';

    public ?string $ai_api_key;

    public ?array $site_social;

    public ?string $zoom_client_id;

    public ?string $zoom_client_secret;

    public ?string $zoom_account_id;

    public ?string $watermark_text;

    public ?bool $watermark_enabled = false;

    public ?string $watermark_position = 'bottom-right';

    public ?string $watermark_font = 'Arial';

    public ?string $google_analytics_tracking_id;

    public ?string $google_tag_manager_id;

    public ?string $facebook_pixel_id;

    public ?string $mailchimp_api_key;

    public ?string $mailchimp_list_id;

    public ?string $mailchimp_email;

    public ?string $mailchimp_name;

    // smtp settings
    public ?string $smtp_host;

    public ?string $smtp_port;

    public ?string $smtp_username;

    public ?string $smtp_password;

    public ?string $smtp_encryption;

    public ?string $smtp_from_address;

    public ?string $smtp_from_name;

    // smtp settings

    public static function group(): string
    {
        return 'general';
    }

    public static function cacheFor(): DateInterval
    {
        return DateInterval::createFromDateString('1 day');
    }

    public static function encrypted(): array
    {
        return [
            'ai_api_key',
            'zoom_client_id',
            'zoom_client_secret',
            'zoom_account_id',
            'mailchimp_api_key',
            'mailchimp_list_id',
            'smtp_password',
        ];
    }
}
